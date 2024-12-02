<?php
session_start();
include("db_connection.php");

// Fetch language code for translations
$lang_code = '';
try {
    $lang_query = "SELECT code FROM language WHERE id = :lang_id";
    $lang_stmt = $pdo->prepare($lang_query);
    $lang_stmt->bindParam(":lang_id", $_SESSION['lang'], PDO::PARAM_INT);
    $lang_stmt->execute();
    $lang_code = $lang_stmt->fetchColumn();
    if (!$lang_code) {
        $lang_code = 'eng'; // Default to English if not found
    }
} catch (PDOException $e) {
    $lang_code = 'eng'; // Fallback
}

$json_path = "../lang/{$lang_code}/scoring.json";
$translations = [];
if (file_exists($json_path)) {
    $translations = json_decode(file_get_contents($json_path), true);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Unauthorized access. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
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

// Pagination
$items_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Fetch total count for pagination
try {
    $count_query = "
        SELECT COUNT(*) 
        FROM song_case c
        WHERE c.account_id = :account_id AND c.status IN (1, 2, 3)";
    $stmt = $pdo->prepare($count_query);
    $stmt->bindParam(":account_id", $account_id, PDO::PARAM_INT);
    $stmt->execute();
    $total_items = $stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch songs for scoring with limit and offset
$songs = [];
try {
    $song_query = "
        SELECT c.case_id, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               c.case_title, 
               c.place, 
               c.status, 
               DATE(c.created_at) AS created_date, 
               DATE(c.close_at) AS close_date
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.account_id = :account_id AND c.status IN (1, 2, 3)
        ORDER BY c.created_at DESC
        LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($song_query);
    $stmt->bindParam(":account_id", $account_id, PDO::PARAM_INT);
    $stmt->bindParam(":limit", $items_per_page, PDO::PARAM_INT);
    $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
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
    <title>Scoring</title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function enableViewButton() {
            document.getElementById('viewButton').disabled = false;
        }

        function toggleFilter() {
            const filterDiv = document.getElementById('filterDiv');
            filterDiv.style.display = filterDiv.style.display === 'none' ? 'block' : 'none';
        }

        function applyFilter() {
            const filterForm = document.getElementById('filterForm');
            filterForm.submit();
        }

        function clearFilter() {
            document.getElementById('caseTitle').value = '';
            document.getElementById('userName').value = '';
            document.getElementById('place').value = '';
            document.getElementById('status').value = '';
            applyFilter();
        }

        function viewCase() {
            const selectedCaseId = document.querySelector('input[name="songSelect"]:checked').value;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'qc_score.php'; 

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
    <div class="top-section">
        <h2><?= htmlspecialchars($translations['header'] ?? 'Scoring') ?></h2>
        <div>
            <button onclick="location.href='qc.php'"><?= htmlspecialchars($translations['back'] ?? 'Back') ?></button>
            <button id="viewButton" onclick="viewCase()" disabled><?= htmlspecialchars($translations['view'] ?? 'View') ?></button>
            <button onclick="toggleFilter()"><?= htmlspecialchars($translations['toggle_filter'] ?? 'Toggle Filter') ?></button>
        </div>
    </div>

    <div class="content">
        <div class="left-section">
            <div id="filterDiv" style="display: none; margin-top: 10px;">
                <form id="filterForm" method="GET">
                    <label for="caseTitle"><?= htmlspecialchars($translations['case_title'] ?? 'Case Title') ?>:</label>
                    <input type="text" id="caseTitle" name="case_title" placeholder="<?= htmlspecialchars($translations['case_title_placeholder'] ?? 'Case Title') ?>">
                    <label for="userName"><?= htmlspecialchars($translations['user_name'] ?? 'User Name') ?>:</label>
                    <input type="text" id="userName" name="user_name" placeholder="<?= htmlspecialchars($translations['user_name_placeholder'] ?? 'User Name') ?>">
                    <label for="place"><?= htmlspecialchars($translations['place'] ?? 'Place') ?>:</label>
                    <input type="text" id="place" name="place" placeholder="<?= htmlspecialchars($translations['place_placeholder'] ?? 'Place') ?>"><br>
                    <label for="status"><?= htmlspecialchars($translations['status'] ?? 'Status') ?>:</label>
                    <select id="status" name="status">
                        <option value=""><?= htmlspecialchars($translations['all'] ?? 'All') ?></option>
                        <option value="1"><?= htmlspecialchars($translations['status_acknowledge'] ?? 'Acknowledge') ?></option>
                        <option value="2"><?= htmlspecialchars($translations['status_ongoing'] ?? 'Ongoing') ?></option>
                        <option value="3"><?= htmlspecialchars($translations['status_close'] ?? 'Close') ?></option>
                    </select>
                    <button type="button" onclick="applyFilter()"><?= htmlspecialchars($translations['filter'] ?? 'Filter') ?></button>
                    <button type="button" onclick="clearFilter()"><?= htmlspecialchars($translations['clear_filter'] ?? 'Clear Filter') ?></button>
                </form>
            </div>
            <table>
                <thead>
                    <tr>
                        <th><?= htmlspecialchars($translations['select'] ?? 'Select') ?></th>
                        <th><?= htmlspecialchars($translations['user_name'] ?? 'User Name') ?></th>
                        <th><?= htmlspecialchars($translations['case_title'] ?? 'Case Title') ?></th>
                        <th><?= htmlspecialchars($translations['place'] ?? 'Place') ?></th>
                        <th><?= htmlspecialchars($translations['status'] ?? 'Status') ?></th>
                        <th><?= htmlspecialchars($translations['create_date'] ?? 'Create Date') ?></th>
                        <th><?= htmlspecialchars($translations['close_date'] ?? 'Close Date') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($songs as $song): ?>
                        <tr>
                            <td><input type="radio" name="songSelect" value="<?= $song['case_id'] ?>" onchange="enableViewButton()"></td>
                            <td><?= htmlspecialchars($song['user_name']) ?></td>
                            <td><?= htmlspecialchars($song['case_title']) ?></td>
                            <td><?= htmlspecialchars($song['place']) ?></td>
                            <td><?= htmlspecialchars($song['status'] == 1 ? $translations['status_acknowledge'] : ($song['status'] == 2 ? $translations['status_ongoing'] : $translations['status_close'])) ?></td>
                            <td><?= htmlspecialchars($song['created_date']) ?></td>
                            <td><?= htmlspecialchars($song['close_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Pagination remains unchanged -->
        </div>
        <div class="right-section">
            <img src="../pic/pao_logo1.webp" alt="<?= htmlspecialchars($translations['logo_alt'] ?? 'Pao Logo') ?>">
        </div>
    </div>
</body>
</html>
