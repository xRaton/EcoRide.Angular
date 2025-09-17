<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../libs/Response.php';

class VehiclesController {
    public function get($id) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM vehicles WHERE id = ?');
        $stmt->execute([$id]); $v = $stmt->fetch();
        if (!$v) Response::json(['error' => 'Véhicule introuvable'], 404);
        Response::json($v);
    }
}
