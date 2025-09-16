<?php
// api/controllers/UsersController.php
require_once __DIR__.'/../libs/Response.php';
require_once __DIR__.'/../libs/Database.php';

class UsersController {
    private $conn;
    public function __construct(){ $db = new Database(); $this->conn = $db->getConnection(); }

    public function me($user){
        // user is decoded JWT data (object)
        $stmt = $this->conn->prepare('SELECT id,pseudo,email,role,credits,created_at,suspended FROM users WHERE id = ?');
        $stmt->execute([$user->id]);
        $u = $stmt->fetch();
        if(!$u) Response::json(['error'=>'Utilisateur introuvable'],404);
        Response::json($u);
    }

    public function addVehicle($userId, $data){
        $required = ['immatriculation','marque','modele','energie','date_premiere_immat','seats'];
        foreach($required as $r) if(empty($data[$r])) Response::json(['error'=>'Missing '.$r],400);
        $stmt = $this->conn->prepare('INSERT INTO vehicles (user_id,immatriculation,marque,modele,couleur,energie,date_premiere_immat,seats) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([$userId,$data['immatriculation'],$data['marque'],$data['modele'],$data['couleur'] ?? null,$data['energie'],$data['date_premiere_immat'],$data['seats']]);
        Response::json(['message'=>'Véhicule ajouté','vehicle_id'=>$this->conn->lastInsertId()],201);
    }

    public function setPreferences($userId, $prefs){
        // prefs: array of {key_name, value}
        $this->conn->beginTransaction();
        $del = $this->conn->prepare('DELETE FROM preferences WHERE user_id = ?');
        $del->execute([$userId]);
        $ins = $this->conn->prepare('INSERT INTO preferences (user_id,key_name,value) VALUES (?,?,?)');
        foreach($prefs as $p){
            if(isset($p['key_name']) && isset($p['value'])) $ins->execute([$userId,$p['key_name'],$p['value']]);
        }
        $this->conn->commit();
        Response::json(['message'=>'Préférences mises à jour']);
    }
}
