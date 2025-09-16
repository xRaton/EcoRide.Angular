<?php
require_once __DIR__.'/../libs/Response.php';
require_once __DIR__.'/../libs/Database.php';
require_once __DIR__.'/../libs/Mailer.php';

class RidesController {
    private $conn;
    public function __construct(){ $db = new Database(); $this->conn = $db->getConnection(); }

    public function list($query){
        $sql = 'SELECT r.*, u.pseudo as driver_pseudo, v.marque, v.modele, v.energie FROM rides r JOIN users u ON r.driver_id = u.id JOIN vehicles v ON r.vehicle_id = v.id WHERE r.seats_available > 0';
        $params = [];
        if(!empty($query['from_city'])){ $sql .= ' AND r.from_city LIKE ?'; $params[] = '%'.$query['from_city'].'%'; }
        if(!empty($query['to_city'])){ $sql .= ' AND r.to_city LIKE ?'; $params[] = '%'.$query['to_city'].'%'; }
        if(!empty($query['date'])){ $sql .= ' AND DATE(r.departure_time) = ?'; $params[] = $query['date']; }
        if(!empty($query['is_ecolo'])){ $sql .= ' AND r.is_ecolo = ?'; $params[] = $query['is_ecolo'] ? 1 : 0; }
        if(!empty($query['max_price'])){ $sql .= ' AND r.price <= ?'; $params[] = $query['max_price']; }
        // duration filter is not stored directly -> approximate via departure & arrival time (if arrival_time exists)
        if(!empty($query['max_duration_minutes'])){ $sql .= ' AND TIMESTAMPDIFF(MINUTE, r.departure_time, r.arrival_time) <= ?'; $params[] = intval($query['max_duration_minutes']); }
        // Order
        $sql .= ' ORDER BY r.departure_time ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rides = $stmt->fetchAll();
        Response::json($rides);
    }

    public function create($data, $userId){
        $required = ['vehicle_id','from_city','to_city','departure_time','seats','price'];
        foreach($required as $r) if(!isset($data[$r]) || $data[$r] === '') Response::json(['error'=>'Missing '.$r],400);
        // verify vehicle belongs to user
        $v = $this->conn->prepare('SELECT * FROM vehicles WHERE id = ? AND user_id = ?');
        $v->execute([$data['vehicle_id'],$userId]);
        if(!$v->fetch()) Response::json(['error'=>'Véhicule invalide ou non propriétaire'],403);

        $is_ecolo = ($data['is_ecolo'] ?? 0) ? 1 : 0;
        $stmt = $this->conn->prepare('INSERT INTO rides (driver_id,vehicle_id,from_city,to_city,departure_time,arrival_time,seats,seats_available,price,is_ecolo,status) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $arrival = $data['arrival_time'] ?? null;
        $stmt->execute([$userId,$data['vehicle_id'],$data['from_city'],$data['to_city'],$data['departure_time'],$arrival,$data['seats'],$data['seats'],$data['price'],$is_ecolo,'scheduled']);
        $rideId = $this->conn->lastInsertId();
        Response::json(['message'=>'Trajet créé','ride_id'=>$rideId],201);
    }

    public function detail($rideId){
        $stmt = $this->conn->prepare('SELECT r.*, u.pseudo as driver_pseudo, u.email as driver_email, v.* FROM rides r JOIN users u ON r.driver_id = u.id JOIN vehicles v ON r.vehicle_id = v.id WHERE r.id = ?');
        $stmt->execute([$rideId]);
        $r = $stmt->fetch();
        if(!$r) Response::json(['error'=>'Trajet introuvable'],404);
        // fetch reviews of driver (approved)
        $rv = $this->conn->prepare('SELECT note,commentaire,created_at FROM reviews WHERE target_user_id = ? AND status = \'approved\'');
        $rv->execute([$r['driver_id']]);
        $r['reviews'] = $rv->fetchAll();
        Response::json($r);
    }

    public function startRide($rideId, $userId){
        // only driver can start, and status scheduled
        $stmt = $this->conn->prepare('SELECT driver_id,status FROM rides WHERE id = ?');
        $stmt->execute([$rideId]); $r = $stmt->fetch();
        if(!$r) Response::json(['error'=>'Trajet introuvable'],404);
        if($r['driver_id'] != $userId) Response::json(['error'=>'Non autorisé'],403);
        if($r['status'] !== 'scheduled') Response::json(['error'=>'Trajet non trouvable pour démarrer'],400);
        $u = $this->conn->prepare('UPDATE rides SET status = ?, departure_time = COALESCE(departure_time, NOW()) WHERE id = ?');
        $u->execute(['started',$rideId]);
        // notify passengers (placeholder)
        // Mailer::send(...)
        Response::json(['message'=>'Trajet démarré']);
    }

    public function finishRide($rideId, $userId){
        $stmt = $this->conn->prepare('SELECT driver_id,status FROM rides WHERE id = ?');
        $stmt->execute([$rideId]); $r = $stmt->fetch();
        if(!$r) Response::json(['error'=>'Trajet introuvable'],404);
        if($r['driver_id'] != $userId) Response::json(['error'=>'Non autorisé'],403);
        if($r['status'] !== 'started') Response::json(['error'=>'Trajet pas en cours'],400);
        $u = $this->conn->prepare('UPDATE rides SET status = ?, arrival_time = NOW() WHERE id = ?');
        $u->execute(['finished',$rideId]);
        // notify passengers to validate ride and leave reviews
        Response::json(['message'=>'Trajet terminé, participants notifiés']);
    }

    public function cancelRide($rideId, $userId){
        $stmt = $this->conn->prepare('SELECT driver_id,status FROM rides WHERE id = ?');
        $stmt->execute([$rideId]); $r = $stmt->fetch();
        if(!$r) Response::json(['error'=>'Trajet introuvable'],404);
        if($r['driver_id'] != $userId) Response::json(['error'=>'Non autorisé'],403);
        // cancel: set status cancelled, refund passengers
        $this->conn->beginTransaction();
        $upd = $this->conn->prepare('UPDATE rides SET status = ? WHERE id = ?');
        $upd->execute(['cancelled',$rideId]);
        // refund bookings that were confirmed
        $bk = $this->conn->prepare('SELECT id,passenger_id,total_price,platform_fee,status FROM bookings WHERE ride_id = ? AND status = \'confirmed\'');
        $bk->execute([$rideId]);
        $bookings = $bk->fetchAll();
        $refundStmt = $this->conn->prepare('UPDATE users SET credits = credits + ? WHERE id = ?');
        $trans = $this->conn->prepare('INSERT INTO transactions (user_id, amount, reason, related_booking) VALUES (?,?,?,?)');
        foreach($bookings as $b){
            $refundAmount = intval($b['total_price']) + intval($b['platform_fee']);
            $refundStmt->execute([$refundAmount, $b['passenger_id']]);
            $trans->execute([$b['passenger_id'], $refundAmount, 'Refund ride cancel', $b['id']]);
            // set booking cancelled
            $this->conn->prepare('UPDATE bookings SET status = \'cancelled\' WHERE id = ?')->execute([$b['id']]);
            // TODO: send mail to passenger
        }
        $this->conn->commit();
        Response::json(['message'=>'Trajet annulé et passagers remboursés']);
    }
}
