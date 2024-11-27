<?php
session_start();
include("db_connection.php");

header("Content-Type: application/json");

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in and authorized
if (!isset($_SESSION['user_id']) || $_SESSION['role'] < 2) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit();
}

// Read JSON input data
$data = json_decode(file_get_contents('php://input'), true);
$case_id = $data['case_id'] ?? null;

if (!$case_id) {
    echo json_encode(['success' => false, 'error' => 'Case ID is required.']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch the fixer's name for logging purposes
    $fixer_query = "SELECT user_name FROM user WHERE user_id = :user_id";
    $fixer_stmt = $pdo->prepare($fixer_query);
    $fixer_stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $fixer_stmt->execute();
    $fixer_name = $fixer_stmt->fetchColumn();

    if (!$fixer_name) {
        echo json_encode(['success' => false, 'error' => 'Fixer not found.']);
        exit();
    }

    // Update song_case table with acknowledgment details
    $update_query = "
        UPDATE song_case
        SET fixer = :user_id, acc_at = NOW(), status = 1
        WHERE case_id = :case_id AND status = 0";
    $update_stmt = $pdo->prepare($update_query);
    $update_stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $update_stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $result = $update_stmt->execute();

    if ($result) {
        // Fetch the max update_no for the case
        $max_update_query = "SELECT COALESCE(MAX(update_no), 0) + 1 AS next_update_no FROM case_update WHERE case_id = :case_id";
        $max_update_stmt = $pdo->prepare($max_update_query);
        $max_update_stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
        $max_update_stmt->execute();
        $next_update_no = $max_update_stmt->fetchColumn();

        // Insert the acknowledgment into the case_update table
        $insert_update_query = "
            INSERT INTO case_update (case_id, update_no, timestamp, update_detail)
            VALUES (:case_id, :update_no, NOW(), :update_detail)";
        $insert_update_stmt = $pdo->prepare($insert_update_query);
        $insert_update_stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
        $insert_update_stmt->bindParam(":update_no", $next_update_no, PDO::PARAM_INT);
        $insert_update_stmt->bindParam(":update_detail", $update_detail, PDO::PARAM_STR);

        $update_detail = "Acknowledge by {$fixer_name}";
        $insert_update_stmt->execute();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update the record.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
