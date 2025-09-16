<?php
require_once __DIR__.'/../libs/Response.php';
require_once __DIR__.'/../libs/Database.php';

class AdminController {
    private $conn;
    public function __construct(){ $db = new Database(); $this->conn = $db->getConnection(); }

    public function suspendUser($userId){
        $stmt = $this->conn->prepare('UPDATE users SET suspended = 1 WHERE id = ?');
        $stmt->execute([$userId]);
        Response::json(['message'=>'Utilisateur suspendu']);
    }

    public function unsuspendUser($userId){
        $stmt = $this->conn->prepare('UPDATE users SET suspended = 0 WHERE id = ?');
        $stmt->execute([$userId]);
        Response::json(['message'=>'Utilisateur réactivé']);
    }

    public function createEmployee($data){
        // create user with role employee (password must be provided)
        if(empty($data['pseudo']) || empty($data['email']) || empty($data['password'])) Response::json(['error'=>'Champs manquants'],400);
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare('INSERT INTO users (pseudo,email,password,role,credits) VALUES (?,?,?,?,0)');
        $stmt->execute([$data['pseudo'],$data['email'],$hash,'employee']);
        $id = $this->conn->lastInsertId();
        $this->conn->prepare('INSERT INTO employees (user_id) VALUES (?)')->execute([$id]);
        Response::json(['message'=>'Employé créé','id'=>$id],201);
    }

    public function statsByDay($day){
        // if day not provided, compute last 7 days
        if($day){
            $stmt = $this->conn->prepare('SELECT * FROM stats WHERE day = ?');
            $stmt->execute([$day]); $s = $stmt->fetch();
            Response::json($s ?: ['day'=>$day,'total_rides'=>0,'platform_credits'=>0]);
        } else {
            $stmt = $this->conn->prepare('SELECT * FROM stats ORDER BY day DESC LIMIT 30');
            $stmt->execute(); Response::json($stmt->fetchAll());
        }
    }

    // regenerate stats (simple calc)
    public function recomputeStats($day = null){
        if($day){
            $d = $day;
            $stmt = $this->conn->prepare('SELECT COUNT(*) as total_rides, SUM(b.platform_fee) as platform_credits FROM rides r LEFT JOIN bookings b ON r.id = b.ride_id WHERE DATE(r.created_at) = ? AND b.status = \'completed\'');
            $stmt->execute([$d]); $res = $stmt->fetch();
            $ins = $this->conn->prepare('INSERT INTO stats (day,total_rides,platform_credits) VALUES (?,?,?) ON DUPLICATE KEY UPDATE total_rides = VALUES(total_rides), platform_credits = VALUES(platform_credits)');
            $ins->execute([$d, $res['total_rides'] ?? 0, $res['platform_credits'] ?? 0]);
            Response::json(['message'=>'Stats recomputed for '.$d]);
        } else {
            // last 7 days
            $stmt = $this->conn->prepare('SELECT DATE(created_at) as day, COUNT(*) as total_rides FROM rides GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 7');
            $stmt->execute(); Response::json($stmt->fetchAll());
        }
    }
}
