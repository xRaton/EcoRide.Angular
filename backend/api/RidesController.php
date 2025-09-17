<?php
// api/RidesController.php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../libs/Response.php';
require_once __DIR__ . '/../libs/Mailer.php';

class RidesController {
    public function list($query) {
        $pdo = Database::getConnection();
        $sql = 'SELECT r.*, u.pseudo as driver_name, v.marque, v.modele FROM rides r JOIN users u ON r.driver_id = u.id JOIN vehicles v ON r.vehicle_id = v.id WHERE r.seats_available > 0';
        $params = [];
        if (!empty($query['from_city'])) { $sql .= ' AND r.from_city LIKE ?'; $params[] = '%'.$query['from_city'].'%'; }
        if (!empty($query['to_city'])) { $sql .= ' AND r.to_city LIKE ?'; $params[] = '%'.$query['to_city'].'%'; }
        if (!empty($query['date'])) { $sql .= ' AND DATE(r.departure_time) = ?'; $params[] = $query['date']; }
        if (isset($query['is_ecolo'])) { $sql .= ' AND r.is_ecolo = ?'; $params[] = $query['is_ecolo'] ? 1 : 0; }
        if (!empty($query['max_price'])) { $sql .= ' AND r.price <= ?'; $params[] = $query['max_price']; }
        $sql .= ' ORDER BY r.departure_time ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        Response::json($stmt->fetchAll());
    }

    public function create($jwtData, $data) {
        $pdo = Database::getConnection();
        $required = ['vehicle_id','from_city','to_city','departure_time','seats','price'];
        foreach ($required as $r) if (empty($data[$r])) Response::json(['error' => "Missing $r"], 400);
        // check vehicle ownership
        $v = $pdo->prepare('SELECT * FROM vehicles WHERE id = ? AND user_id = ?');
        $v->execute([$data['vehicle_id'], $jwtData->sub]); if (!$v->fetch()) Response::json(['error' => 'Véhicule non trouvé ou non propriétaire'], 403);
        $is_ecolo = !empty($data['is_ecolo']) ? 1 : 0;
        $ins = $pdo->prepare('INSERT INTO rides (driver_id,vehicle_id,from_city,to_city,departure_time,arrival_time,seats,seats_available,price,is_ecolo,status) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $ins->execute([$jwtData->sub,$data['vehicle_id'],$data['from_city'],$data['to_city'],$data['departure_time'],$data['arrival_time'] ?? null,$data['seats'],$data['seats'],$data['price'],$is_ecolo,'scheduled']);
        Response::json(['message' => 'Trajet créé', 'ride_id' => $pdo->lastInsertId()], 201);
    }

    public function detail($rideId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT r.*, u.pseudo as driver_name, u.email as driver_email, v.* FROM rides r JOIN users u ON r.driver_id = u.id JOIN vehicles v ON r.vehicle_id = v.id WHERE r.id = ?');
        $stmt->execute([$rideId]); $r = $stmt->fetch();
        if (!$r) Response::json(['error' => 'Trajet introuvable'], 404);
        $rv = $pdo->prepare('SELECT note,commentaire,created_at FROM reviews WHERE target_user_id = ? AND status = "approved"');
        $rv->execute([$r['driver_id']]);
        $r['reviews'] = $rv->fetchAll();
        Response::json($r);
    }

    public function start($rideId, $jwtData) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT driver_id,status FROM rides WHERE id = ?');
        $stmt->execute([$rideId]); $r = $stmt->fetch(); if (!$r) Response::json(['error' => 'Trajet introuvable'], 404);
        if ($r['driver_id'] != $jwtData->sub) Response::json(['error' => 'Non autorisé'], 403);
        if ($r['status'] !== 'scheduled') Response::json(['error' => 'Trajet pas dans un état démarrable'], 400);
        $pdo->prepare('UPDATE rides SET status = ?, departure_time = COALESCE(departure_time,NOW()) WHERE id = ?')->execute(['started',$rideId]);
        Response::json(['message' => 'Trajet démarré']);
    }

    public function finish($rideId, $jwtData) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT driver_id,status FROM rides WHERE id = ?');
        $stmt->execute([$rideId]); $r = $stmt->fetch(); if (!$r) Response::json(['error' => 'Trajet introuvable'], 404);
        if ($r['driver_id'] != $jwtData->sub) Response::json(['error' => 'Non autorisé'], 403);
        if ($r['status'] !== 'started') Response::json(['error' => 'Trajet pas en cours'], 400);
        $pdo->prepare('UPDATE rides SET status = ?, arrival_time = NOW() WHERE id = ?')->execute(['finished',$rideId]);
        Response::json(['message' => 'Trajet terminé']);
    }

    public function cancel($rideId, $jwtData) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT driver_id,status FROM rides WHERE id = ?');
        $stmt->execute([$rideId]); $r = $stmt->fetch(); if (!$r) Response::json(['error' => 'Trajet introuvable'], 404);
        if ($r['driver_id'] != $jwtData->sub) Response::json(['error' => 'Non autorisé'], 403);

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE rides SET status = ? WHERE id = ?')->execute(['cancelled',$rideId]);

        $bk = $pdo->prepare('SELECT id,passenger_id,total_price,platform_fee FROM bookings WHERE ride_id = ? AND status = "confirmed"');
        $bk->execute([$rideId]); $bookings = $bk->fetchAll();
        $refundStmt = $pdo->prepare('UPDATE users SET credits = credits + ? WHERE id = ?');
        $trans = $pdo->prepare('INSERT INTO transactions (user_id,amount,reason,related_booking) VALUES (?,?,?,?)');

        foreach ($bookings as $b) {
            $refund = intval(round($b['total_price'])) + intval($b['platform_fee']);
            $refundStmt->execute([$refund, $b['passenger_id']]);
            $trans->execute([$b['passenger_id'],$refund,'Refund ride cancelled',$b['id']]);
            $pdo->prepare('UPDATE bookings SET status = "cancelled" WHERE id = ?')->execute([$b['id']]);
            Mailer::send('user@example.test','Trajet annulé','Votre trajet a été annulé, remboursement effectué.');
        }
        $pdo->commit();
        Response::json(['message' => 'Trajet annulé et passagers remboursés']);
    }
}
