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

// Fetch language code
$lang_code = 'eng'; // Default language
if (isset($_SESSION['lang'])) {
    try {
        $lang_query = "SELECT code FROM language WHERE id = :lang_id";
        $lang_stmt = $pdo->prepare($lang_query);
        $lang_stmt->bindParam(":lang_id", $_SESSION['lang'], PDO::PARAM_INT);
        $lang_stmt->execute();
        $lang_code = $lang_stmt->fetchColumn() ?: 'eng';
    } catch (PDOException $e) {
        // Default to 'eng' on error
    }
}

// Load language file
$lang_file = "../lang/$lang_code/song_update2.json";
$translations = [];
if (file_exists($lang_file)) {
    $translations = json_decode(file_get_contents($lang_file), true);
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
    <title><?= htmlspecialchars($translations['title'] ?? 'Song Update Details') ?></title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function updateCase(status) {
            const detail = document.getElementById("newDetail").value;

            // Check if the detail has a value
            if (!detail) {
                alert("<?= $translations['empty_detail'] ?? 'Please provide update details.' ?>");
                return; // Exit the function
            }

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
                    window.location.href = 'song_update2.php';
                } else {
                    alert("<?= $translations['error_prefix'] ?? 'Error:' ?> " + result.error);
                }
            })
            .catch(error => {
                alert("<?= $translations['error_general'] ?? 'An error occurred:' ?> " + error.message);
            });
        }
        
    </script>
</head>
<body>
    <div class="top-section">
        <h2><?= htmlspecialchars($translations['title'] ?? 'Song Update') ?> - <?= htmlspecialchars($song['case_title']); ?></h2>
        <button onclick="location.href='song_update.php'"><?= htmlspecialchars($translations['back_button'] ?? 'Back') ?></button>
    </div>

    <div class="content">
        <div class="left-section">
            <div class="button-row">
                <button onclick="updateCase(2)"><?= htmlspecialchars($translations['update_button'] ?? 'Update') ?></button>
                <button onclick="updateCase(3)" <?= $enableCloseCase ? '' : 'disabled' ?>>
                    <?= htmlspecialchars($translations['update_close_button'] ?? 'Update & Close Case') ?>
                </button>
            </div>
            <p><strong><?= htmlspecialchars($translations['user_label'] ?? 'User:') ?></strong> <?= htmlspecialchars($song['user_name']) ?></p>
            <p><strong><?= htmlspecialchars($translations['title_label'] ?? 'Title:') ?></strong> <?= htmlspecialchars($song['case_title']) ?></p>
            <p><strong><?= htmlspecialchars($translations['place_label'] ?? 'Place:') ?></strong> <?= htmlspecialchars($song['place']) ?></p>
            <p><strong><?= htmlspecialchars($translations['created_date_label'] ?? 'Created Date:') ?></strong> <?= htmlspecialchars($song['created_date']) ?></p>

            <table>
                <thead>
                    <tr>
                        <th><?= htmlspecialchars($translations['state_column'] ?? 'State') ?></th>
                        <th><?= htmlspecialchars($translations['date_column'] ?? 'Date') ?></th>
                        <th><?= htmlspecialchars($translations['detail_column'] ?? 'Detail') ?></th>
                        <th><?= htmlspecialchars($translations['attachment_column'] ?? 'Attached') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($translations['create_label'] ?? 'Create') ?></td>
                        <td><?= htmlspecialchars($song['created_date']) ?></td>
                        <td><?= htmlspecialchars($song['detail']) ?></td>
                        <td><button disabled><?= htmlspecialchars($translations['picture_button'] ?? 'Picture') ?></button>
                            <button disabled><?= htmlspecialchars($translations['file_button'] ?? 'File') ?></button></td>
                    </tr>
                    <?php foreach ($updates as $update): ?>
                        <tr>
                            <td><?= htmlspecialchars($translations['update_label'] ?? 'Update') ?> <?= htmlspecialchars($update['update_no']) ?></td>
                            <td><?= htmlspecialchars($update['date']) ?></td>
                            <td><?= htmlspecialchars($update['update_detail']) ?></td>
                            <td><button disabled><?= htmlspecialchars($translations['picture_button'] ?? 'Picture') ?></button>
                                <button disabled><?= htmlspecialchars($translations['file_button'] ?? 'File') ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><?= htmlspecialchars($translations['update_label'] ?? 'Update') ?> <?= htmlspecialchars($x1) ?></td>
                        <td><?= date("Y-m-d") ?></td>
                        <td><textarea id="newDetail" required></textarea></td>
                        <td><button disabled><?= htmlspecialchars($translations['picture_button'] ?? 'Picture') ?></button>
                            <button disabled><?= htmlspecialchars($translations['file_button'] ?? 'File') ?></button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="right-section">
            <img src="../pic/chuch_guita_1.webp" alt="<?= htmlspecialchars($translations['image_alt'] ?? 'Chuch Guitar') ?>">
        </div>
    </div>
</body>
</html>
