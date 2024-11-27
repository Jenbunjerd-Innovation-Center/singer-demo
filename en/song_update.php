<?php
session_start();
include("db_connection.php");

// Check if user is logged in and authorized (role 2 or above)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 2) {
    echo "<script>alert('You are not authorized to access this page. Redirecting to the Fixer page.'); window.location.href = 'fixer.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch list of songs assigned to the user (fixer) with status 1 or 2
$songs = [];
try {
    $song_query = "
        SELECT c.case_id, 
               COALESCE(u.user_name, 'The mask singer') AS user_name, 
               c.case_title, 
               c.place, 
               DATE(c.created_at) AS created_date
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.fixer = :user_id AND c.status IN (1, 2)
        ORDER BY c.created_at ASC";
    $song_stmt = $pdo->prepare($song_query);
    $song_stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $song_stmt->execute();
    $songs = $song_stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Song Update</title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function enableViewButton() {
            document.getElementById('viewButton').disabled = false;
        }

        function goToSongUpdate2() {
            const selectedCaseId = document.querySelector('input[name="songSelect"]:checked').value;

            // Create a form to send the case_id via POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'song_update2.php';

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
    <!-- Top Section with Title, Back, and View Button -->
    <div class="top-section">
        <div class="top-left">
            <h2>Song Update</h2>
        </div>
        <div class="top-right">
            <button onclick="location.href='fixer.php'">Back</button>
            <button id="viewButton" onclick="goToSongUpdate2()" disabled>View</button>
        </div>
    </div>

    <!-- Content Section -->
    <div class="content">
        <!-- Left Section with Table -->
        <div class="left-section">
            <table>
                <thead>
                    <tr>
                        <th></th>
                        <th>User</th>
                        <th>Title</th>
                        <th>Place</th>
                        <th>Created Date</th>
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
        </div>

        <!-- Right Section for Image -->
        <div class="right-section">
            <img src="../pic/chuch_2.webp" alt="Chuch Image">
        </div>
    </div>
</body>
</html>
