<?php
session_start();

include("db_connection.php");

// Fetch language code from the database
$lang_code = 'eng'; // Default
if (isset($_SESSION['lang'])) {
    try {
        $lang_query = "SELECT code FROM language WHERE id = :lang_id";
        $lang_stmt = $pdo->prepare($lang_query);
        $lang_stmt->bindParam(":lang_id", $_SESSION['lang'], PDO::PARAM_INT);
        $lang_stmt->execute();
        $lang_code = $lang_stmt->fetchColumn() ?: $lang_code;
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
        exit();
    }
}

// Load the language file
$jsonPath = "../lang/$lang_code/add_cause.json";
$lang = json_decode(file_get_contents($jsonPath), true);

// Check if user is logged in and authorized
if (!isset($_SESSION['user_id']) || !isset($_POST['case_id'])) {
    echo "<script>alert('" . $lang['unauthorized_access'] . "'); window.location.href = 'cause.php';</script>";
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
            COALESCE(u.user_name, '" . $lang['default_user'] . "') AS user_name, 
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
        throw new Exception($lang['case_not_found']);
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
    <title><?= $lang['title'] ?></title>
    <link rel="stylesheet" href="../style/basic.css">
    <script>
        function updateCause() {
            const dropdown = document.getElementById("causeDropdown").value;
            const textInput = document.getElementById("customCause").value.trim();

            if (!dropdown && !textInput) {
                alert("<?= $lang['cause_required'] ?>");
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
                    alert("<?= $lang['success_message'] ?>");
                    window.location.href = "cause.php";
                } else {
                    alert("<?= $lang['error_message'] ?>" + (result.error || "<?= $lang['unknown_error'] ?>"));
                }
            })
            .catch(error => {
                alert("<?= $lang['error_occurred'] ?>" + error.message);
            });
        }
    </script>
</head>
<body>
    <div class="top-section">
        <h2><?= $lang['title'] ?></h2>
        <button onclick="location.href='cause.php'"><?= $lang['back_button'] ?></button>
    </div>

    <div class="content">
        <div class="left-section">
            <div class="input-group">
                <label for="causeDropdown"><?= $lang['select_cause'] ?>:</label>
                <select id="causeDropdown" class="short-dropdown">
                    <option value=""><?= $lang['select_cause_placeholder'] ?></option>
                    <?php foreach ($causes as $cause): ?>
                        <option value="<?= htmlspecialchars($cause['cause']) ?>"><?= htmlspecialchars($cause['cause']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="customCause"><?= $lang['or_enter_cause'] ?></label>
                <input type="text" id="customCause" placeholder="<?= $lang['enter_cause_placeholder'] ?>">
            </div>

            <div class="button-group">
                <button onclick="updateCause()"><?= $lang['confirm_button'] ?></button>
            </div>

            <div class="case-details">
                <h3><?= $lang['case_details'] ?></h3>
                <p><strong><?= $lang['case_title'] ?>:</strong> <?= htmlspecialchars($song['case_title']) ?></p>
                <p><strong><?= $lang['user_name'] ?>:</strong> <?= htmlspecialchars($song['user_name']) ?></p>
                <p><strong><?= $lang['place'] ?>:</strong> <?= htmlspecialchars($song['place']) ?></p>

                <table>
                    <tr>
                        <th><?= $lang['state'] ?></th>
                        <th><?= $lang['date'] ?></th>
                        <th><?= $lang['detail'] ?></th>
                        <th><?= $lang['attached'] ?></th>
                    </tr>
                    <tr>
                        <td>Create</td>
                        <td><?= htmlspecialchars($song['created_date']) ?></td>
                        <td><?= htmlspecialchars($song['detail']) ?></td>
                        <td><button disabled><?= $lang['picture'] ?></button> <button disabled><?= $lang['file'] ?></button></td>
                    </tr>
                    <?php foreach ($updates as $update): ?>
                        <tr>
                            <td>Update <?= htmlspecialchars($update['update_no']) ?></td>
                            <td><?= htmlspecialchars($update['date']) ?></td>
                            <td><?= htmlspecialchars($update['update_detail']) ?></td>
                            <td><button disabled><?= $lang['picture'] ?></button> <button disabled><?= $lang['file'] ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <div class="right-section">
            <img src="../pic/chuch_2.webp" alt="Chuch Image">
        </div>
    </div>
</body>
</html>
