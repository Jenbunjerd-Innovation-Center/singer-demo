<?php
session_start();
include("db_connection.php");

$data = json_decode(file_get_contents('php://input'), true);
$caseId = $data['case_id'];
$userName = $data['user_name'];

// Update song_case table to set close_at to NULL and status to 2
try {
    $update_case_query = "UPDATE song_case SET close_at = NULL, status = 2 WHERE case_id = ?";
    $update_case_stmt = $pdo->prepare($update_case_query);
    $update_case_stmt->execute([$caseId]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to update song_case.']);
    exit();
}

// Fetch the max update_no for the given case_id
try {
    $max_update_no_query = "SELECT COALESCE(MAX(update_no), 0) FROM case_update WHERE case_id = ?";
    $max_update_no_stmt = $pdo->prepare($max_update_no_query);
    $max_update_no_stmt->execute([$caseId]);
    $next_update_no = $max_update_no_stmt->fetchColumn() + 1;
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch max update number.']);
    exit();
}

// Insert a new record into case_update to log the recovery
$update_detail = "Recover by {$userName}";
try {
    $insert_update_query = "INSERT INTO case_update (case_id, update_no, timestamp, update_detail) VALUES (?, ?, NOW(), ?)";
    $insert_update_stmt = $pdo->prepare($insert_update_query);
    $insert_update_stmt->execute([$caseId, $next_update_no, $update_detail]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to insert update into case_update.']);
}
?>
