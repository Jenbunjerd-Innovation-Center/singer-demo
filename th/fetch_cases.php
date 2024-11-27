<?php
session_start();
include("db_connection.php"); // Include your database connection file here

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$viewType = $_GET['viewType'];

// SQL query based on view type and user role
switch ($viewType) {
    case 'current':
        // Show cases with status 0, 1, or 2, assigned to the current user
        $sql = "SELECT c.case_id, 
                       COALESCE(uc.user_name, 'The Mask Singer') AS user_name, 
                       c.case_title, 
                       c.created_at, 
                       u.user_name AS fixer_name, 
                       c.acc_at, 
                       c.status
                FROM song_case c
                LEFT JOIN user u ON c.fixer = u.user_id
                LEFT JOIN user uc ON c.user_id = uc.user_id
                WHERE c.user_id = ? AND c.status IN (0, 1, 2)
                ORDER BY c.status ASC, c.created_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        break;
    
    case 'all_case':
        // Show all cases assigned to the current user
        $sql = "SELECT c.case_id, 
                       COALESCE(uc.user_name, 'The Mask Singer') AS user_name, 
                       c.case_title, 
                       c.created_at, 
                       u.user_name AS fixer_name, 
                       c.acc_at, 
                       c.status
                FROM song_case c
                LEFT JOIN user u ON c.fixer = u.user_id
                LEFT JOIN user uc ON c.user_id = uc.user_id
                WHERE c.user_id = ?
                ORDER BY c.status ASC, c.created_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        break;

    case 'all_user_case':
        // Only admins can see all user cases without filtering
        if ($userRole == 4) {
            $sql = "SELECT c.case_id, 
                           COALESCE(uc.user_name, 'The Mask Singer') AS user_name, 
                           c.case_title, 
                           c.created_at, 
                           u.user_name AS fixer_name, 
                           c.acc_at, 
                           c.status
                    FROM song_case c
                    LEFT JOIN user u ON c.fixer = u.user_id
                    LEFT JOIN user uc ON c.user_id = uc.user_id
                    ORDER BY c.status ASC, c.created_at ASC";
            $stmt = $pdo->query($sql);
        } else {
            echo json_encode([]);
            exit();
        }
        break;
    
    default:
        echo json_encode([]);
        exit();
}

// Fetch and output results as JSON
$cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cases);
?>
