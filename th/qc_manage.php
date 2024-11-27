<?php
session_start();
include("db_connection.php");

// Get table-specific settings
$type = $_GET['type'] ?? 'location';
$table_settings = [
    'location' => [
        'table_name' => 'location',
        'field_name' => 'loc_name',
        'id_name' => 'loc_id',
        'title' => 'ห้องจัดการสตูดิโอเพลง',
        'image' => '../pic/pao_mas1.webp'
    ],
    'cause' => [
        'table_name' => 'cause',
        'field_name' => 'cause',
        'id_name' => 'id',
        'title' => 'ห้องจัดการสาเหตุ',
        'image' => '../pic/pao_mas1.webp'
    ]
];

if (!array_key_exists($type, $table_settings)) {
    echo "<script>alert('Invalid type.'); window.location.href = 'main_page.php';</script>";
    exit();
}

$settings = $table_settings[$type];
$table_name = $settings['table_name'];
$field_name = $settings['field_name'];
$id_name = $settings['id_name'];
$title = $settings['title'];
$image = $settings['image'];

// Check if user is logged in and authorized
if (!isset($_SESSION['user_id']) || $_SESSION['role'] < 3) {
    echo "<script>alert('Unauthorized access. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
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

// Fetch rows from the relevant table
try {
    $query = "SELECT $id_name as id, $field_name AS name FROM $table_name WHERE account_id = :account_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":account_id", $account_id, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function confirmEdit(id, oldName) {
            const newName = document.getElementById(`field_${id}`).value.trim();
            if (!newName) {
                alert("Name cannot be blank.");
                return;
            }
            if (newName === oldName) {
                alert("No changes detected.");
                return;
            }
            if (confirm(`Do you want to rename "${oldName}" to "${newName}"?`)) {
                fetch('update_common.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'edit', id, name: newName, table: '<?= $table_name ?>', field: '<?= $field_name ?>' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Updated successfully.");
                        location.reload();
                    } else {
                        alert("Error: " + data.error);
                    }
                });
            }
        }

        function confirmDelete(id, name) {
            if (confirm(`Do you want to delete "${name}"?`)) {
                fetch('update_common.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id, table: '<?= $table_name ?>' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Deleted successfully.");
                        location.reload();
                    } else {
                        alert("Error: " + data.error);
                    }
                });
            }
        }

        function confirmAdd() {
            const newName = document.getElementById('new_name').value.trim();
            if (!newName) {
                alert("Name cannot be blank.");
                return;
            }
            if (confirm(`Do you want to add "${newName}"?`)) {
                fetch('update_common.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'add', name: newName, table: '<?= $table_name ?>', field: '<?= $field_name ?>' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Added successfully.");
                        location.reload();
                    } else {
                        alert("Error: " + data.error);
                    }
                });
            }
        }
    </script>
</head>
<body>
    <div class="top-section">
        <h2><?= htmlspecialchars($title) ?></h2>
        <button onclick="location.href='qc.html'">ย้อนกลับ</button>
    </div>

    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <table>
                <tr>
                    <th>ชื่อ</th>
                    <th>การจัดการ</th>
                </tr>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><input type="text" id="field_<?= $row['id'] ?>" value="<?= htmlspecialchars($row['name']) ?>"></td>
                        <td>
                            <button class="small-button" onclick="confirmEdit(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>')">แก้ไข</button>
                            <button class="small-button" onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>')">ลบ</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td><input type="text" id="new_name" placeholder="New Name"></td>
                    <td><button class="small-button" onclick="confirmAdd()">เพิ่ม</button></td>
                </tr>
            </table>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <img src="<?= htmlspecialchars($image) ?>" alt="Image">
        </div>
    </div>
</body>
</html>
