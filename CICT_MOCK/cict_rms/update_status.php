<?php
// update_status.php
require 'db_connect.php';
session_start();

header('Content-Type: application/json');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_status'])) {
    $userId = (int)$_POST['user_id'];
    $newStatus = $_POST['new_status'];

    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $userId);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['new_status'] = $newStatus;
        $response['status_badge_class'] = $newStatus === 'active' ? 'bg-success' : 'bg-danger';
    } else {
        $response['error'] = $stmt->error;
    }
    
    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>