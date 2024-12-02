<?php
session_start();
include("db_connection.php");

// Get language code from the session and fetch translations
$lang_id = $_SESSION['lang'] ?? null;
if (!$lang_id) {
    die("Language ID not set in session.");
}

try {
    // Fetch the language code from the database
    $lang_query = "SELECT code FROM language WHERE id = :lang_id";
    $stmt = $pdo->prepare($lang_query);
    $stmt->bindParam(':lang_id', $lang_id, PDO::PARAM_INT);
    $stmt->execute();
    $lang_code = $stmt->fetchColumn();

    if (!$lang_code) {
        die("Language code not found for ID {$lang_id}.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Load the JSON translation file
$json_path = "../lang/{$lang_code}/all_song.json";
if (!file_exists($json_path)) {
    die("Translation file not found: " . $json_path);
}

$translations = json_decode(file_get_contents($json_path), true);


// Check if the user is logged in and has an account ID
$account_id = $_SESSION['account_id'] ?? null;
if (!$account_id) {
    echo "<script>alert('Unauthorized access. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
    exit();
}

// Fetch status names
$status_names = [];
try {
    $status_query = "SELECT id, name FROM status";
    $stmt = $pdo->prepare($status_query);
    $stmt->execute();
    $status_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($status_results as $status) {
        $status_names[$status['id']] = $status['name'];
    }
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Default values for filters
$song_group = $_GET['song_group'] ?? 'new_release';
$case_title = $_GET['case_title'] ?? '';
$user_name = $_GET['user_name'] ?? '';
$location = $_GET['location'] ?? '';
$fixer_name = $_GET['fixer_name'] ?? '';
$create_date = $_GET['create_date'] ?? '';
$close_date = $_GET['close_date'] ?? '';

// Build SQL conditions based on filters
$conditions = "c.account_id = :account_id";
$params = [':account_id' => $account_id];

// Song group condition
if ($song_group === 'new_release') {
    $conditions .= " AND c.status IN (0, 1, 2)";
}

// Optional filters
if (!empty($case_title)) {
    $conditions .= " AND c.case_title LIKE :case_title";
    $params[':case_title'] = '%' . $case_title . '%';
}
if (!empty($user_name)) {
    $conditions .= " AND u.user_name LIKE :user_name";
    $params[':user_name'] = '%' . $user_name . '%';
}
if (!empty($location)) {
    $conditions .= " AND c.place LIKE :location";
    $params[':location'] = '%' . $location . '%';
}
if (!empty($fixer_name)) {
    $conditions .= " AND f.user_name LIKE :fixer_name";
    $params[':fixer_name'] = '%' . $fixer_name . '%';
}
if (!empty($create_date)) {
    $conditions .= " AND DATE(c.created_at) = :create_date";
    $params[':create_date'] = $create_date;
}
if (!empty($close_date)) {
    $conditions .= " AND DATE(c.close_at) = :close_date";
    $params[':close_date'] = $close_date;
}

// Sorting based on song group
$order_by = ($song_group === 'new_release') ? "c.created_at ASC" : "c.created_at DESC";

// Fetch filtered songs
try {
    $query = "
        SELECT c.case_id, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               COALESCE(f.user_name, 'N/A') AS fixer_name, 
               c.case_title, 
               c.place, 
               c.status, 
               DATE(c.created_at) AS created_date, 
               DATE(c.close_at) AS close_date
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN user f ON c.fixer = f.user_id
        WHERE $conditions
        ORDER BY $order_by";
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => &$value) {
        $stmt->bindParam($key, $value);
    }
    $stmt->execute();
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}
?>


<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang_code) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($translations['page_title'] ?? 'All Songs') ?></title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function applyFilters() {
            document.getElementById('filterForm').submit();
        }

        function clearFilters() {
            document.querySelectorAll('#filterForm input[type="text"], #filterForm input[type="date"]').forEach(input => input.value = '');
            document.getElementById('song_group').value = 'new_release';
            document.getElementById('filterForm').submit();
        }

        function enableViewButton() {
            document.getElementById('viewButton').disabled = false;
        }

        function viewSong() {
            const selectedSong = document.querySelector('input[name="songSelect"]:checked');
            if (selectedSong) {
                document.getElementById('selectedCaseId').value = selectedSong.value;
                document.getElementById('viewForm').submit();
            } else {
                alert('<?= $translations['select_song_alert'] ?? 'Please select a song to view.' ?>');
            }
        }

        function toggleFilter() {
            const filterDiv = document.getElementById('filterDiv');
            filterDiv.style.display = filterDiv.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="top-section">
        <h2><?= htmlspecialchars($translations['page_header'] ?? 'All Songs') ?></h2>
        <div>
            <button onclick="location.href='report.php'"><?= htmlspecialchars($translations['button_back'] ?? 'Back') ?></button>
            <button id="viewButton" onclick="viewSong()" disabled><?= htmlspecialchars($translations['button_view'] ?? 'View') ?></button>
            <button onclick="toggleFilter()"><?= htmlspecialchars($translations['button_toggle_filter'] ?? 'Toggle Filter') ?></button>
        </div>
    </div>

    <form id="viewForm" action="view_case.php" method="POST">
        <input type="hidden" name="case_id" id="selectedCaseId">
        <input type="hidden" name="ref_page" value="all_song">
    </form>

    <div class="content">
        <div class="left-section">
            <div id="filterDiv" style="display: none;">
                <form id="filterForm" method="GET">
                    <label for="song_group"><?= htmlspecialchars($translations['filter_song_group'] ?? 'Song Group:') ?></label>
                    <select name="song_group" id="song_group">
                        <option value="new_release" <?= $song_group === 'new_release' ? 'selected' : '' ?>>
                            <?= htmlspecialchars($translations['song_group_new_release'] ?? 'New Release') ?>
                        </option>
                        <option value="all_songs" <?= $song_group === 'all_songs' ? 'selected' : '' ?>>
                            <?= htmlspecialchars($translations['song_group_all_songs'] ?? 'All Songs') ?>
                        </option>
                    </select><br>

                    <label for="case_title"><?= htmlspecialchars($translations['filter_case_title'] ?? 'Case Title:') ?></label>
                    <input type="text" name="case_title" value="<?= htmlspecialchars($case_title) ?>"><br>

                    <label for="user_name"><?= htmlspecialchars($translations['filter_user_name'] ?? 'User Name:') ?></label>
                    <input type="text" name="user_name" value="<?= htmlspecialchars($user_name) ?>"><br>

                    <label for="location"><?= htmlspecialchars($translations['filter_location'] ?? 'Location:') ?></label>
                    <input type="text" name="location" value="<?= htmlspecialchars($location) ?>"><br>

                    <label for="fixer_name"><?= htmlspecialchars($translations['filter_fixer_name'] ?? 'Fixer Name:') ?></label>
                    <input type="text" name="fixer_name" value="<?= htmlspecialchars($fixer_name) ?>"><br>

                    <label for="create_date"><?= htmlspecialchars($translations['filter_create_date'] ?? 'Create Date:') ?></label>
                    <input type="date" name="create_date" value="<?= htmlspecialchars($create_date) ?>"><br>

                    <label for="close_date"><?= htmlspecialchars($translations['filter_close_date'] ?? 'Close Date:') ?></label>
                    <input type="date" name="close_date" value="<?= htmlspecialchars($close_date) ?>"><br>

                    <button type="button" onclick="applyFilters()"><?= htmlspecialchars($translations['button_filter'] ?? 'Filter') ?></button>
                    <button type="button" onclick="clearFilters()"><?= htmlspecialchars($translations['button_clear_filter'] ?? 'Clear Filter') ?></button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th><?= htmlspecialchars($translations['column_select'] ?? 'Select') ?></th>
                        <th><?= htmlspecialchars($translations['column_user_name'] ?? 'User Name') ?></th>
                        <th><?= htmlspecialchars($translations['column_fixer_name'] ?? 'Fixer Name') ?></th>
                        <th><?= htmlspecialchars($translations['column_case_title'] ?? 'Case Title') ?></th>
                        <th><?= htmlspecialchars($translations['column_place'] ?? 'Place') ?></th>
                        <th><?= htmlspecialchars($translations['column_status'] ?? 'Status') ?></th>
                        <th><?= htmlspecialchars($translations['column_create_date'] ?? 'Create Date') ?></th>
                        <th><?= htmlspecialchars($translations['column_close_date'] ?? 'Close Date') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($songs as $song): ?>
                        <tr>
                            <td><input type="radio" name="songSelect" value="<?= $song['case_id'] ?>" onchange="enableViewButton()"></td>
                            <td><?= htmlspecialchars($song['user_name']) ?></td>
                            <td><?= htmlspecialchars($song['fixer_name']) ?></td>
                            <td><?= htmlspecialchars($song['case_title']) ?></td>
                            <td><?= htmlspecialchars($song['place']) ?></td>
                            <td><?= htmlspecialchars($status_names[$song['status']] ?? $translations['status_unknown'] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($song['created_date']) ?></td>
                            <td><?= htmlspecialchars($song['close_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="right-section">
            <img src="../pic/sri_3.png" alt="<?= htmlspecialchars($translations['alt_image_1'] ?? 'Image 1') ?>">
            <img src="../pic/sri_4.png" alt="<?= htmlspecialchars($translations['alt_image_2'] ?? 'Image 2') ?>">
        </div>
    </div>
</body>
</html>
