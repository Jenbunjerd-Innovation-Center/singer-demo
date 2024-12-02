<?php
session_start();
include("db_connection.php");

// Fetch language code from the database
$langCode = 'eng'; // Default fallback
if (isset($_SESSION['lang'])) {
    try {
        $langQuery = "SELECT code FROM language WHERE id = :lang_id";
        $langStmt = $pdo->prepare($langQuery);
        $langStmt->bindParam(":lang_id", $_SESSION['lang'], PDO::PARAM_INT);
        $langStmt->execute();
        $langCode = $langStmt->fetchColumn() ?: $langCode;
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
        exit();
    }
}

// Load language file
$jsonPath = "../lang/$langCode/view_case.json";
if (!file_exists($jsonPath)) {
    echo "<script>alert('Language file not found.');</script>";
    exit();
}
$langData = json_decode(file_get_contents($jsonPath), true);

// Validate the incoming case_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['case_id'])) {
    $case_id = $_POST['case_id'];
    $ref_page = $_POST['ref_page'] ?? 'default';
} else {
    echo "<script>alert('" . $langData['error_no_case'] . "'); window.location.href = 'main_page.php';</script>";
    exit();
}

// Fetch case details
try {
    $case_query = "
        SELECT c.case_title, 
               COALESCE(u.user_name, '" . $langData['default_user'] . "') AS user_name, 
               c.place, 
               c.created_at, 
               c.detail, 
               c.status, 
               c.close_at
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.case_id = :case_id";
    $stmt = $pdo->prepare($case_query);
    $stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $stmt->execute();
    $case = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$case) {
        echo "<script>alert('" . $langData['error_case_not_found'] . "'); window.location.href = 'main_page.php';</script>";
        exit();
    }
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

$statusText = $langData['status_labels'];
$currentStatus = $statusText[$case['status']] ?? $langData['status_unknown'];
$showCloseDate = ($case['status'] == 3 || $case['status'] == 4) && $case['close_at'];

// Fetch updates
$updates = [];
try {
    $update_query = "SELECT update_no, DATE(timestamp) AS date, update_detail FROM case_update WHERE case_id = :case_id ORDER BY update_no ASC";
    $stmt = $pdo->prepare($update_query);
    $stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $stmt->execute();
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Determine back link dynamically
$backLink = match ($ref_page) {
    'recover' => 'recover.php',
    'singer' => 'singer.php',
    'all_song' => 'all_song.php',
    default => 'main_page.php',
};
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $langData['page_title'] ?></title>
    <link rel="stylesheet" href="../style/basic.css">
</head>
<body>
    <!-- Top Section -->
    <div class="top-section">
        <h2><?= $langData['header'] ?></h2>
        <button onclick="location.href='<?= $backLink ?>'"><?= $langData['back_button'] ?></button>
    </div>

    <!-- Content Section -->
    <div class="content">
        <div class="left-section">
            <p><strong><?= $langData['case_title_label'] ?>:</strong> <?= htmlspecialchars($case['case_title']) ?></p>
            <p><strong><?= $langData['user_label'] ?>:</strong> <?= htmlspecialchars($case['user_name']) ?></p>
            <p><strong><?= $langData['place_label'] ?>:</strong> <?= htmlspecialchars($case['place']) ?></p>
            <p><strong><?= $langData['status_label'] ?>:</strong> <?= htmlspecialchars($currentStatus) ?></p>
            <?php if ($showCloseDate): ?>
                <p><strong><?= $langData['close_date_label'] ?>:</strong> <?= htmlspecialchars(explode(' ', $case['close_at'])[0]) ?></p>
            <?php endif; ?>

            <!-- Updates Table -->
            <table>
                <thead>
                    <tr>
                        <th><?= $langData['state_column'] ?></th>
                        <th><?= $langData['date_column'] ?></th>
                        <th><?= $langData['detail_column'] ?></th>
                        <th><?= $langData['attached_column'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= $langData['state_create'] ?></td>
                        <td><?= htmlspecialchars(explode(' ', $case['created_at'])[0]) ?></td>
                        <td><?= htmlspecialchars($case['detail']) ?></td>
                        <td><button disabled><?= $langData['picture_button'] ?></button> <button disabled><?= $langData['file_button'] ?></button></td>
                    </tr>
                    <?php foreach ($updates as $update): ?>
                        <tr>
                            <td><?= $langData['state_update'] . ' ' . htmlspecialchars($update['update_no']) ?></td>
                            <td><?= htmlspecialchars($update['date']) ?></td>
                            <td><?= htmlspecialchars($update['update_detail']) ?></td>
                            <td><button disabled><?= $langData['picture_button'] ?></button> <button disabled><?= $langData['file_button'] ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="right-section">
            <img src="../pic/Sri_cry_2.png" alt="<?= $langData['image_alt'] ?>">
        </div>
    </div>
</body>
</html>
