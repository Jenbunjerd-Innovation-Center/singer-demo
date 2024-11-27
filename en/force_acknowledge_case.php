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
$fixerId = $data['fixer_id']; // ID of the user being assigned as the fixer

// Update the case status to 'Acknowledge' (status = 1), set the fixer, and add the current timestamp to acc_at
$sql = "UPDATE song_case SET status = 1, fixer = ?, acc_at = NOW() WHERE case_id = ?";
$stmt = $pdo->prepare($sql);
$result = $stmt->execute([$fixerId, $caseId]);

// Respond with success or failure
if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to acknowledge the case.']);
}
?>
