<?php
session_start();
include("db_connection.php"); // Include your database connection file here

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountName = trim($_POST['account']);
    $userName = trim($_POST['user']);
    $password = trim($_POST['password']);

    try {
        // Step 1: Check if account exists
        $accountCheckSql = "SELECT account_id FROM account WHERE account_name = ?";
        $accountStmt = $pdo->prepare($accountCheckSql);
        $accountStmt->execute([$accountName]);
        $account = $accountStmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            // Account does not exist
            echo "<script>alert('Account name is incorrect. Please try again.'); window.location.href = 'index.html';</script>";
            exit();
        }

        // Step 2: Check if user exists within the account
        $userCheckSql = "SELECT user_id, role, password, user_name, lang FROM user WHERE account_id = ? AND user_name = ? AND status = 1";
        $userStmt = $pdo->prepare($userCheckSql);
        $userStmt->execute([$account['account_id'], $userName]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // User does not exist
            echo "<script>alert('Username is incorrect. Please try again.'); window.location.href = 'index.html';</script>";
            exit();
        }

        // Step 3: Verify the password
        if (!password_verify($password, $user['password'])) {
            // Password is incorrect
            echo "<script>alert('Password is incorrect. Please try again.'); window.location.href = 'index.html';</script>";
            exit();
        }

        // If all checks pass, set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['user_name']; // Store the username in the session
        $_SESSION['account_id'] = $account['account_id']; // Store the account ID in the session
        $_SESSION['lang'] = $user['lang']; // Store the language ID in the session

        // Log the login event with a formatted message
        $logMessage = "[Info] User ID {$user['user_id']} logged in";
        $logSql = "INSERT INTO log (message) VALUES (?)";
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute([$logMessage]);

        // Redirect to the main page
        header("Location: main_page.php");
        exit();
    } catch (PDOException $e) {
        // Handle database errors
        echo "<script>alert('Database error: " . $e->getMessage() . "'); window.location.href = 'index.html';</script>";
        exit();
    }
}
?>