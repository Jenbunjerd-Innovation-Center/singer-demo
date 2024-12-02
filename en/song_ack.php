<?php
session_start();
include("db_connection.php");

// Check if user is logged in and authorized (role 2 or above)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 2) {
    echo "<script>alert('You are not authorized to access this page. Redirecting to the Fixer page.'); window.location.href = 'fixer.php';</script>";
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

    // Fetch user's language code
    $lang_query = "SELECT code FROM language WHERE id = (SELECT lang FROM user WHERE user_id = :user_id)";
    $lang_stmt = $pdo->prepare($lang_query);
    $lang_stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $lang_stmt->execute();
    $lang_code = $lang_stmt->fetchColumn();
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch list of songs with status = 0 and same account_id
$songs = [];
try {
    $song_query = "
        SELECT c.case_id, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               c.case_title, 
               c.detail, 
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

// Load the language JSON file
$lang_path = "../lang/$lang_code/song_ack.json";
$lang = json_decode(file_get_contents($lang_path), true);
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang_code) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lang['title']) ?></title>
    <link rel="stylesheet" href="../style/basic.css">
</head>
<body>
    <!-- Top Section -->
    <div class="top-section">
        <div class="top-left">
            <h2><?= htmlspecialchars($lang['heading']) ?></h2>
        </div>
        <div class="top-right">
            <button onclick="location.href='fixer.php'"><?= htmlspecialchars($lang['back_button']) ?></button>
            <button id="viewButton" onclick="acknowledgeSong()" disabled><?= htmlspecialchars($lang['acknowledge_button']) ?></button>
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
                        <th><?= htmlspecialchars($lang['user_column']) ?></th>
                        <th><?= htmlspecialchars($lang['title_column']) ?></th>
                        <th><?= htmlspecialchars($lang['detail_column']) ?></th>
                        <th><?= htmlspecialchars($lang['place_column']) ?></th>
                        <th><?= htmlspecialchars($lang['created_date_column']) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($songs as $song): ?>
                        <tr>
                            <td><input type="radio" name="songSelect" value="<?= $song['case_id'] ?>" onchange="enableViewButton()"></td>
                            <td><?= htmlspecialchars($song['user_name']) ?></td>
                            <td><?= htmlspecialchars($song['case_title']) ?></td>
                            <td><?= htmlspecialchars($song['detail']) ?></td>
                            <td><?= htmlspecialchars($song['place']) ?></td>
                            <td><?= htmlspecialchars($song['created_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Right Section with Image -->
        <div class="right-section">
            <img src="../pic/chuch_1.webp" alt="<?= htmlspecialchars($lang['image_alt']) ?>">
        </div>
    </div>

    <script>
        function enableViewButton() {
            document.getElementById('viewButton').disabled = false;
        }

        function acknowledgeSong() {
            const selectedCaseId = document.querySelector('input[name="songSelect"]:checked');
            if (!selectedCaseId) {
                alert("<?= htmlspecialchars($lang['select_alert']) ?>");
                return;
            }

            fetch("acknowledge_song.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ case_id: selectedCaseId.value })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert("<?= htmlspecialchars($lang['success_message']) ?>");
                    location.reload();
                } else {
                    alert("<?= htmlspecialchars($lang['error_message']) ?>" + result.error);
                }
            })
            .catch(error => alert("<?= htmlspecialchars($lang['error_message']) ?>" + error.message));
        }
    </script>
</body>
</html>
