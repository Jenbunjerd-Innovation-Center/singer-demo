<?php
session_start();
include("db_connection.php");

// Check if user is logged in and authorized
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 3) {
    echo "<script>alert('You are not authorized to access this page. Redirecting to the QC Management page.'); window.location.href = 'qc.html';</script>";
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

// Fetch list of songs
$songs = [];
try {
    $song_query = "
        SELECT c.case_id, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               c.case_title, 
               c.place, 
               DATE(c.created_at) AS created_date
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.account_id = :account_id AND c.status = 0
        ORDER BY c.created_at ASC";
    $stmt = $pdo->prepare($song_query);
    $stmt->bindParam(":account_id", $account_id, PDO::PARAM_INT);
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
    <title>ห้องเพลงใหม่</title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function enableViewButton() {
            document.getElementById("viewButton").disabled = false;
        }

        function viewSong() {
            const selectedSong = document.querySelector('input[name="songSelect"]:checked');
            if (selectedSong) {
                const form = document.createElement("form");
                form.method = "POST";
                form.action = "qc_view.php";
                
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "case_id";
                input.value = selectedSong.value;

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            } else {
                alert("Please select a song to view.");
            }
        }
    </script>
</head>
<body>
    <div class="top-section">
        <h1>ห้องเพลงใหม่</h1>
        <div>
            <button onclick="location.href='qc.html'">ย้อนกลับ</button>
            <button id="viewButton" onclick="viewSong()" disabled>ดูเพลง</button>
        </div>
    </div>

    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <form id="songList">
                <table>
                    <thead>
                        <tr>
                            <th>เลือก</th>
                            <th>นักร้อง</th>
                            <th>ชื่อเพลง</th>
                            <th>สถานที่แต่งเพลง</th>
                            <th>วันที่แต่งเพลง</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($songs as $song): ?>
                            <tr>
                                <td><input type="radio" name="songSelect" value="<?= $song['case_id'] ?>" onchange="enableViewButton()"></td>
                                <td><?= htmlspecialchars($song['user_name']) ?></td>
                                <td><?= htmlspecialchars($song['case_title']) ?></td>
                                <td><?= htmlspecialchars($song['place']) ?></td>
                                <td><?= htmlspecialchars($song['created_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <img src="../pic/pao_2.webp" alt="Pao Image">
        </div>
    </div>
</body>
</html>
