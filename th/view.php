<?php
session_start();
include("db_connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['case_id'])) {
    $case_id = $_POST['case_id'];
} else {
    echo "<script>alert('No case selected. Redirecting to Singer Page.'); window.location.href = 'singer.php';</script>";
    exit();
}

// Fetch case details from song_case
try {
    $case_query = "
        SELECT c.case_title, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               c.place, 
               c.created_at, 
               c.detail, 
               c.status, 
               c.close_at
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.case_id = :case_id";
    $stmt = $pdo->prepare($case_query);
    $stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $stmt->execute();
    $case = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$case) {
        echo "<script>alert('Case not found. Redirecting to Singer Page.'); window.location.href = 'singer.php';</script>";
        exit();
    }
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

$statusText = ["Create", "Acknowledge", "Ongoing", "Close", "Cancel", "Force Close"];
$currentStatus = isset($statusText[$case['status']]) ? $statusText[$case['status']] : "Unknown";
$showCloseDate = ($case['status'] == 3 || $case['status'] == 4) && $case['close_at'];

// Fetch updates from case_update for the selected case_id
$updates = [];
$x1 = 1; // Initialize update counter for display
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
    <title>ดูเนื้อเพลง</title>
    <link rel="stylesheet" href="../style/basic.css">
</head>
<body>
    <!-- Top Section with Title and Back Button -->
    <div class="top-section">
        <h2>ดูเนื้อเพลง</h2>
        <button onclick="location.href='singer.php'">ย้อนกลับ</button>
    </div>

    <!-- Bottom Section with Left and Right Columns -->
    <div class="content">
        <!-- Left Section for Case Information and Updates -->
        <div class="left-section">
            <!-- Case Information -->
            <p><strong>ชื่อเพลง:</strong> <?= htmlspecialchars($case['case_title']) ?></p>
            <p><strong>นักร้อง:</strong> <?= htmlspecialchars($case['user_name']) ?></p>
            <p><strong>สถานที่แต่งเพลง:</strong> <?= htmlspecialchars($case['place']) ?></p>
            <p><strong>สถานะ:</strong> <?= htmlspecialchars($currentStatus) ?></p>
            <?php if ($showCloseDate): ?>
                <p><strong>วันที่แก้ไขเพลงสำเร็จ:</strong> <?= htmlspecialchars(explode(' ', $case['close_at'])[0]) ?></p>
            <?php endif; ?>

            <!-- Case Updates Table -->
            <table>
                <tr>
                    <th>สถานะ</th>
                    <th>วันที่แต่งเพลง</th>
                    <th>เนื้อเพลง</th>
                    <th>แนบไฟล์</th>
                </tr>
                <!-- Initial Case Creation Row -->
                <tr>
                    <td>สร้าง</td>
                    <td><?= htmlspecialchars(explode(' ', $case['created_at'])[0]) ?></td>
                    <td><?= htmlspecialchars($case['detail']) ?></td>
                    <td><button disabled>Picture</button> <button disabled>File</button></td>
                </tr>
                <!-- Case Updates Rows -->
                <?php foreach ($updates as $update): ?>
                    <tr>
                        <td>Update <?= htmlspecialchars($update['update_no']) ?></td>
                        <td><?= htmlspecialchars($update['date']) ?></td>
                        <td><?= htmlspecialchars($update['update_detail']) ?></td>
                        <td><button disabled>Picture</button> <button disabled>File</button></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Right Section for Image -->
        <div class="right-section">
            <img src="../pic/Sri_cry_2.png" alt="Sri Cry">
        </div>
    </div>
</body>
</html>
