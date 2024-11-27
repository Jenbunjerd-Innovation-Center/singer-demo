<?php
session_start();
include("db_connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $action = $data['action'] ?? null;
    $loc_id = $data['loc_id'] ?? null;
    $loc_name = trim($data['loc_name'] ?? '');

    if (!$action || (!$loc_name && $action !== 'delete')) {
        echo json_encode(['success' => false, 'error' => 'Invalid input.']);
        exit();
    }

    try {
        if ($action === 'edit') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM location WHERE loc_name = :loc_name AND loc_id != :loc_id");
            $stmt->execute([':loc_name' => $loc_name, ':loc_id' => $loc_id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'error' => 'Location name must be unique.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE location SET loc_name = :loc_name WHERE loc_id = :loc_id");
            $stmt->execute([':loc_name' => $loc_name, ':loc_id' => $loc_id]);
            file_put_contents('../log/location_log.txt', "Edited: $loc_id to $loc_name\n", FILE_APPEND);
            echo json_encode(['success' => true]);
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM location WHERE loc_id = :loc_id");
            $stmt->execute([':loc_id' => $loc_id]);
            file_put_contents('../log/location_log.txt', "Deleted: $loc_id\n", FILE_APPEND);
            echo json_encode(['success' => true]);
        } elseif ($action === 'add') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM location WHERE loc_name = :loc_name");
            $stmt->execute([':loc_name' => $loc_name]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'error' => 'Location name must be unique.']);
                exit();
            }

            $stmt = $pdo->prepare("INSERT INTO location (loc_name, account_id) VALUES (:loc_name, :account_id)");
            $stmt->execute([':loc_name' => $loc_name, ':account_id' => $_SESSION['account_id']]);
            file_put_contents('../log/location_log.txt', "Added: $loc_name\n", FILE_APPEND);
            echo json_encode(['success' => true]);
        }
    } catch (PDOException $e) {
        // Log detailed error for debugging
        file_put_contents('../log/location_error_log.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
