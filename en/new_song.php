<?php
session_start();
include("db_connection.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('User not logged in. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
    exit();
}

// Fetch language code
$lang_code = '';
try {
    $lang_query = "SELECT code FROM language WHERE id = :lang_id";
    $lang_stmt = $pdo->prepare($lang_query);
    $lang_stmt->bindParam(":lang_id", $_SESSION['lang'], PDO::PARAM_INT);
    $lang_stmt->execute();
    $lang_code = $lang_stmt->fetchColumn();

    if (!$lang_code) {
        $lang_code = 'eng'; // Default to English if not found
    }
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch labels and text from JSON
$json_path = "../lang/{$lang_code}/new_song.json";
if (!file_exists($json_path)) {
    echo "<script>alert('Language file not found.'); window.location.href = 'main_page.php';</script>";
    exit();
}
$labels = json_decode(file_get_contents($json_path), true);

// Query account_id based on session user_id
$user_id = $_SESSION['user_id'];
try {
    $query = "SELECT account_id FROM user WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $account_id = $stmt->fetchColumn();

    if (!$account_id) {
        echo "<script>alert('Account not found. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
        exit();
    }
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Query location list
$locations = [];
try {
    $loc_query = "SELECT loc_id, loc_name FROM location WHERE account_id = :account_id";
    $loc_stmt = $pdo->prepare($loc_query);
    $loc_stmt->bindParam(":account_id", $account_id, PDO::PARAM_INT);
    $loc_stmt->execute();
    $locations = $loc_stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title><?= htmlspecialchars($labels['page_title']); ?></title>
    <link rel="stylesheet" href="../style/basic.css">

    <script>
        function toggleImage() {
            const imageElement = document.getElementById("dynamicImage");
            const isChecked = document.getElementById("toggle-mask").checked;
            imageElement.src = isChecked ? "../pic/the_mask_1.webp" : "../pic/Sri_logo_2.webp";
        }

        function my_submit() {
            const title = document.getElementById('title').value.trim();
            if (!title) {
                alert("<?= htmlspecialchars($labels['error_title_required']); ?>");
                return;
            }

            const loc1 = document.getElementById('loc1').value;
            const loc2 = document.getElementById('loc2').value.trim();
            const scene = loc1 || loc2;

            if (!scene) {
                alert("<?= htmlspecialchars($labels['error_location_required']); ?>");
                return;
            }

            const detail = document.getElementById('detail').value.trim();
            if (!detail) {
                alert("<?= htmlspecialchars($labels['error_detail_required']); ?>");
                return;
            }

            const isMaskSinger = document.getElementById('toggle-mask').checked;
            const mask = isMaskSinger ? 1 : 0;

            const formData = new FormData();
            formData.append("title", title);
            formData.append("scene", scene);
            formData.append("detail", detail);
            formData.append("mask", mask);

            fetch("new_song_handler.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert("<?= htmlspecialchars($labels['success_message']); ?>");
                    window.location.href = "singer.php";
                } else {
                    alert("<?= htmlspecialchars($labels['error_generic']); ?>: " + (result.error || "<?= htmlspecialchars($labels['error_unknown']); ?>"));
                }
            })
            .catch(error => {
                alert("<?= htmlspecialchars($labels['error_generic']); ?>: " + error.message);
            });
        }
    </script>
</head>
<body>

    <!-- Top Section with Title -->
    <div class="top-section">
        <h1><?= htmlspecialchars($labels['page_title']); ?></h1>
        <button onclick="window.location.href='singer.php'"><?= htmlspecialchars($labels['back_button']); ?></button>
    </div>

    <!-- Bottom Section with Left and Right Columns -->
    <div class="content">
        <!-- Left Section with Form Elements -->
        <div class="left-section">
            <form id="newSongForm" method="POST">
                <div class="input-group">
                    <label for="title"><?= htmlspecialchars($labels['case_title']); ?>:</label>
                    <input type="text" id="title" name="title" placeholder="<?= htmlspecialchars($labels['placeholder_title']); ?>" required>
                </div>

                <div class="input-group">
                    <label for="loc1"><?= htmlspecialchars($labels['location']); ?>:</label>
                    <select id="loc1" name="loc1" class="select-short">
                        <option value=""><?= htmlspecialchars($labels['select_location']); ?></option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= htmlspecialchars($location['loc_name']) ?>"><?= htmlspecialchars($location['loc_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="loc2"><?= htmlspecialchars($labels['or']); ?>:</label>
                    <input type="text" id="loc2" name="loc2" placeholder="<?= htmlspecialchars($labels['placeholder_location']); ?>">
                </div>

                <div class="input-group">
                    <label for="detail"><?= htmlspecialchars($labels['detail']); ?>:</label>
                    <textarea id="detail" name="detail" placeholder="<?= htmlspecialchars($labels['placeholder_detail']); ?>" required></textarea>
                </div>

                <div class="input-group">
                    <label for="toggle-mask"><?= htmlspecialchars($labels['mask_toggle']); ?>:</label>
                    <input type="checkbox" id="toggle-mask" name="toggle-mask" onchange="toggleImage()">
                </div>

                <div class="button-group">
                    <button type="button" onclick="my_submit()"><?= htmlspecialchars($labels['submit_button']); ?></button>
                </div>
            </form>
        </div>

        <!-- Right Section for Displaying the Image -->
        <div class="right-section">
            <img id="dynamicImage" src="../pic/Sri_logo_2.webp" alt="<?= htmlspecialchars($labels['dynamic_image_alt']); ?>">
        </div>
    </div>
</body>
</html>
