<?php
session_start();
include("db_connection.php");

// Validate the incoming case_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['case_id'])) {
    $case_id = $_POST['case_id'];
    $ref_page = $_POST['ref_page'] ?? 'default';
} else {
    echo "<script>alert('No case selected. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
    exit();
}

// Fetch case details
try {
    $case_query = "
        SELECT c.case_title, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
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
        echo "<script>alert('Case not found. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
        exit();
    }
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

$statusText = ["Create", "Acknowledge", "Ongoing", "Close", "Cancel", "Force Close"];
$currentStatus = isset($statusText[$case['status']]) ? $statusText[$case['status']] : "Unknown";
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
    'all_song' => 'all_song.php', // Add this line for all_song reference
    default => 'main_page.php',
};
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Case Details</title>
    <link rel="stylesheet" href="../style/basic.css">
</head>
<body>
    <!-- Top Section -->
    <div class="top-section">
        <h2>View Case Details</h2>
        <button onclick="location.href='<?= $backLink ?>'">Back</button>
    </div>

    <!-- Content Section -->
    <div class="content">
        <div class="left-section">
            <p><strong>Case Title:</strong> <?= htmlspecialchars($case['case_title']) ?></p>
            <p><strong>User:</strong> <?= htmlspecialchars($case['user_name']) ?></p>
            <p><strong>Place:</strong> <?= htmlspecialchars($case['place']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($currentStatus) ?></p>
            <?php if ($showCloseDate): ?>
                <p><strong>Close Date:</strong> <?= htmlspecialchars(explode(' ', $case['close_at'])[0]) ?></p>
            <?php endif; ?>

            <!-- Updates Table -->
            <table>
                <thead>
                    <tr>
                        <th>State</th>
                        <th>Date</th>
                        <th>Detail</th>
                        <th>Attached</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Create</td>
                        <td><?= htmlspecialchars(explode(' ', $case['created_at'])[0]) ?></td>
                        <td><?= htmlspecialchars($case['detail']) ?></td>
                        <td><button disabled>Picture</button> <button disabled>File</button></td>
                    </tr>
                    <?php foreach ($updates as $update): ?>
                        <tr>
                            <td>Update <?= htmlspecialchars($update['update_no']) ?></td>
                            <td><?= htmlspecialchars($update['date']) ?></td>
                            <td><?= htmlspecialchars($update['update_detail']) ?></td>
                            <td><button disabled>Picture</button> <button disabled>File</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="right-section">
            <img src="../pic/Sri_cry_2.png" alt="Sri Cry">
        </div>
    </div>
</body>
</html>
