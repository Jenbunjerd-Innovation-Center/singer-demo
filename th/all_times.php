<?php
session_start();
include("db_connection.php");

// Check if the user is logged in and has an account ID
$account_id = $_SESSION['account_id'] ?? null;
if (!$account_id) {
    echo "<script>alert('Unauthorized access. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
    exit();
}

// Get dropdown selections
$type = $_GET['type'] ?? 'vocalist';
$months = $_GET['months'] ?? 6;

// Determine the type and table title
switch ($type) {
    case 'guitarist':
        $col_header = 'fixer_name';
        $table_title = 'Top Guitarists';
        break;
    case 'venue':
        $col_header = 'place';
        $table_title = 'Top Concert Venues';
        break;
    default:
        $col_header = 'user_name';
        $table_title = 'Top Vocalists';
        $type = 'vocalist';
}

// Calculate the date range based on the selected number of months
$date_range_start = date('Y-m-01', strtotime("-$months months"));
$current_month = date('Y-m-01'); // Start from the current month

// Fetch data for the pivot table based on the selected type and date range
$pivot_data = [];
try {
    $query = "
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               COALESCE(f.user_name, 'N/A') AS fixer_name, 
               place,
               COUNT(*) AS song_qty
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN user f ON c.fixer = f.user_id
        WHERE c.account_id = :account_id 
        AND c.status IN (0, 1, 2, 3) 
        AND created_at >= :date_range_start
        GROUP BY month, $col_header
        ORDER BY month DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':account_id' => $account_id,
        ':date_range_start' => $date_range_start,
    ]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize data into a pivot format
    foreach ($data as $row) {
        $month = $row['month'];
        $header_value = $row[$col_header];
        $pivot_data[$month][$header_value] = $row['song_qty'];
    }
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch all distinct column headers for display in the pivot table
$distinct_headers = [];
foreach ($data as $row) {
    $header_value = $row[$col_header];
    if (!in_array($header_value, $distinct_headers)) {
        $distinct_headers[] = $header_value;
    }
}
sort($distinct_headers); // Sort headers alphabetically or by default ordering
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการเพลงฮิตตลอดกาล</title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function refreshTable() {
            const type = document.getElementById('typeDropdown').value;
            const months = document.getElementById('monthDropdown').value;
            window.location.href = `all_times.php?type=${type}&months=${months}`;
        }
    </script>
</head>
<body>
    <!-- Top Section -->
    <div class="top-section">
        <div class="top-left">
            <h2>รายงานเพลงฮิตตลอดกาล</h2>
        </div>
        <div class="top-right">
            <button onclick="location.href='report.html'">ย้อนกลับ</button>
            <select id="typeDropdown" class="short-dropdown">
                <option value="vocalist" <?= $type === 'vocalist' ? 'selected' : '' ?>>นักร้อง</option>
                <option value="guitarist" <?= $type === 'guitarist' ? 'selected' : '' ?>>ผู้ปรับแต่งเพลง</option>
                <option value="venue" <?= $type === 'venue' ? 'selected' : '' ?>>สถานที่แสดง</option>
            </select>
            <select id="monthDropdown" class="short-dropdown">
                <option value="6" <?= $months == 6 ? 'selected' : '' ?>>6</option>
                <option value="12" <?= $months == 12 ? 'selected' : '' ?>>12</option>
                <option value="18" <?= $months == 18 ? 'selected' : '' ?>>18</option>
                <option value="24" <?= $months == 24 ? 'selected' : '' ?>>24</option>
            </select>
            <button onclick="refreshTable()">แสดง</button>
        </div>
    </div>

    <!-- Content Section -->
    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <h3><?= $table_title ?></h3>
            <table>
                <tr>
                    <th>เดือน</th>
                    <?php foreach ($distinct_headers as $header): ?>
                        <th><?= htmlspecialchars($header) ?></th>
                    <?php endforeach; ?>
                </tr>
                <?php foreach ($pivot_data as $month => $values): ?>
                    <tr>
                        <td><?= htmlspecialchars($month) ?></td>
                        <?php foreach ($distinct_headers as $header): ?>
                            <td><?= isset($values[$header]) ? htmlspecialchars($values[$header]) : '0' ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <img src="../pic/sri_1.png" alt="Sri Image">
            <img src="../pic/chuch_5.webp" alt="Chuch Image">
        </div>
    </div>
</body>
</html>
