<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../libs/Response.php';

class ReviewsController {
    public function add($rideId, $jwtData, $data) {
        $pdo = Database::getConnection();
        if (!isset($data['note']) || !isset($data['target_user_id'])) Response::json(['error' => 'Champs manquants'], 400);
        $note = intval($data['note']);
        if ($note < 1 || $note > 5) Response::json(['error' => 'Note invalide'], 400);
        $ins = $pdo->prepare('INSERT INTO reviews (ride_id,reviewer_id,target_user_id,note,commentaire,status) VALUES (?,?,?,?,?,?)');
        $ins->execute([$rideId,$jwtData->sub,$data['target_user_id'],$note,$data['commentaire'] ?? null,'pending']);
        Response::json(['message' => 'Avis soumis, en attente de validation'], 201);
    }

    public function pending($jwtData) {
        if ($jwtData->role !== 'employee' && $jwtData->role !== 'admin') Response::json(['error' => 'Forbidden'], 403);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT rv.*, u.pseudo as reviewer_pseudo, t.pseudo as target_pseudo FROM reviews rv JOIN users u ON rv.reviewer_id = u.id JOIN users t ON rv.target_user_id = t.id WHERE rv.status = 'pending'");
        $stmt->execute(); Response::json($stmt->fetchAll());
    }

    public function moderate($reviewId, $jwtData, $data) {
        if ($jwtData->role !== 'employee' && $jwtData->role !== 'admin') Response::json(['error' => 'Forbidden'], 403);
        $action = $data['action'] ?? '';
        if (!in_array($action, ['approve','reject'])) Response::json(['error' => 'Action invalide'], 400);
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE reviews SET status = ?, validated_by_employee_id = ? WHERE id = ?')->execute([$status, $jwtData->sub, $reviewId]);
        Response::json(['message' => 'Review '.$status]);
    }
}
