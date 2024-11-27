<?php
session_start();
include("db_connection.php");

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

// Default to the latest month
$selected_month = $_GET['month_year'] ?? $month_years[0];

// Fetch data for "By Status" table
$by_status = [];
try {
    $query = "
        SELECT c.status, COUNT(*) AS song_qty
        FROM song_case c
        WHERE c.account_id = :account_id 
        AND DATE_FORMAT(c.created_at, '%Y-%m') = :selected_month
        AND c.status NOT IN (4, 5)
        GROUP BY c.status";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':account_id' => $account_id, ':selected_month' => $selected_month]);
    $by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch data for "By Location" table
$by_location = [];
try {
    $query = "
        SELECT c.place AS location, COUNT(*) AS song_qty
        FROM song_case c
        WHERE c.account_id = :account_id 
        AND DATE_FORMAT(c.created_at, '%Y-%m') = :selected_month
        AND c.status NOT IN (4, 5)
        GROUP BY c.place";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':account_id' => $account_id, ':selected_month' => $selected_month]);
    $by_location = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>รายงานตามประเภทยอดฮิต</title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function updateTable() {
            const selectedMonth = document.getElementById('monthDropdown').value;
            window.location.href = 'top_style.php?month_year=' + selectedMonth;
        }
    </script>
</head>
<body>
    <!-- Top Section -->
    <div class="top-section">
        <div class="top-left">
            <h2>รายงานตามประเภทยอดฮิต</h2>
        </div>
        <div class="top-right">
            <button onclick="location.href='report.html'">ย้อนกลับ</button>
            <select id="monthDropdown" class="short-dropdown" onchange="updateTable()">
                <?php foreach ($month_years as $month): ?>
                    <option value="<?= htmlspecialchars($month) ?>" <?= $selected_month === $month ? 'selected' : '' ?>>
                        <?= htmlspecialchars($month) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Content Section -->
    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <!-- By Status Table -->
            <h3>ตามสถานะ</h3>
            <table>
                <thead>
                    <tr>
                        <th>สถานะ</th>
                        <th>จำนวนเพลง</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($by_status as $status): ?>
                        <tr>
                            <td><?= htmlspecialchars($status['status']) ?></td>
                            <td><?= htmlspecialchars($status['song_qty']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- By Location Table -->
            <h3>ตามสถานที่</h3>
            <table>
                <thead>
                    <tr>
                        <th>สถานที่แต่งเพลง</th>
                        <th>จำนวนเพลง</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($by_location as $location): ?>
                        <tr>
                            <td><?= htmlspecialchars($location['location']) ?></td>
                            <td><?= htmlspecialchars($location['song_qty']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <img src="../pic/sri_cry_1.png" alt="Sri Cry 1">
            <img src="../pic/chuch_4.webp" alt="Chuch 4">
        </div>
    </div>
</body>
</html>
