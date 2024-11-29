<?php
session_start();
include("db_connection.php"); // Include your database connection file here

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$viewType = $_GET['viewType'];

try {
    // SQL query based on view type
    switch ($viewType) {
        case 'my_current':
            // Show cases with status 0, 1, or 2, assigned to the current user
            $sql = "SELECT c.case_id, 
                           COALESCE(uc.user_name, 'The Mask Singer') AS user_name, 
                           c.case_title, 
                           c.created_at, 
                           u.user_name AS fixer_name, 
                           c.acc_at, 
                           c.status,
                           COALESCE(sn.name, 'Unknown') AS status_name
                    FROM song_case c
                    LEFT JOIN user u ON c.fixer = u.user_id
                    LEFT JOIN user uc ON c.user_id = uc.user_id
                    LEFT JOIN status sn ON c.status = sn.id
                    WHERE c.user_id = :userId AND c.status IN (0, 1, 2)
                    ORDER BY c.status ASC, c.created_at ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':userId' => $userId]);
            break;

        case 'my_all':
            // Show all cases assigned to the current user
            $sql = "SELECT c.case_id, 
                           COALESCE(uc.user_name, 'The Mask Singer') AS user_name, 
                           c.case_title, 
                           c.created_at, 
                           u.user_name AS fixer_name, 
                           c.acc_at, 
                           c.status,
                           COALESCE(sn.name, 'Unknown') AS status_name
                    FROM song_case c
                    LEFT JOIN user u ON c.fixer = u.user_id
                    LEFT JOIN user uc ON c.user_id = uc.user_id
                    LEFT JOIN status sn ON c.status = sn.id
                    WHERE c.user_id = :userId
                    ORDER BY c.status ASC, c.created_at ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':userId' => $userId]);
            break;

        case 'mask_current':
            // Show cases with status 0, 1, or 2, with no assigned user (user_id is NULL)
            $sql = "SELECT c.case_id, 
                           'The Mask Singer' AS user_name, 
                           c.case_title, 
                           c.created_at, 
                           u.user_name AS fixer_name, 
                           c.acc_at, 
                           c.status,
                           COALESCE(sn.name, 'Unknown') AS status_name
                    FROM song_case c
                    LEFT JOIN user u ON c.fixer = u.user_id
                    LEFT JOIN status sn ON c.status = sn.id
                    WHERE c.user_id IS NULL AND c.status IN (0, 1, 2)
                    ORDER BY c.status ASC, c.created_at ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            break;

        case 'mask_all':
            // Show all cases with no assigned user (user_id is NULL)
            $sql = "SELECT c.case_id, 
                           'The Mask Singer' AS user_name, 
                           c.case_title, 
                           c.created_at, 
                           u.user_name AS fixer_name, 
                           c.acc_at, 
                           c.status,
                           COALESCE(sn.name, 'Unknown') AS status_name
                    FROM song_case c
                    LEFT JOIN user u ON c.fixer = u.user_id
                    LEFT JOIN status sn ON c.status = sn.id
                    WHERE c.user_id IS NULL
                    ORDER BY c.status ASC, c.created_at ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            break;

        default:
            echo json_encode([]);
            exit();
    }

    // Fetch and output results as JSON
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cases);

} catch (PDOException $e) {
    // Handle errors
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit();
}
