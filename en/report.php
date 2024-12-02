<?php
session_start();
include("db_connection.php");

// Fetch the user's language preference
$lang_code = 'en'; // Default language
try {
    if (isset($_SESSION['lang'])) {
        $query = "SELECT code FROM language WHERE id = :lang_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':lang_id', $_SESSION['lang'], PDO::PARAM_INT);
        $stmt->execute();
        $lang_code = $stmt->fetchColumn() ?: 'en';
    }
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Load translations
$json_path = "../lang/{$lang_code}/report.json";
$translations = [];
if (file_exists($json_path)) {
    $translations = json_decode(file_get_contents($json_path), true);
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang_code) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($translations['title'] ?? 'Report Page') ?></title>
    <link rel="stylesheet" href="../style/basic.css">
</head>
<body>
    <!-- Top Section -->
    <div class="top-section">
        <h2><?= htmlspecialchars($translations['header'] ?? 'Reports') ?></h2>
        <button onclick="location.href='main_page.php'"><?= htmlspecialchars($translations['button_back'] ?? 'Back') ?></button>
    </div>

    <!-- Content Section -->
    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <table>
                <tr>
                    <td><button onclick="location.href='all_song.php'"><?= htmlspecialchars($translations['button_songs'] ?? 'Songs') ?></button></td>
                    <td><button onclick="location.href='top_artist.php'"><?= htmlspecialchars($translations['button_top_artist'] ?? 'Top Artist') ?></button></td>
                </tr>
                <tr>
                    <td><button onclick="location.href='top_style.php'"><?= htmlspecialchars($translations['button_top_style'] ?? 'Top Style') ?></button></td>
                    <td><!-- <button onclick="location.href='all_times.php'"><?= htmlspecialchars($translations['button_all_times_hit'] ?? 'All Times Hit') ?></button> --></td>
                </tr>
            </table>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <img src="../pic/sri_logo_2.webp" alt="<?= htmlspecialchars($translations['alt_sri_logo'] ?? 'Sri Logo') ?>">
            <img src="../pic/chuch_logo_1.webp" alt="<?= htmlspecialchars($translations['alt_chuch_logo'] ?? 'Chuch Logo') ?>">
            <img src="../pic/pao_logo1.webp" alt="<?= htmlspecialchars($translations['alt_pao_logo'] ?? 'Pao Logo') ?>">
        </div>
    </div>
</body>
</html>
