<?php
session_start();
include("db_connection.php");

// Check if user is logged in and authorized
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 2) {
    echo "<script>alert('You are not authorized to access this page. Redirecting to the Fixer page.'); window.location.href = 'fixer.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch song cases where cause is NULL and fixer_id is the current user
$songs = [];
try {
    $song_query = "
        SELECT 
            c.case_id, 
            COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
            c.case_title, 
            DATE(c.created_at) AS created_date 
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.fixer = :user_id AND c.cause IS NULL
        ORDER BY c.created_at ASC";
    $stmt = $pdo->prepare($song_query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>การจัดการสาเหตุ</title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function enableAddCauseButton() {
            document.getElementById("addCauseButton").disabled = false;
        }

        function addCause() {
            const selectedCaseId = document.querySelector('input[name="songSelect"]:checked').value;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'add_cause.php';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'case_id';
            input.value = selectedCaseId;

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</head>
<body>
    <!-- Top Section with Title and Buttons -->
    <div class="top-section">
        <h2>การจัดการสาเหตุ</h2>
        <div class="button-group">
            <button onclick="location.href='fixer.php'">ย้อนกลับ</button>
            <button id="addCauseButton" onclick="addCause()" disabled>เพิ่มสาเหตุ</button>
        </div>
    </div>

    <!-- Content Section -->
    <div class="content">
        <!-- Left Section for Song List -->
        <div class="left-section">
            <h3>สาเหตุของเพลงที่รอการแก้ไข</h3>
            <table>
                <thead>
                    <tr>
                        <th>เลือก</th>
                        <th>น้องร้อง</th>
                        <th>ชื่อเพลง</th>
                        <th>วันที่แต่งเพลง</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($songs as $song): ?>
                        <tr>
                            <td><input type="radio" name="songSelect" value="<?= $song['case_id'] ?>" onchange="enableAddCauseButton()"></td>
                            <td><?= htmlspecialchars($song['user_name']) ?></td>
                            <td><?= htmlspecialchars($song['case_title']) ?></td>
                            <td><?= htmlspecialchars($song['created_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Right Section for Image -->
        <div class="right-section">
            <img src="../pic/chuch_1.webp" alt="Chuch Image">
        </div>
    </div>
</body>
</html>
