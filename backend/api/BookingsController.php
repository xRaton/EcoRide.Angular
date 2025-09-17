<?php
// api/BookingsController.php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../libs/Response.php';
require_once __DIR__ . '/../libs/Mailer.php';

class BookingsController {
    public function requestJoin($rideId, $jwtData, $data) {
        $pdo = Database::getConnection();
        $seats = intval($data['seats'] ?? 1);
        if ($seats < 1) Response::json(['error' => 'Seats invalid'], 400);

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT seats_available, price FROM rides WHERE id = ? FOR UPDATE');
        $stmt->execute([$rideId]); $r = $stmt->fetch();
        if (!$r) { $pdo->rollBack(); Response::json(['error' => 'Trajet introuvable'], 404); }
        if ($r['seats_available'] < $seats) { $pdo->rollBack(); Response::json(['error' => 'Pas assez de places'], 409); }

        $totalPrice = floatval($r['price']) * $seats;
        $ins = $pdo->prepare('INSERT INTO bookings (ride_id,passenger_id,seats_booked,total_price,platform_fee,status) VALUES (?,?,?,?,?,?)');
        $ins->execute([$rideId,$jwtData->sub,$seats,$totalPrice,2,'pending']);
        $bookingId = $pdo->lastInsertId();
        $pdo->commit();
        Response::json(['message' => 'Réservation en attente. Confirmez pour dépenser vos crédits','booking_id' => $bookingId], 201);
    }

    public function confirm($bookingId, $jwtData) {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT b.*, r.seats_available, r.driver_id FROM bookings b JOIN rides r ON b.ride_id = r.id WHERE b.id = ? FOR UPDATE');
        $stmt->execute([$bookingId]); $b = $stmt->fetch();
        if (!$b) { $pdo->rollBack(); Response::json(['error' => 'Réservation introuvable'], 404); }
        if ($b['passenger_id'] != $jwtData->sub) { $pdo->rollBack(); Response::json(['error' => 'Non autorisé'], 403); }
        if ($b['status'] !== 'pending') { $pdo->rollBack(); Response::json(['error' => 'Réservation non en attente'], 400); }
        if ($b['seats_available'] < $b['seats_booked']) { $pdo->rollBack(); Response::json(['error' => 'Plus assez de places'], 409); }

        $userStmt = $pdo->prepare('SELECT credits FROM users WHERE id = ? FOR UPDATE'); $userStmt->execute([$jwtData->sub]); $u = $userStmt->fetch();
        $need = intval(round($b['total_price'])) + intval($b['platform_fee']);
        if ($u['credits'] < $need) { $pdo->rollBack(); Response::json(['error' => 'Crédits insuffisants'], 409); }

        $pdo->prepare('UPDATE users SET credits = credits - ? WHERE id = ?')->execute([$need, $jwtData->sub]);
        $pdo->prepare('INSERT INTO transactions (user_id,amount,reason,related_booking) VALUES (?,?,?,?)')->execute([$jwtData->sub, -$need, 'Booking confirm', $bookingId]);
        $pdo->prepare('UPDATE rides SET seats_available = seats_available - ? WHERE id = ?')->execute([$b['seats_booked'],$b['ride_id']]);
        $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute(['confirmed',$bookingId]);
        $pdo->commit();

        Mailer::send('driver@example.test','Nouveau passager','Un passager a confirmé sa réservation.');
        Response::json(['message' => 'Réservation confirmée']);
    }

    public function complete($bookingId, $jwtData) {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT b.*, r.driver_id FROM bookings b JOIN rides r ON b.ride_id = r.id WHERE b.id = ? FOR UPDATE');
        $stmt->execute([$bookingId]); $b = $stmt->fetch();
        if (!$b) { $pdo->rollBack(); Response::json(['error' => 'Réservation introuvable'], 404); }
        if ($b['status'] !== 'confirmed') { $pdo->rollBack(); Response::json(['error' => 'Réservation non confirmée'], 400); }

        $price = intval(round($b['total_price']));
        $pdo->prepare('UPDATE users SET credits = credits + ? WHERE id = ?')->execute([$price, $b['driver_id']]);
        $pdo->prepare('INSERT INTO transactions (user_id,amount,reason,related_booking) VALUES (?,?,?,?)')->execute([$b['driver_id'],$price,'Ride earnings',$bookingId]);
        $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute(['completed',$bookingId]);
        $pdo->commit();
        Response::json(['message' => 'Réservation complétée, conducteur crédité']);
    }

    public function cancel($bookingId, $jwtData) {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? FOR UPDATE');
        $stmt->execute([$bookingId]); $b = $stmt->fetch();
        if (!$b) { $pdo->rollBack(); Response::json(['error' => 'Réservation introuvable'], 404); }
        if ($b['passenger_id'] != $jwtData->sub) { $pdo->rollBack(); Response::json(['error' => 'Non autorisé'], 403); }

        if ($b['status'] === 'pending') {
            $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute(['cancelled',$bookingId]);
            $pdo->commit();
            Response::json(['message' => 'Réservation annulée']);
        } elseif ($b['status'] === 'confirmed') {
            $refund = intval(round($b['total_price'])) + intval($b['platform_fee']);
            $pdo->prepare('UPDATE users SET credits = credits + ? WHERE id = ?')->execute([$refund,$jwtData->sub]);
            $pdo->prepare('INSERT INTO transactions (user_id,amount,reason,related_booking) VALUES (?,?,?,?)')->execute([$jwtData->sub,$refund,'Refund booking cancel',$bookingId]);
            $pdo->prepare('UPDATE rides SET seats_available = seats_available + ? WHERE id = ?')->execute([$b['seats_booked'],$b['ride_id']]);
            $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute(['cancelled',$bookingId]);
            $pdo->commit();
            Response::json(['message' => 'Réservation annulée et remboursée']);
        } else {
            $pdo->rollBack(); Response::json(['error' => 'Impossible d\'annuler ce statut'], 400);
        }
    }
}
