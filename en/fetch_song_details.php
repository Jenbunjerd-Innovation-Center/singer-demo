<?php
session_start();
include("db_connection.php");

header("Content-Type: application/json");

$case_id = $_GET['case_id'] ?? null;

if (!$case_id) {
    echo json_encode(['success' => false, 'error' => 'Case ID is required.']);
    exit();
}

try {
    // Fetch full details of the song case
    $query = "
        SELECT c.case_id, 
               COALESCE(u.user_name, 'The mask singer') AS user_name, 
               c.case_title, 
               c.place, 
               DATE(c.created_at) AS created_date,
               c.detail
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.case_id = :case_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $stmt->execute();
    $song = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($song) {
        echo json_encode(['success' => true] + $song);
    } else {
        echo json_encode(['success' => false, 'error' => 'Song case not found.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
