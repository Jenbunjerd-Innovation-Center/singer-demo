<?php
session_start();
include("db_connection.php");

// Fetch language options from the database
$lang_options = [];
$user_lang = $_SESSION['lang'] ?? 1; // Default language ID

try {
    // Fetch all available languages
    $lang_query = "SELECT id, code FROM language";
    $lang_stmt = $pdo->prepare($lang_query);
    $lang_stmt->execute();
    $lang_options = $lang_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch the user's current language
    $user_lang_query = "SELECT lang FROM user WHERE user_id = :user_id";
    $user_lang_stmt = $pdo->prepare($user_lang_query);
    $user_lang_stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $user_lang_stmt->execute();
    $user_lang = $user_lang_stmt->fetchColumn() ?: $user_lang;
} catch (PDOException $e) {
    echo "<script>alert('Error fetching language options: " . $e->getMessage() . "');</script>";
}

// Update language in the database if changed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lang'])) {
    try {
        $new_lang = intval($_POST['lang']);
        $update_lang_query = "UPDATE user SET lang = :lang WHERE user_id = :user_id";
        $update_lang_stmt = $pdo->prepare($update_lang_query);
        $update_lang_stmt->bindParam(':lang', $new_lang, PDO::PARAM_INT);
        $update_lang_stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $update_lang_stmt->execute();

        $_SESSION['lang'] = $new_lang; // Update session variable
        header("Refresh:0"); // Refresh the page
        exit();
    } catch (PDOException $e) {
        echo "<script>alert('Error updating language: " . $e->getMessage() . "');</script>";
    }
}

// Fetch static text from the JSON file
$lang_code = array_column($lang_options, 'code', 'id')[$user_lang] ?? 'eng'; // Default to English
$json_file = "../lang/$lang_code/main_page.json";
$static_text = [];

if (file_exists($json_file)) {
    $static_text = json_decode(file_get_contents($json_file), true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $static_text['title']; ?></title>
    <link rel="stylesheet" href="../style/basic.css">
</head>
<body>
    <div class="top-section">
        <h2><?php echo $static_text['welcome']?></h2>
        <h3><?php echo $static_text['welcome_user'] ; ?> <?php echo $_SESSION['user_name']; ?></h3>
        <form method="POST" style="display: inline;">
            <select name="lang" onchange="this.form.submit()" class="short-dropdown">
                <?php foreach ($lang_options as $option): ?>
                    <option value="<?php echo $option['id']; ?>" <?php echo $option['id'] == $user_lang ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($option['code']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="content">
        <div class="left-section">
            <p><button id="singerButton" onclick="location.href='singer.php'"><?php echo $static_text['singer']; ?></button></p>
            <p><button id="fixerButton" onclick="location.href='fixer.php'"><?php echo $static_text['fixer']; ?></button></p>
            <p><button id="qcButton" onclick="location.href='qc.html'"><?php echo $static_text['qc']; ?></button></p>
            <p><button onclick="location.href='report.html'"><?php echo $static_text['report']; ?></button></p>
            <p><button onclick="location.href='feedback.html'"><?php echo $static_text['feedback'] ; ?></button></p>
            <p><button onclick="location.href='logout.php'"><?php echo $static_text['logout']?></button></p>
        </div>

        <div class="right-section">
            <a href="notice.html">
                <img src="../pic/sri_2.png" alt="<?php echo $static_text['image_alt']; ?>" class="logo">
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
