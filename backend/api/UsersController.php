<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../libs/Response.php';

class UsersController {
    public function me($jwtData) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id,pseudo,email,role,credits,created_at,suspended FROM users WHERE id = ?');
        $stmt->execute([$jwtData->sub]); $u = $stmt->fetch();
        if (!$u) Response::json(['error' => 'Utilisateur introuvable'], 404);
        Response::json($u);
    }

    public function addVehicle($jwtData, $data) {
        $pdo = Database::getConnection();
        if (empty($data['immatriculation'])) Response::json(['error' => 'immatriculation manquante'], 400);
        $ins = $pdo->prepare('INSERT INTO vehicles (user_id,immatriculation,marque,modele,couleur,energie,date_premiere_immat,seats) VALUES (?,?,?,?,?,?,?,?)');
        $ins->execute([$jwtData->sub, $data['immatriculation'], $data['marque'] ?? null, $data['modele'] ?? null, $data['couleur'] ?? null, $data['energie'] ?? 'essence', $data['date_premiere_immat'] ?? null, $data['seats'] ?? 4]);
        Response::json(['message' => 'Véhicule ajouté', 'vehicle_id' => $pdo->lastInsertId()], 201);
    }

    public function listVehicles($jwtData) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM vehicles WHERE user_id = ?');
        $stmt->execute([$jwtData->sub]);
        Response::json($stmt->fetchAll());
    }
}
