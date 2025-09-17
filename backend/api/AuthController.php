<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../libs/Response.php';
require_once __DIR__ . '/../libs/JwtHandler.php';

class AuthController {
    public function register($data) {
        $pdo = Database::getConnection();
        if (empty($data['pseudo']) || empty($data['email']) || empty($data['password'])) {
            Response::json(['error' => 'Champs manquants'], 400);
        }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) Response::json(['error' => 'Email déjà utilisé'], 409);

        if (strlen($data['password']) < 8) Response::json(['error' => 'Mot de passe trop court (>=8)'], 400);

        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $ins = $pdo->prepare('INSERT INTO users (pseudo,email,password,role,credits) VALUES (?,?,?,?,20)');
        $ins->execute([$data['pseudo'], $data['email'], $hash, 'user']);
        $id = $pdo->lastInsertId();

        $tx = $pdo->prepare('INSERT INTO transactions (user_id,amount,reason) VALUES (?,?,?)');
        $tx->execute([$id, 20, 'Initial credits']);

        $jwt = new JwtHandler();
        $token = $jwt->generate(['sub' => $id, 'email' => $data['email'], 'role' => 'user']);

        Response::json(['message' => 'Utilisateur créé', 'token' => $token, 'user' => ['id' => $id, 'pseudo' => $data['pseudo'], 'email' => $data['email'], 'credits' => 20]], 201);
    }

    public function login($data) {
        $pdo = Database::getConnection();
        if (empty($data['email']) || empty($data['password'])) Response::json(['error' => 'Champs manquants'], 400);
        $stmt = $pdo->prepare('SELECT id,pseudo,password,role,credits,suspended FROM users WHERE email = ?');
        $stmt->execute([$data['email']]); $user = $stmt->fetch();
        if (!$user) Response::json(['error' => 'Identifiants invalides'], 401);
        if ($user['suspended']) Response::json(['error' => 'Compte suspendu'], 403);
        if (!password_verify($data['password'], $user['password'])) Response::json(['error' => 'Identifiants invalides'], 401);
        $jwt = new JwtHandler();
        $token = $jwt->generate(['sub' => $user['id'], 'email' => $data['email'], 'role' => $user['role']]);
        Response::json(['message' => 'Connecté', 'token' => $token, 'user' => ['id' => $user['id'], 'pseudo' => $user['pseudo'], 'email' => $data['email'], 'role' => $user['role'], 'credits' => $user['credits']]], 200);
    }
}
