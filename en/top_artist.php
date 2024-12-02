<?php
session_start();
include("db_connection.php");


// Fetch language code from the database
$lang_code = $_SESSION['lang'] ?? 'en';
$lang_query = "SELECT code FROM language WHERE id = :lang_id";
$lang_stmt = $pdo->prepare($lang_query);
$lang_stmt->execute([':lang_id' => $lang_code]);
$lang_code = $lang_stmt->fetchColumn() ?? 'en';

// Load translations from JSON file
$json_path = "../lang/{$lang_code}/top_artist.json";
if (!file_exists($json_path)) {
    die("Translation file not found: {$json_path}");
}
$translations = json_decode(file_get_contents($json_path), true);

// Check if the user is logged in and has an account ID
$account_id = $_SESSION['account_id'] ?? null;
if (!$account_id) {
    echo "<script>alert('Unauthorized access. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
    exit();
}

// Fetch distinct month-year values for the dropdown
$month_years = [];
try {
    $query = "SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') AS month_year FROM song_case WHERE account_id = :account_id ORDER BY month_year DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':account_id' => $account_id]);
    $month_years = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Default to the latest month and "Top Vocalist" view
$selected_month = $_GET['month_year'] ?? $month_years[0];
$selected_view = $_GET['view'] ?? 'vocalist'; // Default to "vocalist"

// Fetch data based on the selected view
if ($selected_view === 'vocalist') {
    $data = [];
    try {
        $query = "
            SELECT COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
                   SUM(c.score) AS total_score, 
                   COUNT(c.user_id) AS total_song
            FROM song_case c
            LEFT JOIN user u ON c.user_id = u.user_id
            WHERE c.account_id = :account_id AND DATE_FORMAT(c.created_at, '%Y-%m') = :selected_month
            GROUP BY user_name";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':account_id' => $account_id, ':selected_month' => $selected_month]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
        exit();
    }
} elseif ($selected_view === 'guitarist') {
    $data = [];
    try {
        $query = "
            SELECT f.user_name AS fixer_name, 
                   SUM(c.fix_score) AS total_score, 
                   COUNT(c.fixer) AS total_song,
                   COUNT(CASE WHEN c.close_at IS NOT NULL THEN c.fixer END) AS total_close_song,
                   AVG(DATEDIFF(c.acc_at, c.created_at)) AS avg_accept_date,
                   AVG(CASE WHEN c.close_at IS NOT NULL THEN DATEDIFF(c.close_at, c.created_at) END) AS avg_close_date
            FROM song_case c
            LEFT JOIN user f ON c.fixer = f.user_id
            WHERE c.account_id = :account_id AND DATE_FORMAT(c.created_at, '%Y-%m') = :selected_month
                  AND c.fixer IS NOT NULL
            GROUP BY f.user_name";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':account_id' => $account_id, ':selected_month' => $selected_month]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang_code) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($translations['title']) ?></title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function updateTable() {
            const selectedMonth = document.getElementById('monthDropdown').value;
            const selectedView = document.getElementById('viewDropdown').value;
            window.location.href = `top_artist.php?month_year=${selectedMonth}&view=${selectedView}`;
        }
    </script>
</head>
<body>
    <div class="top-section">
        <div class="top-left">
            <h2><?= htmlspecialchars($translations['title']) ?></h2>
        </div>
        <div class="top-right">
            <button onclick="location.href='report.html'"><?= htmlspecialchars($translations['button_back']) ?></button>
        </div>
    </div>

    <div class="content">
        <div class="left-section">
            <label for="monthDropdown"><?= htmlspecialchars($translations['label_month']) ?>:</label>
            <select id="monthDropdown" class="short-dropdown" onchange="updateTable()">
                <?php foreach ($month_years as $month): ?>
                    <option value="<?= htmlspecialchars($month) ?>" <?= $selected_month === $month ? 'selected' : '' ?>>
                        <?= htmlspecialchars($month) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="viewDropdown"><?= htmlspecialchars($translations['label_view']) ?>:</label>
            <select id="viewDropdown" class="short-dropdown" onchange="updateTable()">
                <option value="vocalist" <?= $selected_view === 'vocalist' ? 'selected' : '' ?>><?= htmlspecialchars($translations['option_vocalist']) ?></option>
                <option value="guitarist" <?= $selected_view === 'guitarist' ? 'selected' : '' ?>><?= htmlspecialchars($translations['option_guitarist']) ?></option>
            </select>
            <h3><?= $selected_view === 'vocalist' ? htmlspecialchars($translations['header_vocalist']) : htmlspecialchars($translations['header_guitarist']) ?></h3>
            <table>
                <thead>
                    <tr>
                        <?php if ($selected_view === 'vocalist'): ?>
                            <th><?= htmlspecialchars($translations['column_vocalist_name']) ?></th>
                            <th><?= htmlspecialchars($translations['column_total_score']) ?></th>
                            <th><?= htmlspecialchars($translations['column_total_song']) ?></th>
                        <?php else: ?>
                            <th><?= htmlspecialchars($translations['column_guitarist_name']) ?></th>
                            <th><?= htmlspecialchars($translations['column_total_score']) ?></th>
                            <th><?= htmlspecialchars($translations['column_total_song']) ?></th>
                            <th><?= htmlspecialchars($translations['column_total_closed']) ?></th>
                            <th><?= htmlspecialchars($translations['column_avg_accept_days']) ?></th>
                            <th><?= htmlspecialchars($translations['column_avg_close_days']) ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php if ($selected_view === 'vocalist'): ?>
                                <td><?= htmlspecialchars($row['user_name']) ?></td>
                                <td><?= htmlspecialchars($row['total_score']) ?></td>
                                <td><?= htmlspecialchars($row['total_song']) ?></td>
                            <?php else: ?>
                                <td><?= htmlspecialchars($row['fixer_name']) ?></td>
                                <td><?= htmlspecialchars($row['total_score']) ?></td>
                                <td><?= htmlspecialchars($row['total_song']) ?></td>
                                <td><?= htmlspecialchars($row['total_close_song']) ?></td>
                                <td><?= is_null($row['avg_accept_date']) ? '-' : round($row['avg_accept_date'], 2) ?></td>
                                <td><?= is_null($row['avg_close_date']) ? '-' : round($row['avg_close_date'], 2) ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="right-section">
            <img src="../pic/sri_1.png" alt="Sri 1">
            <img src="../pic/chuch_1.webp" alt="Chuch 1">
        </div>
    </div>
</body>
</html>
