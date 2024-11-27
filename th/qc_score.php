<?php
session_start();
include("db_connection.php");

// Check if the case_id is passed via POST
$case_id = $_POST['case_id'] ?? null;
if (!$case_id) {
    echo "<script>alert('No case selected. Redirecting to Scoring Page.'); window.location.href = 'scoring.php';</script>";
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
        echo "<script>alert('Case not found.'); window.location.href = 'scoring.php';</script>";
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

// Handle score updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['case_score']) && $_POST['case_score'] !== "") {
        $case_score = (int) $_POST['case_score'];
        $update_query = "UPDATE song_case SET score = :score WHERE case_id = :case_id";
        $stmt = $pdo->prepare($update_query);
        $stmt->execute([':score' => $case_score, ':case_id' => $case_id]);
        $case['score'] = $case_score; // Update displayed score
    } 
    
    if (isset($_POST['fix_score']) && $_POST['fix_score'] !== "") {
        $fix_score = (int) $_POST['fix_score'];
        $update_query = "UPDATE song_case SET fix_score = :fix_score WHERE case_id = :case_id";
        $stmt = $pdo->prepare($update_query);
        $stmt->execute([':fix_score' => $fix_score, ':case_id' => $case_id]);
        $case['fix_score'] = $fix_score; // Update displayed fix score
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คะแนนผู้คุมเสียงดนตรี</title>
    <link rel="stylesheet" href="../style/basic.css">
</head>
<body>
    <!-- Top Section -->
    <div class="top-section">
        <h2>คะแนนผู้คุมเสียงดนตรี</h2>
        <button onclick="location.href='scoring.php'">ย้อนกลับ</button>
    </div>

    <!-- Content Section -->
    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <!-- Case Information -->
            <h3>รายละเอียดเพลงและการอัปเดต</h3>
            <p><strong>ชื่อเพลง:</strong> <?= htmlspecialchars($case['case_title']) ?></p>
            <p><strong>นักร้อง:</strong> <?= htmlspecialchars($case['user_name']) ?></p>
            <p><strong>นักปรับแต่งเพลง:</strong> <?= htmlspecialchars($case['fixer_name']) ?></p>
            <p><strong>สถานที่แต่งเพลง:</strong> <?= htmlspecialchars($case['place']) ?></p>

            <!-- Score Management -->
            <h3>จัดการคะแนน</h3>
            <form method="POST">
                <input type="hidden" name="case_id" value="<?= htmlspecialchars($case_id) ?>">

                <label for="case_score">คะแนนเพลง:</label>
                <input type="number" name="case_score" value="<?= htmlspecialchars($case['score']) ?>" min="0" max="9">
                <button type="submit">อัปเดต</button><br>
                
                <label for="fix_score">คะแนนการแก้ไขเพลง:</label>
                <input type="number" name="fix_score" value="<?= htmlspecialchars($case['fix_score']) ?>" min="0" max="9">
                <button type="submit">อัปเดต</button>
            </form>

            <!-- Case Details and Updates -->
            <h3>รายละเอียดเพลงและการอัปเดต</h3>
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
                        <td><?= htmlspecialchars(explode(' ', $case['created_at'])[0]) ?></td>
                        <td><?= htmlspecialchars($case['detail']) ?></td>
                        <td><button disabled>Picture</button> <button disabled>File</button></td>
                    </tr>
                    <?php foreach ($updates as $update): ?>
                        <tr>
                            <td>อัปเดต <?= htmlspecialchars($update['update_no']) ?></td>
                            <td><?= htmlspecialchars($update['date']) ?></td>
                            <td><?= htmlspecialchars($update['detail']) ?></td>
                            <td><button disabled>Picture</button> <button disabled>File</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <img src="../pic/pao_mas2.webp" alt="Pao Image">
        </div>
    </div>
</body>
</html>
