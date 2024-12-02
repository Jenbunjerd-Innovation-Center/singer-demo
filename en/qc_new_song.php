<?php
session_start();
include("db_connection.php");

// Check if user is logged in and authorized
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 3) {
    echo "<script>alert('You are not authorized to access this page. Redirecting to the QC Management page.'); window.location.href = 'qc.php';</script>";
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

// Fetch language code for the session
$lang_code = 'eng'; // Default language
try {
    $lang_query = "SELECT code FROM language WHERE id = :lang_id";
    $lang_stmt = $pdo->prepare($lang_query);
    $lang_stmt->bindParam(":lang_id", $_SESSION['lang'], PDO::PARAM_INT);
    $lang_stmt->execute();
    $lang_code = $lang_stmt->fetchColumn();
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Load translations
$jsonPath = "../lang/$lang_code/qc_new_song.json";
$translations = [];
if (file_exists($jsonPath)) {
    $translations = json_decode(file_get_contents($jsonPath), true);
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
    <title><?= $translations['title'] ?? 'QC New Song' ?></title>
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
                alert("<?= $translations['select_song'] ?? 'Please select a song to view.' ?>");
            }
        }
    </script>
</head>
<body>
    <div class="top-section">
        <h1><?= $translations['header'] ?? 'QC New Song' ?></h1>
        <div>
            <button onclick="location.href='qc.php'"><?= $translations['back'] ?? 'Back' ?></button>
            <button id="viewButton" onclick="viewSong()" disabled><?= $translations['view'] ?? 'View' ?></button>
        </div>
    </div>

    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <form id="songList">
                <table>
                    <thead>
                        <tr>
                            <th><?= $translations['select'] ?? 'Select' ?></th>
                            <th><?= $translations['user'] ?? 'User' ?></th>
                            <th><?= $translations['title_column'] ?? 'Title' ?></th>
                            <th><?= $translations['place'] ?? 'Place' ?></th>
                            <th><?= $translations['created_date'] ?? 'Created Date' ?></th>
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
            <img src="../pic/pao_2.webp" alt="<?= $translations['image_alt'] ?? 'Pao Image' ?>">
        </div>
    </div>
</body>
</html>
