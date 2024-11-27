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
        <h2>Welcome to the Main Page</h2>
        <h3>Welcome, <?php echo $_SESSION['user_name']; ?></h3>
    </div>

    <div class="content">
        <div class="left-section">
            <p><button id="singerButton" onclick="location.href='singer.php'">Singer</button></p>
            <p><button id="fixerButton" onclick="location.href='fixer.php'">Fixer (Guitarist)</button></p>
            <p><button id="qcButton" onclick="location.href='qc.html'">QC (Drummer)</button></p>
            <p><button onclick="location.href='report.html'">Report</button></p>
            <p><button onclick="location.href='feedback.html'">Feedback</button></p>
            <p><button onclick="location.href='logout.php'">Logout</button></p>
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
