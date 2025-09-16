<?php
require_once __DIR__.'/../libs/Response.php';
require_once __DIR__.'/../libs/Database.php';

class VehiclesController {
    private $conn;
    public function __construct(){ $db = new Database(); $this->conn = $db->getConnection(); }

    public function listByUser($userId){
        $stmt = $this->conn->prepare('SELECT * FROM vehicles WHERE user_id = ?');
        $stmt->execute([$userId]);
        Response::json($stmt->fetchAll());
    }

    public function get($vehicleId){
        $stmt = $this->conn->prepare('SELECT * FROM vehicles WHERE id = ?');
        $stmt->execute([$vehicleId]);
        $v = $stmt->fetch();
        if(!$v) Response::json(['error'=>'Véhicule introuvable'],404);
        Response::json($v);
    }
}
