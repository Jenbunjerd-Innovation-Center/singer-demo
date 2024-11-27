<?php
session_start();
include("db_connection.php");

// Check if user is logged in and authorized
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 2) {
    echo "<script>alert('You are not authorized to access this page. Redirecting to the Song Update page.'); window.location.href = 'song_update.php';</script>";
    exit();
}

// Use POST method to hide case_id in URL
$case_id = $_POST['case_id'] ?? null;
if (!$case_id) {
    echo "<script>alert('No case selected. Redirecting to the Song Update page.'); window.location.href = 'song_update.php';</script>";
    exit();
}

// Fetch song case details and check if 'cause' is set
$song = [];
$enableCloseCase = false;
try {
    $song_query = "
        SELECT c.case_title, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               c.place, 
               DATE(c.created_at) AS created_date, 
               c.detail, 
               c.cause
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.case_id = :case_id";
    $stmt = $pdo->prepare($song_query);
    $stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $stmt->execute();
    $song = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$song) {
        echo "<script>alert('Case not found. Redirecting to Song Update page.'); window.location.href = 'song_update.php';</script>";
        exit();
    }

    // Check if 'cause' is not NULL
    $enableCloseCase = !is_null($song['cause']);
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch updates for the case
$updates = [];
$x1 = 1; // Initialize update number for display
try {
    $update_query = "SELECT update_no, DATE(timestamp) AS date, update_detail FROM case_update WHERE case_id = :case_id ORDER BY update_no ASC";
    $stmt = $pdo->prepare($update_query);
    $stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $stmt->execute();
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $x1 = count($updates) + 1;
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
    <title>รายละเอียดการอัปเดตเพลง</title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function updateCase(status) {
            const detail = document.getElementById("newDetail").value;
            const data = {
                case_id: <?= json_encode($case_id) ?>,
                update_no: <?= json_encode($x1) ?>,
                update_detail: detail,
                status: status
            };

            fetch("update_case.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    window.location.href = 'fixer.php';
                } else {
                    alert("Error: " + result.error);
                }
            })
            .catch(error => {
                alert("An error occurred: " + error.message);
            });
        }
    </script>
</head>
<body>

    <!-- Top Section with Title and Back Button -->
    <div class="top-section">
        <h2>อัปเดตเพลง - รหัสของเพลง: <?= htmlspecialchars($song['case_title']); ?></h2>
        <button onclick="location.href='song_update.php'">ย้อนกลับ</button>
    </div>

    <!-- Content Section -->
    <div class="content">
        <!-- Left Section for Case Details and Buttons -->
        <div class="left-section">
            <!-- Update Buttons in the Same Line -->
            <div class="button-row">
                <button onclick="updateCase(2)">อัปเดต</button>
                <button onclick="updateCase(3)" <?= $enableCloseCase ? '' : 'disabled' ?>>อัปเดต & แก้ไขเพลงสำเร็จ</button>
            </div>
            <p><strong>นักร้อง:</strong> <?= htmlspecialchars($song['user_name']) ?></p>
            <p><strong>ชื่อเพลง:</strong> <?= htmlspecialchars($song['case_title']) ?></p>
            <p><strong>สถานที่แต่งเพลง:</strong> <?= htmlspecialchars($song['place']) ?></p>
            <p><strong>วันที่แต่งเพลง:</strong> <?= htmlspecialchars($song['created_date']) ?></p>

            <!-- Case Details Table -->
            <table>
                <thead>
                    <tr>
                        <th>สถานะ</th>
                        <th>วันที่</th>
                        <th>เนื้อเพลง</th>
                        <th>แนบไฟล์</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>สร้าง</td>
                        <td><?= htmlspecialchars($song['created_date']) ?></td>
                        <td><?= htmlspecialchars($song['detail']) ?></td>
                        <td><button disabled>Picture</button> <button disabled>File</button></td>
                    </tr>
                    <?php foreach ($updates as $update): ?>
                        <tr>
                            <td>อัปเดต <?= htmlspecialchars($update['update_no']) ?></td>
                            <td><?= htmlspecialchars($update['date']) ?></td>
                            <td><?= htmlspecialchars($update['update_detail']) ?></td>
                            <td><button disabled>Picture</button> <button disabled>File</button></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td>อัปเดต <?= htmlspecialchars($x1) ?></td>
                        <td><?= date("Y-m-d") ?></td>
                        <td><textarea id="newDetail"></textarea></td>
                        <td><button disabled>Picture</button> <button disabled>File</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Right Section for Images -->
        <div class="right-section">
            <img src="../pic/chuch_guita_1.webp" alt="Chuch Guitar">
        </div>
    </div>
</body>
</html>
