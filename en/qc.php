<?php
session_start();
include("db_connection.php");

// Fetch language code from the session and database
$lang_code = "eng"; // Default language
try {
    $query = "SELECT code FROM language WHERE id = :lang_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['lang_id' => $_SESSION['lang']]);
    $lang_code = $stmt->fetchColumn() ?: $lang_code;
} catch (PDOException $e) {
    // Handle database errors
}

// Load language JSON file
$lang_file = "../lang/{$lang_code}/qc.json";
$translations = file_exists($lang_file) ? json_decode(file_get_contents($lang_file), true) : [];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang_code) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($translations['page_title'] ?? 'QC Management') ?></title>
    <link rel="stylesheet" href="../style/basic.css">
</head>
<body>
    <!-- Top Section -->
    <div class="top-section">
        <h1><?= htmlspecialchars($translations['header'] ?? 'QC Management') ?></h1>
        <button onclick="location.href='main_page.php'"><?= htmlspecialchars($translations['back_button'] ?? 'Back') ?></button>
    </div>

    <!-- Content Section -->
    <div class="content">
        <!-- Left Section with Buttons -->
        <div class="left-section">
            <table>
                <tr>
                    <td><button onclick="location.href='qc_new_song.php'"><?= htmlspecialchars($translations['new_song_button'] ?? 'New Song') ?></button></td>
                    <td><button onclick="location.href='qc_manage.php?type=location'"><?= htmlspecialchars($translations['location_button'] ?? 'Location') ?></button></td>
                </tr>
                <tr>
                    <td><button onclick="location.href='recover.php'"><?= htmlspecialchars($translations['recover_button'] ?? 'Recover') ?></button></td>
                    <td><button onclick="location.href='qc_manage.php?type=cause'"><?= htmlspecialchars($translations['cause_button'] ?? 'Cause') ?></button></td>
                </tr>
                <tr>
                    <td><button onclick="location.href='scoring.php'"><?= htmlspecialchars($translations['scoring_button'] ?? 'Scoring') ?></button></td>
                </tr>
            </table>
        </div>

        <!-- Right Section with Image -->
        <div class="right-section">
            <img src="../pic/pao_1.webp" alt="<?= htmlspecialchars($translations['image_alt'] ?? 'Pao Image') ?>">
        </div>
    </div>
</body>
</html>
