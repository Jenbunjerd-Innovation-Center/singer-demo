<?php
session_start();
include("db_connection.php");

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
    <title>การให้คะแนน</title>
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
        <h2>การให้คะแนน</h2>
        <div>
            <button onclick="location.href='qc.html'">ย้อนกลับ</button>
            <button id="viewButton" onclick="viewCase()" disabled>ดูเนื้อเพลง</button>
            <button onclick="toggleFilter()">ตัวกรองเพลง</button>
        </div>
    </div>

    <div class="content">
        <div class="left-section">
            <div id="filterDiv" style="display: none; margin-top: 10px;">
                <form id="filterForm" method="GET">
                    <label for="caseTitle">ชื่อเพลง:</label>
                    <input type="text" id="caseTitle" name="case_title" placeholder="Case Title">
                    <label for="userName">นักร้อง:</label>
                    <input type="text" id="userName" name="user_name" placeholder="User Name">
                    <label for="place">สถานที่แต่งเพลง:</label>
                    <input type="text" id="place" name="place" placeholder="Place"><br>
                    <label for="status">สถานะ:</label>
                    <select id="status" name="status">
                        <option value="">ทั้งหมด</option>
                        <option value="1">ตอบรับ</option>
                        <option value="2">กำลังดำเนินการ</option>
                        <option value="3">แก้ไขสำเร็จ</option>
                    </select>
                    <button type="button" onclick="applyFilter()">ตัวกรอง</button>
                    <button type="button" onclick="clearFilter()">ล้างตัวกรอง</button>
                </form>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>เลือก</th>
                        <th>นักร้อง</th>
                        <th>ชื่อเพลง</th>
                        <th>สถานที่แต่งเพลง</th>
                        <th>สถานะ</th>
                        <th>วันที่แต่งเพลง</th>
                        <th>วันที่แก้ไขเพลง</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($songs as $song): ?>
                        <tr>
                            <td><input type="radio" name="songSelect" value="<?= $song['case_id'] ?>" onchange="enableViewButton()"></td>
                            <td><?= htmlspecialchars($song['user_name']) ?></td>
                            <td><?= htmlspecialchars($song['case_title']) ?></td>
                            <td><?= htmlspecialchars($song['place']) ?></td>
                            <td>
                                <?= $song['status'] == 1 ? 'Acknowledge' : ($song['status'] == 2 ? 'Ongoing' : 'Close') ?>
                            </td>
                            <td><?= htmlspecialchars($song['created_date']) ?></td>
                            <td><?= htmlspecialchars($song['close_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?= $current_page - 1 ?>">ก่อนหน้า</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" <?= $i === $current_page ? 'class="active"' : '' ?>><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?= $current_page + 1 ?>">ถัดไป</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="right-section">
            <img src="../pic/pao_logo1.webp" alt="Pao Logo">
        </div>
    </div>
</body>
</html>
