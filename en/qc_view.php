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

// Fetch language code
try {
    $langQuery = "SELECT code FROM language WHERE id = :lang_id";
    $langStmt = $pdo->prepare($langQuery);
    $langStmt->bindParam(":lang_id", $_SESSION['lang'], PDO::PARAM_INT);
    $langStmt->execute();
    $langCode = $langStmt->fetchColumn();
} catch (Exception $e) {
    echo "<script>alert('Error fetching language.');</script>";
    exit();
}

// Load translations
$langFile = "../lang/$langCode/qc_view.json";
if (file_exists($langFile)) {
    $translations = json_decode(file_get_contents($langFile), true);
} else {
    $translations = [];
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
        throw new Exception($translations['error_case_not_found'] ?? "Case not found.");
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
    echo "<script>alert('" . ($translations['error_database'] ?? "Database error") . ": " . $e->getMessage() . "');</script>";
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
    echo "<script>alert('" . ($translations['error_database'] ?? "Database error") . ": " . $e->getMessage() . "');</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCode) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translations['title'] ?? 'QC View' ?></title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function forceClose() {
            const reason = document.getElementById("forceCloseReason").value.trim();
            if (!reason) {
                alert("<?= $translations['error_force_close_reason'] ?? 'Please provide a reason for force closing.' ?>");
                return;
            }

            fetch("force_close_case.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ case_id: <?= json_encode($case_id) ?>, force_reason: reason }),
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert("<?= $translations['force_close_success'] ?? 'Case successfully force closed.' ?>");
                    window.location.href = "qc_new_song.php";
                } else {
                    alert("<?= $translations['error_prefix'] ?? 'Error:' ?> " + result.error);
                }
            })
            .catch(error => alert("<?= $translations['error_general'] ?? 'An error occurred:' ?> " + error.message));
        }

        function forceAcknowledge() {
            const fixer = document.getElementById("fixerDropdown").value;
            if (!fixer) {
                alert("<?= $translations['error_select_fixer'] ?? 'Please select a fixer.' ?>");
                return;
            }

            fetch("force_acknowledge_case.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ case_id: <?= json_encode($case_id) ?>, fixer: fixer }),
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert("<?= $translations['force_acknowledge_success'] ?? 'Case successfully force acknowledged.' ?>");
                    window.location.href = "qc_new_song.php";
                } else {
                    alert("<?= $translations['error_prefix'] ?? 'Error:' ?> " + result.error);
                }
            })
            .catch(error => alert("<?= $translations['error_general'] ?? 'An error occurred:' ?> " + error.message));
        }
    </script>
</head>
<body>
    <div class="top-section">
        <h2><?= $translations['header'] ?? 'QC View' ?></h2>
        <div>
            <button onclick="location.href='qc_new_song.php'"><?= $translations['back'] ?? 'Back' ?></button>
            
            
        </div>
    </div>
    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <div class="sub-div-top">
                <div class="sub-div-top-left">
                    <p><strong><?= $translations['case_title'] ?? 'Case Title:' ?></strong> <?= htmlspecialchars($case['case_title']) ?></p>
                    <p><strong><?= $translations['user'] ?? 'User:' ?></strong> <?= htmlspecialchars($case['user_name']) ?></p>
                    <p><strong><?= $translations['place'] ?? 'Place:' ?></strong> <?= htmlspecialchars($case['place']) ?></p>
                    <p><strong><?= $translations['status'] ?? 'Status:' ?></strong> <?= htmlspecialchars($case['status']) ?></p>
                    <p><strong><?= $translations['cause'] ?? 'Cause:' ?></strong> <?= htmlspecialchars($case['cause'] ?? $translations['not_applicable'] ?? 'N/A') ?></p>
                </div>
                <div class="sub-div-top-right">
                    <textarea id="forceCloseReason" placeholder="<?= $translations['force_close_reason_placeholder'] ?? 'Force close reason' ?>"></textarea>
                    <button onclick="forceClose()"><?= $translations['force_close'] ?? 'Force Close' ?></button><br><br>
                    <select id="fixerDropdown" class="short-dropdown">
                        <option value=""><?= $translations['select_fixer'] ?? 'Select Fixer' ?></option>
                        <?php foreach ($fixers as $fixer): ?>
                            <option value="<?= htmlspecialchars($fixer['user_id']) ?>"><?= htmlspecialchars($fixer['user_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="forceAcknowledge()"><?= $translations['force_acknowledge'] ?? 'Force Acknowledge' ?></button>
                </div>
            </div>

            <div class="sub-div-bot">
                <table>
                    <thead>
                        <tr>
                            <th><?= $translations['state'] ?? 'State' ?></th>
                            <th><?= $translations['date'] ?? 'Date' ?></th>
                            <th><?= $translations['detail'] ?? 'Detail' ?></th>
                            <th><?= $translations['attached'] ?? 'Attached' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= $translations['create'] ?? 'Create' ?></td>
                            <td><?= htmlspecialchars($case['created_date']) ?></td>
                            <td><?= htmlspecialchars($case['detail']) ?></td>
                            <td><button disabled><?= $translations['picture'] ?? 'Picture' ?></button> <button disabled><?= $translations['file'] ?? 'File' ?></button></td>
                        </tr>
                        <?php foreach ($updates as $update): ?>
                            <tr>
                                <td><?= $translations['update'] ?? 'Update' ?> <?= htmlspecialchars($update['update_no']) ?></td>
                                <td><?= htmlspecialchars($update['date']) ?></td>
                                <td><?= htmlspecialchars($update['update_detail']) ?></td>
                                <td><button disabled><?= $translations['picture'] ?? 'Picture' ?></button> <button disabled><?= $translations['file'] ?? 'File' ?></button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <img src="../pic/pao_mas2.webp" alt="<?= $translations['image_alt'] ?? 'Pao Image' ?>">
        </div>
    </div>

</body>
</html>
