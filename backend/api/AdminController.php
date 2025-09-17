<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../libs/Response.php';

class AdminController {
    public function createEmployee($jwtData, $data) {
        if ($jwtData->role !== 'admin') Response::json(['error' => 'Forbidden'], 403);
        if (empty($data['pseudo']) || empty($data['email']) || empty($data['password'])) Response::json(['error' => 'Champs manquants'], 400);
        $pdo = Database::getConnection();
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (pseudo,email,password,role,credits) VALUES (?,?,?,?,0)');
        $stmt->execute([$data['pseudo'],$data['email'],$hash,'employee']);
        Response::json(['message' => 'Employé créé', 'id' => $pdo->lastInsertId()], 201);
    }

    public function suspend($jwtData, $userId) {
        if ($jwtData->role !== 'admin') Response::json(['error' => 'Forbidden'], 403);
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE users SET suspended = 1 WHERE id = ?')->execute([$userId]);
        Response::json(['message' => 'Utilisateur suspendu']);
    }

    public function unsuspend($jwtData, $userId) {
        if ($jwtData->role !== 'admin') Response::json(['error' => 'Forbidden'], 403);
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE users SET suspended = 0 WHERE id = ?')->execute([$userId]);
        Response::json(['message' => 'Utilisateur réactivé']);
    }

    public function stats($jwtData, $day = null) {
        if ($jwtData->role !== 'admin') Response::json(['error' => 'Forbidden'], 403);
        $pdo = Database::getConnection();
        if ($day) {
            $stmt = $pdo->prepare('SELECT * FROM stats WHERE day = ?');
            $stmt->execute([$day]); $s = $stmt->fetch();
            Response::json($s ?: ['day' => $day, 'total_rides' => 0, 'platform_credits' => 0]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM stats ORDER BY day DESC LIMIT 30');
            $stmt->execute(); Response::json($stmt->fetchAll());
        }
    }
}
