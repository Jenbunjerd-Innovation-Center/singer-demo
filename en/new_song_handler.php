<?php
session_start();
include("db_connection.php");

header("Content-Type: application/json");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in.']);
    exit();
}

// Validate and sanitize inputs
$title = trim($_POST['title'] ?? '');
$scene = trim($_POST['scene'] ?? '');
$detail = trim($_POST['detail'] ?? '');
$mask = isset($_POST['mask']) ? (int)$_POST['mask'] : 0;

if (empty($title) || empty($scene) || empty($detail)) {
    echo json_encode(['success' => false, 'error' => 'Required fields are missing.']);
    exit();
}

// Get user_id and account_id
$user_id = $_SESSION['user_id'];

// Fetch account_id if not already set in session
if (empty($_SESSION['account_id'])) {
    try {
        $query = "SELECT account_id FROM user WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $account_id = $stmt->fetchColumn();

        if (!$account_id) {
            echo json_encode(['success' => false, 'error' => 'Account ID not found.']);
            exit();
        }
        $_SESSION['account_id'] = $account_id;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
} else {
    $account_id = $_SESSION['account_id'];
}

try {
    if ($mask === 1) {
        // Insert without user_id when mask is checked
        $stmt = $pdo->prepare("
            INSERT INTO song_case (account_id, case_title, place, detail, created_at, status, score)
            VALUES (:account_id, :title, :scene, :detail, NOW(), 0, 0)
        ");
        $stmt->execute([
            ':account_id' => $account_id,
            ':title' => $title,
            ':scene' => $scene,
            ':detail' => $detail,
        ]);
    } else {
        // Insert with user_id when mask is unchecked
        $stmt = $pdo->prepare("
            INSERT INTO song_case (account_id, user_id, case_title, place, detail, created_at, status, score)
            VALUES (:account_id, :user_id, :title, :scene, :detail, NOW(), 0, 0)
        ");
        $stmt->execute([
            ':account_id' => $account_id,
            ':user_id' => $user_id,
            ':title' => $title,
            ':scene' => $scene,
            ':detail' => $detail,
        ]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
