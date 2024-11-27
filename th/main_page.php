<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Page</title>
    <link rel="stylesheet" href="../style/basic.css">
</head>
<body>
    <div class="top-section">
        <h2>ยินดีต้อนรับสู่หน้าเมนูหลัก</h2>
        <h3>ยินดีต้อนรับ, คุณ <?php echo $_SESSION['user_name']; ?></h3>
    </div>

    <div class="content">
        <div class="left-section">
            <p><button id="singerButton" onclick="location.href='singer.php'">นักร้อง</button></p>
            <p><button id="fixerButton" onclick="location.href='fixer.php'">นักปรับแต่งเพลง</button></p>
            <p><button id="qcButton" onclick="location.href='qc.html'">ผู้คุมเสียงดนตรี</button></p>
            <p><button onclick="location.href='report.html'">รายงาน</button></p>
            <p><button onclick="location.href='feedback.html'">ฝากโน้ตข้อเสนอแนะ</button></p>
            <p><button onclick="location.href='logout.php'">ออกจากระบบ</button></p>
        </div>

        <div class="right-section">
            <a href="notice.html">
                <img src="../pic/sri_2.png" alt="Sri 2" class="logo">
            </a>
        </div>
    </div>

    <script>
        // User role-based button disabling
        const userRole = <?php echo json_encode($_SESSION['role']); ?>;
        if (userRole === 1) {
            document.getElementById('fixerButton').disabled = true;
            document.getElementById('qcButton').disabled = true;
        } else if (userRole === 2) {
            document.getElementById('qcButton').disabled = true;
        }
    </script>
</body>
</html>
