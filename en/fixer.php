<?php
session_start();
include("db_connection.php");

// Check if user is logged in and authorized
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 2) {
    echo "<script>alert('You are not authorized to access this page. Redirecting to the main page.'); window.location.href = 'main_page.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch account_id for the user
try {
    $query = "SELECT account_id FROM user WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $account_id = $stmt->fetchColumn();
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch counts for different tasks
$x = $y = $x2 = 0;

try {
    // Count for Acknowledge
    $ack_query = "SELECT COUNT(*) FROM song_case WHERE account_id = :account_id AND status = 0";
    $ack_stmt = $pdo->prepare($ack_query);
    $ack_stmt->bindParam(":account_id", $account_id, PDO::PARAM_INT);
    $ack_stmt->execute();
    $x = $ack_stmt->fetchColumn();

    // Count for Update
    $update_query = "SELECT COUNT(*) FROM song_case WHERE fixer = :user_id AND status IN (1, 2)";
    $update_stmt = $pdo->prepare($update_query);
    $update_stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $update_stmt->execute();
    $y = $update_stmt->fetchColumn();

    // Count for Root Cause
    $cause_query = "SELECT COUNT(*) FROM song_case WHERE fixer = :user_id AND cause IS NULL";
    $cause_stmt = $pdo->prepare($cause_query);
    $cause_stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $cause_stmt->execute();
    $x2 = $cause_stmt->fetchColumn();
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixer Page</title>
    <link rel="stylesheet" href="../style/basic.css">
</head>
<body>

    <!-- Top Section with Title and Back Button -->
    <div class="top-section">
        <h2>Welcome to the Fixer Page</h2>
        <button onclick="location.href='main_page.php'">Back</button>
    </div>

    <!-- Bottom Section with Left, Middle, and Right Divs -->
    <div class="content">
        <!-- Left Section for Acknowledge Button and Count -->
        <div class="left-section">
            <button onclick="location.href='song_ack.php'">Acknowledge</button>
            <p><?= htmlspecialchars($x); ?> songs await</p>
        </div>

        <!-- Middle Section for Update Button, Root Cause Button, and Counts -->
        <div class="left-section">
            <button onclick="location.href='song_update.php'">Update</button>
            <p><?= htmlspecialchars($y); ?> songs await</p>
            <button onclick="location.href='cause.php'">Root Cause</button>
            <p><?= htmlspecialchars($x2); ?> songs await</p>
        </div>

        <!-- Right Section for Images -->
        <div class="right-section">
            <img src="../pic/chuch_logo_1.webp" alt="Chuch Logo">
            <img src="../pic/chuch_instru.webp" alt="Chuch Instrument">
        </div>
    </div>

</body>
</html>
