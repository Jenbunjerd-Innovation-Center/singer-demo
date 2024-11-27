<?php
header("Content-Type: application/json");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("db_connection.php");

try {
    // Parse the input data
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data || !isset($data['case_id']) || !isset($data['cause'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid input.']);
        exit();
    }

    $case_id = $data['case_id'];
    $cause = $data['cause'];

    // Update the cause in the song_case table
    $query = "UPDATE song_case SET cause = :cause WHERE case_id = :case_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":cause", $cause, PDO::PARAM_STR);
    $stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update the database.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
}
