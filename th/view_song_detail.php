<?php
session_start();
include("db_connection.php");

// Check if the case_id is passed via POST
$case_id = $_POST['case_id'] ?? null;
if (!$case_id) {
    echo "<script>alert('No case selected. Redirecting to All Songs Page.'); window.location.href = 'all_song.php';</script>";
    exit();
}

// Fetch case details from song_case and user information
try {
    $query = "
        SELECT c.case_title, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               COALESCE(f.user_name, 'N/A') AS fixer_name, 
               c.place, 
               c.score, 
               c.fix_score, 
               c.created_at, 
               c.detail
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN user f ON c.fixer = f.user_id
        WHERE c.case_id = :case_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $stmt->execute();
    $case = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$case) {
        echo "<script>alert('Case not found.'); window.location.href = 'all_song.php';</script>";
        exit();
    }
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch case updates from case_update
$updates = [];
try {
    $update_query = "SELECT update_no, DATE(timestamp) AS date, update_detail AS detail FROM case_update WHERE case_id = :case_id ORDER BY update_no ASC";
    $update_stmt = $pdo->prepare($update_query);
    $update_stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $update_stmt->execute();
    $updates = $update_stmt->fetchAll(PDO::FETCH_ASSOC);
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
</head>
<body>
    <h2>เนื้อเพลง</h2>

    <!-- Back Button -->
    <button onclick="location.href='all_song.php'">ย้อนกลับ</button>

    <!-- Case Information -->
    <h3>ข้อมูลเพลง</h3>
    <p><strong>ชื่อเพลง:</strong> <?= htmlspecialchars($case['case_title']) ?></p>
    <p><strong>นักร้อง:</strong> <?= htmlspecialchars($case['user_name']) ?></p>
    <p><strong>นักปรับแต่งเพลง:</strong> <?= htmlspecialchars($case['fixer_name']) ?></p>
    <p><strong>สถานที่แต่งเพลง:</strong> <?= htmlspecialchars($case['place']) ?></p>
    <p><strong>คะแนนเพลง:</strong> <?= htmlspecialchars($case['score']) ?></p>
    <p><strong>คะแนนการแก้ไขเพลง:</strong> <?= htmlspecialchars($case['fix_score']) ?></p>

    <!-- Updates Table -->
    <h3>รายละเอียดเพลงและการอัปเดต</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>สถานะ</th>
            <th>วันที่แต่งเพลง</th>
            <th>เนื้อเพลง</th>
            <th>แนบไฟล์</th>
        </tr>
        <!-- Initial Case Details -->
        <tr>
            <td>สร้าง</td>
            <td><?= htmlspecialchars(explode(' ', $case['created_at'])[0]) ?></td>
            <td><?= htmlspecialchars($case['detail']) ?></td>
            <td><button disabled>Picture</button> <button disabled>File</button></td>
        </tr>
        
        <!-- Case Updates -->
        <?php foreach ($updates as $update): ?>
            <tr>
                <td>อัปเดต <?= htmlspecialchars($update['update_no']) ?></td>
                <td><?= htmlspecialchars($update['date']) ?></td>
                <td><?= htmlspecialchars($update['detail']) ?></td>
                <td><button disabled>Picture</button> <button disabled>File</button></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
