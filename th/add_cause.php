<?php
session_start();

include("db_connection.php");

// Check if user is logged in and authorized
if (!isset($_SESSION['user_id']) || !isset($_POST['case_id'])) {
    echo "<script>alert('No case selected or unauthorized access. Redirecting to Cause Management.'); window.location.href = 'cause.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$case_id = $_POST['case_id'];

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

// Fetch causes from the cause table
$causes = [];
try {
    $cause_query = "SELECT id, cause FROM cause WHERE account_id = :account_id";
    $cause_stmt = $pdo->prepare($cause_query);
    $cause_stmt->bindParam(":account_id", $account_id, PDO::PARAM_INT);
    $cause_stmt->execute();
    $causes = $cause_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch song case details
$song = [];
try {
    $song_query = "
        SELECT 
            c.case_title, 
            COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
            c.place, 
            c.status, 
            c.detail, 
            DATE(c.created_at) AS created_date 
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id 
        WHERE c.case_id = :case_id";
    $stmt = $pdo->prepare($song_query);
    $stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $stmt->execute();
    $song = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$song) {
        throw new Exception("Song case not found.");
    }
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
} catch (Exception $e) {
    echo "<script>alert('" . $e->getMessage() . "');</script>";
    exit();
}

// Fetch updates from case_update
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มสาเหตุ</title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function updateCause() {
            const dropdown = document.getElementById("causeDropdown").value;
            const textInput = document.getElementById("customCause").value.trim();

            if (!dropdown && !textInput) {
                alert("Please select or enter a cause.");
                return;
            }

            const cause = dropdown || textInput;

            fetch("update_cause.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    case_id: <?= json_encode($case_id) ?>,
                    cause: cause,
                }),
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert("Cause updated successfully!");
                    window.location.href = "cause.php";
                } else {
                    alert("Error: " + (result.error || "Unknown error"));
                }
            })
            .catch(error => {
                alert("An error occurred: " + error.message);
            });
        }
    </script>
</head>
<body>
    <div class="top-section">
        <h2>เพิ่มสาเหตุ</h2>
        <button onclick="location.href='cause.php'">ย้อนกลับ</button>
    </div>

    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <!-- Cause Selection -->
            <div class="input-group">
                <label for="causeDropdown">เลือกสาเหตุ:</label>
                <select id="causeDropdown">
                    <option value="">เลือกสาเหตุ</option>
                    <?php foreach ($causes as $cause): ?>
                        <option value="<?= htmlspecialchars($cause['cause']) ?>"><?= htmlspecialchars($cause['cause']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group">
                <label for="customCause">หรือพิมพ์สาเหตุ:</label>
                <input type="text" id="customCause" placeholder="Enter cause">
            </div>

            <div class="button-group">
                <button onclick="updateCause()">ยืนยัน</button>
            </div>

            <!-- Case Details -->
            <div class="case-details">
                <h3>เนื้อเพลง</h3>
                <p><strong>ชื่อเพลง:</strong> <?= htmlspecialchars($song['case_title']) ?></p>
                <p><strong>นักร้อง:</strong> <?= htmlspecialchars($song['user_name']) ?></p>
                <p><strong>สถานที่แต่งเพลง:</strong> <?= htmlspecialchars($song['place']) ?></p>
                <p><strong>สถานะ:</strong> <?= htmlspecialchars($song['status']) ?></p>

                <table>
                    <tr>
                        <th>สถานะ</th>
                        <th>วันที่</th>
                        <th>เนื้อเพลง</th>
                        <th>แนบไฟล์</th>
                    </tr>
                    <tr>
                        <td>สร้าง</td>
                        <td><?= htmlspecialchars($song['created_date']) ?></td>
                        <td><?= htmlspecialchars($song['detail']) ?></td>
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
                </table>
            </div>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <img src="../pic/chuch_2.webp" alt="Chuch Image">
        </div>
    </div>
</body>
</html>
