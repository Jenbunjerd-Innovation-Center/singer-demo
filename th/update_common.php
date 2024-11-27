<?php
session_start();
include("db_connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $action = $data['action'] ?? null;
    $id = $data['id'] ?? null;
    $name = trim($data['name'] ?? '');
    $table = $data['table'] ?? null;
    $field = $data['field'] ?? null;

    if (!$action || (!$name && $action !== 'delete') || !$table || !$field) {
        echo json_encode(['success' => false, 'error' => 'Invalid input.']);
        exit();
    }

    try {
        if ($action === 'edit') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $field = :name AND id != :id");
            $stmt->execute([':name' => $name, ':id' => $id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'error' => 'Name must be unique.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE $table SET $field = :name WHERE id = :id");
            $stmt->execute([':name' => $name, ':id' => $id]);
            echo json_encode(['success' => true]);
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } elseif ($action === 'add') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $field = :name");
            $stmt->execute([':name' => $name]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'error' => 'Name must be unique.']);
                exit();
            }

            $stmt = $pdo->prepare("INSERT INTO $table ($field, account_id) VALUES (:name, :account_id)");
            $stmt->execute([':name' => $name, ':account_id' => $_SESSION['account_id']]);
            echo json_encode(['success' => true]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
