<?php
 
session_start();

 
 
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    global $pdo;
    $user_id = $_SESSION['user_id'];

     
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);

         
        http_response_code(200);
        echo json_encode(["status" => "success"]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
     
    http_response_code(403);
}
?>