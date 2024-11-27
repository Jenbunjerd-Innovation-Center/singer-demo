<?php
session_start();
include("db_connection.php"); // Include your database connection file here

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

// Read the JSON input
$data = json_decode(file_get_contents('php://input'), true);
$caseId = $data['case_id'];

// Update the case status to 'Cancelled' (status = 4) and set close_at to the current timestamp
$sql = "UPDATE song_case SET status = 4, close_at = NOW() WHERE case_id = ? AND status IN (0, 1, 2)";
$stmt = $pdo->prepare($sql);
$result = $stmt->execute([$caseId]);

// Respond with success or failure
if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel case.']);
}
?>

