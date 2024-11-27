<?php
session_start();
include("db_connection.php");

// Check if the user is logged in and has an account ID
$account_id = $_SESSION['account_id'] ?? null;
if (!$account_id) {
    echo "<script>alert('Unauthorized access. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
    exit();
}

// Fetch status names
$status_names = [];
try {
    $status_query = "SELECT id, name FROM status_name";
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Songs</title>
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
                alert('Please select a song to view.');
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
        <h2>All Songs</h2>
        <div>
            <button onclick="location.href='report.html'">Back</button>
            <button id="viewButton" onclick="viewSong()" disabled>View</button>
            <button onclick="toggleFilter()">Toggle Filter</button>
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
                    <label for="song_group">Song Group:</label>
                    <select name="song_group" id="song_group">
                        <option value="new_release" <?= $song_group === 'new_release' ? 'selected' : '' ?>>New Release</option>
                        <option value="all_songs" <?= $song_group === 'all_songs' ? 'selected' : '' ?>>All Songs</option>
                    </select><br>

                    <label for="case_title">Case Title:</label>
                    <input type="text" name="case_title" value="<?= htmlspecialchars($case_title) ?>"><br>

                    <label for="user_name">User Name:</label>
                    <input type="text" name="user_name" value="<?= htmlspecialchars($user_name) ?>"><br>

                    <label for="location">Location:</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($location) ?>"><br>

                    <label for="fixer_name">Fixer Name:</label>
                    <input type="text" name="fixer_name" value="<?= htmlspecialchars($fixer_name) ?>"><br>

                    <label for="create_date">Create Date:</label>
                    <input type="date" name="create_date" value="<?= htmlspecialchars($create_date) ?>"><br>

                    <label for="close_date">Close Date:</label>
                    <input type="date" name="close_date" value="<?= htmlspecialchars($close_date) ?>"><br>

                    <button type="button" onclick="applyFilters()">Filter</button>
                    <button type="button" onclick="clearFilters()">Clear Filter</button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>User Name</th>
                        <th>Fixer Name</th>
                        <th>Case Title</th>
                        <th>Place</th>
                        <th>Status</th>
                        <th>Create Date</th>
                        <th>Close Date</th>
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
                            <td><?= htmlspecialchars($status_names[$song['status']] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($song['created_date']) ?></td>
                            <td><?= htmlspecialchars($song['close_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="right-section">
            <img src="../pic/sri_3.png" alt="Sri 3">
            <img src="../pic/sri_4.png" alt="Sri 4">
        </div>
    </div>
</body>
</html>
