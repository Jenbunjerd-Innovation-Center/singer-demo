<?php
session_start();
include("db_connection.php");

// Check if user is logged in and authorized
if (!isset($_SESSION['user_id']) || $_SESSION['role'] < 3) {
    echo "<script>alert('You are not authorized to access this page. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$case_id = $_POST['case_id'] ?? null;

if (!$case_id) {
    echo "<script>alert('No case selected. Redirecting to QC New Song Page.'); window.location.href = 'qc_new_song.php';</script>";
    exit();
}

// Fetch song case details
try {
    $case_query = "
        SELECT c.case_title, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               c.place, 
               c.status, 
               c.cause, 
               DATE(c.created_at) AS created_date, 
               c.detail
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.case_id = :case_id";
    $stmt = $pdo->prepare($case_query);
    $stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $stmt->execute();
    $case = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$case) {
        throw new Exception("Case not found.");
    }
} catch (Exception $e) {
    echo "<script>alert('" . $e->getMessage() . "'); window.location.href = 'qc_new_song.php';</script>";
    exit();
}

// Fetch updates for the table
try {
    $update_query = "
        SELECT update_no, DATE(timestamp) AS date, update_detail 
        FROM case_update 
        WHERE case_id = :case_id 
        ORDER BY update_no ASC";
    $stmt = $pdo->prepare($update_query);
    $stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $stmt->execute();
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch fixers for the dropdown
try {
    $fixer_query = "SELECT user_id, user_name FROM user WHERE account_id = :account_id AND role = 2";
    $stmt = $pdo->prepare($fixer_query);
    $stmt->bindParam(":account_id", $_SESSION['account_id'], PDO::PARAM_INT);
    $stmt->execute();
    $fixers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>ห้องผู้คุมเสียงดนตรี</title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function forceClose() {
            const reason = document.getElementById("forceCloseReason").value.trim();
            if (!reason) {
                alert("Please provide a reason for force closing.");
                return;
            }

            fetch("force_close_case.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    case_id: <?= json_encode($case_id) ?>,
                    force_reason: reason,
                }),
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert("Case successfully force closed.");
                    window.location.href = "qc_new_song.php";
                } else {
                    alert("Error: " + result.error);
                }
            })
            .catch(error => {
                alert("An error occurred: " + error.message);
            });
        }

        function forceAcknowledge() {
            const fixer = document.getElementById("fixerDropdown").value;
            if (!fixer) {
                alert("Please select a fixer.");
                return;
            }

            fetch("force_acknowledge_case.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    case_id: <?= json_encode($case_id) ?>,
                    fixer: fixer,
                }),
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert("Case successfully force acknowledged.");
                    window.location.href = "qc_new_song.php";
                } else {
                    alert("Error: " + result.error);
                }
            })
            .catch(error => {
                alert("An error occurred: " + error.message);
            });
        }
    </script>
</head>
<body>
    <!-- Top Section -->
    <div class="top-section">
        <h2>ห้องผู้คุมเสียงดนตรี</h2>
        <div>
            <button onclick="location.href='qc_new_song.php'">ย้อนกลับ</button>
            <button onclick="forceClose()">บังคับให้แก้ไขเพลงสำเร็จ</button>
            <button onclick="forceAcknowledge()">บังคับการตอบรับเพลง</button>
        </div>
    </div>

    <!-- Content Layout -->
    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <div class="sub-div-top">
                <div class="sub-div-top-left">
                    <p><strong>ชื่อเพลง:</strong> <?= htmlspecialchars($case['case_title']) ?></p>
                    <p><strong>นักร้อง:</strong> <?= htmlspecialchars($case['user_name']) ?></p>
                    <p><strong>สถานที่แต่งเพลง:</strong> <?= htmlspecialchars($case['place']) ?></p>
                    <p><strong>สถานะ:</strong> <?= htmlspecialchars($case['status']) ?></p>
                    <p><strong>สาเหตุ:</strong> <?= htmlspecialchars($case['cause'] ?? 'N/A') ?></p>
                </div>
                <div class="sub-div-top-right">
                    <textarea id="forceCloseReason" placeholder="Force close reason"></textarea>
                    <select id="fixerDropdown">
                        <option value="">เลือกนักปรับแต่งเพลง</option>
                        <?php foreach ($fixers as $fixer): ?>
                            <option value="<?= htmlspecialchars($fixer['user_id']) ?>"><?= htmlspecialchars($fixer['user_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="sub-div-bot">
                <table>
                    <thead>
                        <tr>
                            <th>สถานะ</th>
                            <th>วันที่</th>
                            <th>เนื้อเพลง</th>
                            <th>แนบไฟล์</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>สร้าง</td>
                            <td><?= htmlspecialchars($case['created_date']) ?></td>
                            <td><?= htmlspecialchars($case['detail']) ?></td>
                            <td><button disabled>Picture</button> <button disabled>File</button></td>
                        </tr>
                        <?php foreach ($updates as $update): ?>
                            <tr>
                                <td>อัปเดต <?= htmlspecialchars($update['update_no']) ?></td>
                                <td><?= htmlspecialchars($update['date']) ?></td>
                                <td><?= htmlspecialchars($update['update_detail']) ?></td>
                                <td><button disabled>Picture</button> <button disabled>File</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <img src="../pic/pao_mas2.webp" alt="Pao Image">
        </div>
    </div>
</body>
</html>
