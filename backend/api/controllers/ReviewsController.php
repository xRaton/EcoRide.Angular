<?php
// api/controllers/ReviewsController.php
require_once __DIR__.'/../libs/Response.php';
require_once __DIR__.'/../libs/Database.php';

class ReviewsController {
    private $conn;
    public function __construct(){ $db = new Database(); $this->conn = $db->getConnection(); }

    public function addReview($rideId, $reviewerId, $data){
        // data: note (1-5), commentaire, target_user_id (driver)
        if(empty($data['note']) || empty($data['target_user_id'])) Response::json(['error'=>'Champs manquants'],400);
        $note = intval($data['note']);
        if($note <1 || $note >5) Response::json(['error'=>'Note invalide'],400);
        $stmt = $this->conn->prepare('INSERT INTO reviews (ride_id,reviewer_id,target_user_id,note,commentaire,status) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$rideId,$reviewerId,$data['target_user_id'],$note,$data['commentaire'] ?? null,'pending']);
        Response::json(['message'=>'Avis soumis, en attente de validation'],201);
    }

    // For employees: list pending reviews
    public function pendingReviews(){
        $stmt = $this->conn->prepare('SELECT rv.*, u.pseudo as reviewer_pseudo, t.pseudo as target_pseudo FROM reviews rv JOIN users u ON rv.reviewer_id = u.id JOIN users t ON rv.target_user_id = t.id WHERE rv.status = \'pending\'');
        $stmt->execute();
        Response::json($stmt->fetchAll());
    }

    public function moderate($reviewId, $employeeId, $action){
        // action: approve | reject
        if(!in_array($action,['approve','reject'])) Response::json(['error'=>'Action invalide'],400);
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $this->conn->prepare('UPDATE reviews SET status = ?, validated_by_employee_id = ? WHERE id = ?');
        $stmt->execute([$status, $employeeId, $reviewId]);
        Response::json(['message'=>'Review '.$status]);
    }
}
