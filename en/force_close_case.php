<?php
session_start();
include("db_connection.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

// Read the JSON input
$data = json_decode(file_get_contents('php://input'), true);
$caseId = $data['case_id'];
$reason = $data['reason'];
$forceId = $data['force_id']; // ID of the user forcing the close

// Fetch the user name of the person performing the force close
try {
    $user_query = "SELECT user_name FROM user WHERE user_id = ?";
    $user_stmt = $pdo->prepare($user_query);
    $user_stmt->execute([$forceId]);
    $user_name = $user_stmt->fetchColumn();
    if (!$user_name) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// Update the case status to 'Force Close' (status = 5), set force_reason, force_id, and add current timestamp to close_at
try {
    $sql = "UPDATE song_case SET status = 5, force_reason = ?, force_id = ?, close_at = NOW() WHERE case_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$reason, $forceId, $caseId]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to force close the case.']);
    exit();
}

// Fetch the maximum update_no for the specified case_id
try {
    $update_no_query = "SELECT COALESCE(MAX(update_no), 0) FROM case_update WHERE case_id = ?";
    $update_no_stmt = $pdo->prepare($update_no_query);
    $update_no_stmt->execute([$caseId]);
    $max_update_no = $update_no_stmt->fetchColumn();
    $next_update_no = $max_update_no + 1;
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// Insert a new record into case_update
$update_detail = "force closed by {$user_name} because {$reason}";
try {
    $insert_update_query = "INSERT INTO case_update (case_id, update_no, timestamp, update_detail) VALUES (?, ?, NOW(), ?)";
    $insert_update_stmt = $pdo->prepare($insert_update_query);
    $insert_update_stmt->execute([$caseId, $next_update_no, $update_detail]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to insert update into case_update.']);
}
?>
