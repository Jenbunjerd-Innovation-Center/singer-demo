<?php
session_start();
include("db_connection.php");

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

// Fetch locations
try {
    $location_query = "SELECT loc_id, loc_name FROM location WHERE account_id = :account_id";
    $stmt = $pdo->prepare($location_query);
    $stmt->bindParam(":account_id", $account_id, PDO::PARAM_INT);
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>การจัดการสถานที่ของผู้คุมเสียงดนตรี</title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function confirmEdit(locId, oldLocName) {
            const newLocName = document.getElementById(`loc_${locId}`).value.trim();
            if (!newLocName) {
                alert("Location name cannot be blank.");
                return;
            }
            if (newLocName === oldLocName) {
                alert("No changes detected.");
                return;
            }
            if (confirm(`Do you want to rename "${oldLocName}" to "${newLocName}"?`)) {
                fetch('update_location.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'edit', 
                        loc_id: locId, 
                        loc_name: newLocName // Ensure this matches the backend's expectations
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Server Response:", data); // Debugging
                    if (data.success) {
                        alert("Location updated successfully.");
                        location.reload(); // Reload page after success
                    } else {
                        alert("Error: " + data.error);
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error("Fetch error:", error); // Log error details
                    alert("An unexpected error occurred. Please try again.");
                });
            }
        }


        function confirmDelete(locId, locName) {
            if (confirm(`Do you want to delete "${locName}"?`)) {
                fetch('update_location.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', loc_id: locId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Location deleted successfully.");
                        location.reload();
                    } else {
                        alert("Error: " + data.error);
                        location.reload();
                    }
                });
            }
        }

        function confirmAdd() {
            const newLocName = document.getElementById('new_loc_name').value.trim();
            if (!newLocName) {
                alert("Location name cannot be blank.");
                return;
            }
            if (confirm(`Do you want to add "${newLocName}"?`)) {
                fetch('update_location.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'add', loc_name: newLocName })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Location added successfully.");
                        location.reload();
                    } else {
                        alert("Error: " + data.error);
                        location.reload();
                    }
                });
            }
        }
    </script>
</head>
<body>
    <div class="top-section">
        <h2>การจัดการสถานที่</h2>
        <button onclick="location.href='qc.html'">ย้อนกลับ</button>
    </div>

    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <table>
                <tr>
                    <th>สถานที่</th>
                    <th>การจัดการสถานที่</th>
                </tr>
                <?php foreach ($locations as $location): ?>
                    <tr>
                        <td><input type="text" id="loc_<?= $location['loc_id'] ?>" value="<?= htmlspecialchars($location['loc_name']) ?>"></td>
                        <td>
                            <button class="small-button" onclick="confirmEdit(<?= $location['loc_id'] ?>, '<?= htmlspecialchars($location['loc_name']) ?>')">แก้ไข</button>
                            <button class="small-button" onclick="confirmDelete(<?= $location['loc_id'] ?>, '<?= htmlspecialchars($location['loc_name']) ?>')">ลบ</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td><input type="text" id="new_loc_name" placeholder="New Location"></td>
                    <td><button class="small-button" onclick="confirmAdd()">เพิ่ม</button></td>
                </tr>
            </table>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <img src="../pic/pao_mas1.webp" alt="Pao Image">
        </div>
    </div>
</body>
</html>
