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

// Fetch language code
$lang_code = 'eng'; // Default language
try {
    $lang_query = "SELECT code FROM language WHERE id = (SELECT lang FROM user WHERE user_id = :user_id)";
    $lang_stmt = $pdo->prepare($lang_query);
    $lang_stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $lang_stmt->execute();
    $lang_code = $lang_stmt->fetchColumn() ?? $lang_code;
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

// Load language JSON file
$jsonPath = "../lang/{$lang_code}/fixer.json";
$lang = json_decode(file_get_contents($jsonPath), true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lang['title']); ?></title>
    <link rel="stylesheet" href="../style/basic.css">
</head>
<body>

    <!-- Top Section with Title and Back Button -->
    <div class="top-section">
        <h2><?= htmlspecialchars($lang['welcome_message']); ?></h2>
        <button onclick="location.href='main_page.php'"><?= htmlspecialchars($lang['back_button']); ?></button>
    </div>

    <!-- Bottom Section with Left, Middle, and Right Divs -->
    <div class="content">
        <!-- Left Section for Acknowledge Button and Count -->
        <div class="left-section">
            <button onclick="location.href='song_ack.php'"><?= htmlspecialchars($lang['acknowledge_button']); ?></button>
            <p><?= htmlspecialchars($x); ?> <?= htmlspecialchars($lang['songs_await']); ?></p>
        </div>

        <!-- Middle Section for Update Button, Root Cause Button, and Counts -->
        <div class="left-section">
            <button onclick="location.href='song_update.php'"><?= htmlspecialchars($lang['update_button']); ?></button>
            <p><?= htmlspecialchars($y); ?> <?= htmlspecialchars($lang['songs_await']); ?></p>
            <button onclick="location.href='cause.php'"><?= htmlspecialchars($lang['root_cause_button']); ?></button>
            <p><?= htmlspecialchars($x2); ?> <?= htmlspecialchars($lang['songs_await']); ?></p>
        </div>

        <!-- Right Section for Images -->
        <div class="right-section">
            <img src="../pic/chuch_logo_1.webp" alt="<?= htmlspecialchars($lang['image_alt_logo']); ?>">
            <img src="../pic/chuch_instru.webp" alt="<?= htmlspecialchars($lang['image_alt_instrument']); ?>">
        </div>
    </div>

</body>
</html>
