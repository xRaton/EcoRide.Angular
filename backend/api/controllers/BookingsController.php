<?php
require_once __DIR__.'/../libs/Response.php';
require_once __DIR__.'/../libs/Database.php';
require_once __DIR__.'/../libs/Mailer.php';

class BookingsController {
    private $conn;
    public function __construct(){ $db = new Database(); $this->conn = $db->getConnection(); }

    // passenger requests to join -> double confirmation flow simplified:
    // Step 1: create pending booking (status = pending)
    // Step 2: passenger confirms (endpoint confirmBooking) -> deduct credits and set confirmed
    public function requestJoin($rideId, $userId, $data){
        $seats = intval($data['seats'] ?? 1);
        if($seats < 1) Response::json(['error'=>'Invalid seats'],400);
        // check ride exists and seats_available
        $stmt = $this->conn->prepare('SELECT seats_available, price FROM rides WHERE id = ? FOR UPDATE');
        $stmt->execute([$rideId]); $r = $stmt->fetch();
        if(!$r) Response::json(['error'=>'Trajet introuvable'],404);
        if($r['seats_available'] < $seats) Response::json(['error'=>'Pas assez de places'],409);
        // create pending booking
        $totalPrice = floatval($r['price']) * $seats;
        $bk = $this->conn->prepare('INSERT INTO bookings (ride_id,passenger_id,seats_booked,total_price,platform_fee,status) VALUES (?,?,?,?,?,?)');
        $bk->execute([$rideId,$userId,$seats,$totalPrice,2,'pending']);
        $bookingId = $this->conn->lastInsertId();
        // Notify passenger: ask for final confirmation (client should call confirm endpoint)
        Response::json(['message'=>'Réservation en attente. Confirmez pour dépenser vos crédits','booking_id'=>$bookingId],201);
    }

    // passenger confirms booking (double confirmation)
    public function confirmBooking($bookingId, $userId){
        $this->conn->beginTransaction();
        $stmt = $this->conn->prepare('SELECT b.*, r.seats_available, r.driver_id FROM bookings b JOIN rides r ON b.ride_id = r.id WHERE b.id = ? FOR UPDATE');
        $stmt->execute([$bookingId]); $b = $stmt->fetch();
        if(!$b) { $this->conn->rollBack(); Response::json(['error'=>'Réservation introuvable'],404); }
        if($b['passenger_id'] != $userId) { $this->conn->rollBack(); Response::json(['error'=>'Non autorisé'],403); }
        if($b['status'] !== 'pending'){ $this->conn->rollBack(); Response::json(['error'=>'Réservation non en attente'],400); }
        if($b['seats_available'] < $b['seats_booked']){ $this->conn->rollBack(); Response::json(['error'=>'Plus assez de places'],409); }
        // check passenger credits
        $u = $this->conn->prepare('SELECT credits FROM users WHERE id = ? FOR UPDATE');
        $u->execute([$userId]); $user = $u->fetch();
        $amountNeeded = intval(round($b['total_price'])) + intval($b['platform_fee']);
        if($user['credits'] < $amountNeeded){ $this->conn->rollBack(); Response::json(['error'=>'Crédits insuffisants'],409); }
        // deduct credits from passenger
        $ded = $this->conn->prepare('UPDATE users SET credits = credits - ? WHERE id = ?');
        $ded->execute([$amountNeeded, $userId]);
        // record transaction
        $t = $this->conn->prepare('INSERT INTO transactions (user_id, amount, reason, related_booking) VALUES (?,?,?,?)');
        $t->execute([$userId, -$amountNeeded, 'Booking confirm', $bookingId]);
        // decrement seats_available
        $upd = $this->conn->prepare('UPDATE rides SET seats_available = seats_available - ? WHERE id = ?');
        $upd->execute([$b['seats_booked'], $b['ride_id']]);
        // mark booking confirmed
        $this->conn->prepare('UPDATE bookings SET status = \'confirmed\' WHERE id = ?')->execute([$bookingId]);
        $this->conn->commit();
        // Notify driver (placeholder)
        // Mailer::send(...)
        Response::json(['message'=>'Réservation confirmée']);
    }

    // After ride finished and passenger validation, complete booking: transfer driver credits
    public function completeBooking($bookingId, $validatorId){
        // This endpoint should be called after passenger validates ride is ok.
        $this->conn->beginTransaction();
        $stmt = $this->conn->prepare('SELECT b.*, r.driver_id FROM bookings b JOIN rides r ON b.ride_id = r.id WHERE b.id = ? FOR UPDATE');
        $stmt->execute([$bookingId]); $b = $stmt->fetch();
        if(!$b){ $this->conn->rollBack(); Response::json(['error'=>'Booking not found'],404); }
        if($b['status'] !== 'confirmed'){ $this->conn->rollBack(); Response::json(['error'=>'Booking not confirmed'],400); }
        // transfer price to driver credits (platform keeps fixed platform_fee)
        $priceCredits = intval(round($b['total_price']));
        $driverId = $b['driver_id'];
        $this->conn->prepare('UPDATE users SET credits = credits + ? WHERE id = ?')->execute([$priceCredits, $driverId]);
        $this->conn->prepare('INSERT INTO transactions (user_id, amount, reason, related_booking) VALUES (?,?,?,?)')->execute([$driverId, $priceCredits, 'Ride earnings', $bookingId]);
        // platform fee (already taken from passenger) remains in system; we can add to stats later.
        $this->conn->prepare('UPDATE bookings SET status = \'completed\' WHERE id = ?')->execute([$bookingId]);
        $this->conn->commit();
        Response::json(['message'=>'Réservation complétée, conducteur crédité']);
    }

    public function cancelBooking($bookingId, $userId){
        // passenger cancels before confirmation -> just change; if after confirmed -> refund rules
        $this->conn->beginTransaction();
        $stmt = $this->conn->prepare('SELECT * FROM bookings WHERE id = ? FOR UPDATE');
        $stmt->execute([$bookingId]); $b = $stmt->fetch();
        if(!$b){ $this->conn->rollBack(); Response::json(['error'=>'Booking not found'],404); }
        if($b['passenger_id'] != $userId) { $this->conn->rollBack(); Response::json(['error'=>'Non autorisé'],403); }
        if($b['status'] === 'pending'){
            $this->conn->prepare('UPDATE bookings SET status = \'cancelled\' WHERE id = ?')->execute([$bookingId]);
            $this->conn->commit();
            Response::json(['message'=>'Réservation annulée']);
        } elseif($b['status'] === 'confirmed'){
            // refund passenger and increase seats_available
            $refund = intval(round($b['total_price'])) + intval($b['platform_fee']);
            $this->conn->prepare('UPDATE users SET credits = credits + ? WHERE id = ?')->execute([$refund, $userId]);
            $this->conn->prepare('INSERT INTO transactions (user_id, amount, reason, related_booking) VALUES (?,?,?,?)')->execute([$userId,$refund,'Refund booking cancel',$bookingId]);
            $this->conn->prepare('UPDATE rides SET seats_available = seats_available + ? WHERE id = ?')->execute([$b['seats_booked'],$b['ride_id']]);
            $this->conn->prepare('UPDATE bookings SET status = \'cancelled\' WHERE id = ?')->execute([$bookingId]);
            $this->conn->commit();
            Response::json(['message'=>'Réservation annulée et remboursée']);
        } else {
            $this->conn->rollBack(); Response::json(['error'=>'Impossible d\'annuler ce statut'],400);
        }
    }
}
