<?php
session_start();
include("db_connection.php");

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$case_id = $data['case_id'];
$update_no = $data['update_no'];
$update_detail = $data['update_detail'];
$status = $data['status'];

try {
    // Insert into case_update
    $insert_update = "INSERT INTO case_update (case_id, update_no, timestamp, update_detail) VALUES (:case_id, :update_no, NOW(), :update_detail)";
    $stmt = $pdo->prepare($insert_update);
    $stmt->execute([':case_id' => $case_id, ':update_no' => $update_no, ':update_detail' => $update_detail]);

    // Update song_case
    $update_song = "UPDATE song_case SET status = :status, close_at = (CASE WHEN :status = 3 THEN NOW() ELSE close_at END) WHERE case_id = :case_id";
    $stmt = $pdo->prepare($update_song);
    $stmt->execute([':status' => $status, ':case_id' => $case_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
