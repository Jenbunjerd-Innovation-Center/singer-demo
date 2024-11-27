<?php
session_start();
include("db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Unauthorized access. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch account_id and case_recover from account based on user_id
try {
    $account_query = "
        SELECT a.account_id, a.case_recover 
        FROM account a
        JOIN user u ON u.account_id = a.account_id
        WHERE u.user_id = :user_id";
    $stmt = $pdo->prepare($account_query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$account) {
        echo "<script>alert('Account not found.'); window.location.href = 'qc.html';</script>";
        exit();
    }
    $account_id = $account['account_id'];
    $case_recover_days = $account['case_recover'];
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch recoverable songs based on the criteria
$songs = [];
try {
    $song_query = "
        SELECT c.case_id, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               c.case_title, 
               c.place, 
               DATE(c.close_at) AS close_date, 
               f.user_name AS fixer_name
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN user f ON c.fixer = f.user_id
        WHERE c.account_id = :account_id AND c.status = 3 
              AND DATEDIFF(CURDATE(), c.close_at) < :case_recover_days
        ORDER BY c.close_at DESC";
    $stmt = $pdo->prepare($song_query);
    $stmt->bindParam(":account_id", $account_id, PDO::PARAM_INT);
    $stmt->bindParam(":case_recover_days", $case_recover_days, PDO::PARAM_INT);
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
    <title>กู้คืนเพลง</title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function enableButtons() {
            document.getElementById('recoverButton').disabled = false;
            document.getElementById('viewButton').disabled = false;
        }

        function recover() {
            const selectedCaseId = document.querySelector('input[name="songSelect"]:checked').value;

            if (confirm("Are you sure you want to recover this case?")) {
                fetch("recover_case.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ case_id: selectedCaseId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Case recovered successfully.");
                        window.location.reload();
                    } else {
                        alert("Failed to recover case. " + data.message);
                    }
                });
            }
        }

        function viewCase() {
            const selectedCaseId = document.querySelector('input[name="songSelect"]:checked').value;

            const form = document.createElement("form");
            form.method = "POST";
            form.action = "view_case.php";

            const caseInput = document.createElement("input");
            caseInput.type = "hidden";
            caseInput.name = "case_id";
            caseInput.value = selectedCaseId;

            const refInput = document.createElement("input");
            refInput.type = "hidden";
            refInput.name = "ref_page";
            refInput.value = "recover";

            form.appendChild(caseInput);
            form.appendChild(refInput);
            document.body.appendChild(form);
            form.submit();
        }

    </script>
</head>
<body>
    <div class="top-section">
        <h2>กู้คืนเพลง</h2>
        <div>
            <button onclick="location.href='qc.html'">ย้อนกลับ</button>
            <button id="recoverButton" onclick="recover()" disabled>กู้คืน</button>
            <button id="viewButton" onclick="viewCase()" disabled>ดูเนื้อเพลง</button>
        </div>
    </div>

    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <table>
                <thead>
                    <tr>
                        <th>เลือก</th>
                        <th>นักปรับแต่งเพลง</th>
                        <th>ชื่อเพลง</th>
                        <th>สถานที่แต่งเพลง</th>
                        <th>วันที่แก้ไขเพลงสำเร็จ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($songs as $song): ?>
                        <tr>
                            <td><input type="radio" name="songSelect" value="<?= $song['case_id'] ?>" onchange="enableButtons()"></td>
                            <td><?= htmlspecialchars($song['fixer_name']) ?></td>
                            <td><?= htmlspecialchars($song['case_title']) ?></td>
                            <td><?= htmlspecialchars($song['place']) ?></td>
                            <td><?= htmlspecialchars($song['close_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <img src="../pic/pao_logo2.webp" alt="Pao Logo">
        </div>
    </div>
</body>
</html>
