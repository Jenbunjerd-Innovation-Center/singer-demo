<?php
session_start();
include("db_connection.php");

// Read JSON input
$data = json_decode(file_get_contents('php://input'), true);
$caseId = $data['case_id'];

// Fetch case details from song_case
try {
    $case_query = "
        SELECT c.case_title, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               c.place, 
               c.created_at, 
               c.detail, 
               f.user_name AS fixer_name, 
               c.acc_at, 
               c.close_at
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN user f ON c.fixer = f.user_id
        WHERE c.case_id = ?";
    $stmt = $pdo->prepare($case_query);
    $stmt->execute([$caseId]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$case) {
        echo json_encode(['success' => false, 'message' => 'Case not found.']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// Fetch case updates from case_update
$updates = [];
try {
    $update_query = "SELECT update_no, DATE(timestamp) AS date, update_detail AS detail FROM case_update WHERE case_id = ? ORDER BY update_no ASC";
    $stmt = $pdo->prepare($update_query);
    $stmt->execute([$caseId]);
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch updates.']);
    exit();
}

// Return case details and updates as JSON
echo json_encode([
    'success' => true,
    'case_title' => $case['case_title'],
    'user_name' => $case['user_name'],
    'created_at' => $case['created_at'],
    'place' => $case['place'],
    'fixer_name' => $case['fixer_name'],
    'acc_at' => $case['acc_at'],
    'close_at' => $case['close_at'],
    'detail' => $case['detail'],
    'updates' => $updates
]);
?>
