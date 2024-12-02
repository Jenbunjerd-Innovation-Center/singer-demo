<?php
session_start();
include("db_connection.php");

// Fetch language code based on session lang
$lang_code = 'eng'; // Default
if (isset($_SESSION['lang'])) {
    try {
        $stmt = $pdo->prepare("SELECT code FROM language WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['lang']]);
        $lang_code = $stmt->fetchColumn() ?: 'eng';
    } catch (PDOException $e) {
        $lang_code = 'eng';
    }
}

// Load translations
$json_path = "../lang/{$lang_code}/feedback.json";
$translations = [];
if (file_exists($json_path)) {
    $translations = json_decode(file_get_contents($json_path), true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($translations['title'] ?? 'Feedback') ?></title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function confirmSave() {
            const name = document.getElementById('reporter_name').value.trim();
            const detail = document.getElementById('detail').value.trim();

            if (!name || !detail) {
                alert("<?= $translations['error_required'] ?? 'Both name and detail are required.' ?>");
                return;
            }

            if (confirm(`<?= $translations['confirm_save'] ?? 'Do you want to save the feedback?' ?>\n\n<?= $translations['label_name'] ?? 'Name' ?>: ${name}\n<?= $translations['label_detail'] ?? 'Detail' ?>: ${detail}`)) {
                const formData = new FormData();
                formData.append('name', name);
                formData.append('detail', detail);
                const fileInput = document.getElementById('file_upload');
                if (fileInput.files.length > 0) {
                    formData.append('file', fileInput.files[0]);
                }

                fetch('save_feedback.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("<?= $translations['success_save'] ?? 'Feedback saved successfully.' ?>");
                        location.href = 'main_page.php';
                    } else {
                        alert("<?= $translations['error_save'] ?? 'Error saving feedback:' ?> " + data.error);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("<?= $translations['error_unexpected'] ?? 'An unexpected error occurred.' ?>");
                });
            }
        }
    </script>
</head>
<body>
    <!-- Top Section -->
    <div class="top-section">
        <h2><?= htmlspecialchars($translations['title'] ?? 'Feedback') ?></h2>
        <div>
            <button onclick="location.href='main_page.php'"><?= htmlspecialchars($translations['button_back'] ?? 'Back') ?></button>
            </div>
    </div>

    <!-- Content Layout -->
    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <input type="text" id="reporter_name" placeholder="<?= htmlspecialchars($translations['placeholder_name'] ?? 'Reporter Name') ?>" required><br><br>
            <textarea id="detail" placeholder="<?= htmlspecialchars($translations['placeholder_detail'] ?? 'Detail') ?>" required></textarea><br><br>
            <input type="file" id="file_upload"><br><br>
            <button onclick="confirmSave()"><?= htmlspecialchars($translations['button_save'] ?? 'Save') ?></button>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <img src="../pic/sri_whistle.png" alt="Whistle">
        </div>
    </div>
</body>
</html>
