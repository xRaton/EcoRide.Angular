<?php
// api/controllers/AuthController.php
require_once __DIR__.'/../libs/Response.php';
require_once __DIR__.'/../libs/Database.php';
require_once __DIR__.'/../libs/JwtHandler.php';

class AuthController {
    private $conn;
    private $jwt;

    public function __construct(){
        $db = new Database(); $this->conn = $db->getConnection();
        $this->jwt = new JwtHandler();
    }

    public function register($data){
        // expected: pseudo, email, password
        if(empty($data['pseudo']) || empty($data['email']) || empty($data['password'])){
            Response::json(['error'=>'Champs manquants'],400);
        }
        // email unique
        $stmt = $this->conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$data['email']]);
        if($stmt->fetch()) Response::json(['error'=>'Email déjà utilisé'],409);

        // password strength check (simple)
        if(strlen($data['password']) < 8) Response::json(['error'=>'Mot de passe trop court (>=8)'],400);

        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare('INSERT INTO users (pseudo,email,password,credits) VALUES (?,?,?,20)');
        $stmt->execute([$data['pseudo'],$data['email'],$hash]);
        $id = $this->conn->lastInsertId();

        // Log initial credits transaction
        $t = $this->conn->prepare('INSERT INTO transactions (user_id, amount, reason) VALUES (?,?,?)');
        $t->execute([$id, 20, 'Initial credits']);

        $token = $this->jwt->generateToken(['id'=>$id,'email'=>$data['email'],'role'=>'user']);
        Response::json(['message'=>'Utilisateur créé','token'=>$token,'user'=>['id'=>$id,'pseudo'=>$data['pseudo'],'email'=>$data['email'],'credits'=>20]],201);
    }

    public function login($data){
        if(empty($data['email']) || empty($data['password'])) Response::json(['error'=>'Champs manquants'],400);
        $stmt = $this->conn->prepare('SELECT id,password,pseudo,role,credits,suspended FROM users WHERE email = ?');
        $stmt->execute([$data['email']]);
        $u = $stmt->fetch();
        if(!$u) Response::json(['error'=>'Email ou mot de passe invalide'],401);
        if($u['suspended']) Response::json(['error'=>'Compte suspendu'],403);
        if(!password_verify($data['password'],$u['password'])) Response::json(['error'=>'Email ou mot de passe invalide'],401);
        $token = $this->jwt->generateToken(['id'=>$u['id'],'email'=>$data['email'],'role'=>$u['role']]);
        Response::json(['message'=>'Connecté','token'=>$token,'user'=>['id'=>$u['id'],'pseudo'=>$u['pseudo'],'email'=>$data['email'],'role'=>$u['role'],'credits'=>$u['credits']]]);
    }
}
