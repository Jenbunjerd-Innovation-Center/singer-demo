<?php session_start();
include("db_connection.php"); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Singer Page</title>
    <link rel="stylesheet" href="../style/basic.css">
</head>

<body>
    <?php
    // Fetch language file based on user's session language
    // Fetch the user's language ID from the session
    $lang_id = $_SESSION['lang']; // Assuming lang is stored in the session

    // Default language code
    $lang_code = 'eng'; // Fallback language code

    try {
        // Fetch the language code from the language table using the lang ID
        $langQuery = "SELECT code FROM language WHERE id = :lang_id";
        $langStmt = $pdo->prepare($langQuery);
        $langStmt->bindParam(':lang_id', $lang_id, PDO::PARAM_INT);
        $langStmt->execute();
        $lang_code = $langStmt->fetchColumn() ?: 'eng'; // Use 'eng' if no code is found
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
        exit();
    }

    // Construct the JSON path using the fetched language code
    $jsonPath = "../lang/$lang_code/singer.json";

    $translations = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];

    // Fetch status names from the database
    $statusNames = [];
    try {
        $statusQuery = "SELECT id, name FROM status";
        $statusStmt = $pdo->query($statusQuery);
        while ($statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
            $statusNames[$statusRow['id']] = $statusRow['name'];
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
    ?>

    <!-- Top Section -->
    <div class="top-section">
        <div class="top-left">
            <h2><?= $translations["pageTitle"] ?? "Singer Page"; ?></h2>
        </div>
        <div class="top-right">
            <button onclick="location.href='main_page.php'"><?= $translations["back"] ?? "Back"; ?></button>
            <button onclick="location.href='new_song.php'"><?= $translations["createNew"]; ?></button>

        </div>
    </div>

    <!-- Content Section -->
    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <div class="top-controls">
                <button id="viewButton" onclick="viewSong()" disabled><?= $translations["view"] ?? "View"; ?></button>
                <button id="cancelButton" onclick="songCancel()" disabled><?= $translations["cancel"] ?? "Cancel"; ?></button>
                <select id="toggleView" class="select-short" onchange="loadCases()">
                    <option value="my_current"><?= $translations["myCurrent"] ?? "My Current"; ?></option>
                    <option value="my_all"><?= $translations["myAll"] ?? "My All"; ?></option>
                    <option value="mask_current"><?= $translations["maskCurrent"] ?? "Mask Current"; ?></option>
                    <option value="mask_all"><?= $translations["maskAll"] ?? "Mask All"; ?></option>
                </select>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Select</th>
                        <th><?= $translations["user"] ?? "User"; ?></th>
                        <th><?= $translations["title"] ?? "Title"; ?></th>
                        <th><?= $translations["createDate"] ?? "Create Date"; ?></th>
                        <th><?= $translations["supporter"] ?? "Supporter"; ?></th>
                        <th><?= $translations["ackDate"] ?? "Acknowledge Date"; ?></th>
                        <th><?= $translations["status"] ?? "Status"; ?></th>
                    </tr>
                </thead>
                <tbody id="caseList"></tbody>
            </table>
        </div>
        <p id='debug'></p>
        <p id='debug2'></p>
        <!-- Right Section -->
        <div class="right-section">
            <img src="../pic/Sri_logo_1.webp" alt="Sri Logo">
            <img src="../pic/sri_1.png" alt="Sri 1">
        </div>
    </div>

    <script>
        const userId = <?php echo json_encode($_SESSION['user_id']); ?>;
        const langCode = <?php echo json_encode($lang_code); ?>;

        // Load cases based on dropdown selection
        function loadCases() {
            const viewType = document.getElementById('toggleView').value;
            const caseList = document.getElementById('caseList');
            caseList.innerHTML = '';
            //document.getElementById('debug').innerHTML=viewType;
            //document.getElementById('debug2').innerHTML=userId;
            fetch(`fetch_cases.php?viewType=${viewType}&userId=${userId}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(caseItem => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td><input type="radio" name="caseSelect" value="${caseItem.case_id}" onchange="updateButtonStates(${caseItem.status})"></td>
                            <td>${caseItem.user_name || "The Mask Singer"}</td>
                            <td>${caseItem.case_title}</td>
                            <td>${caseItem.created_at.split(' ')[0]}</td>
                            <td>${caseItem.fixer_name || "N/A"}</td>
                            <td>${caseItem.acc_at || "N/A"}</td>
                            <td>${getStatusText(caseItem.status)}</td>
                        `;
                        caseList.appendChild(row);
                    });
                });
        }

        // Fetch status text from PHP-generated statusNames array
        const statusNames = <?php echo json_encode($statusNames); ?>;

        function getStatusText(status) {
            return statusNames[status] || "Unknown";
        }

        function updateButtonStates(status) {
            document.getElementById('viewButton').disabled = false;
            document.getElementById('cancelButton').disabled = !(status === 0 || status === 1 || status === 2);
        }

        function viewSong() {
            const selectedCaseId = document.querySelector('input[name="caseSelect"]:checked').value;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'view_case.php';

            const caseInput = document.createElement('input');
            caseInput.type = 'hidden';
            caseInput.name = 'case_id';
            caseInput.value = selectedCaseId;

            const refInput = document.createElement('input');
            refInput.type = 'hidden';
            refInput.name = 'ref_page';
            refInput.value = 'singer';

            form.appendChild(caseInput);
            form.appendChild(refInput);
            document.body.appendChild(form);
            form.submit();
        }

        function songCancel() {
            const selectedCaseId = document.querySelector('input[name="caseSelect"]:checked').value;
            if (confirm("Do you want to cancel this case?")) {
                fetch('cancel_case.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            case_id: selectedCaseId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert("Case canceled successfully.");
                            loadCases();
                        } else {
                            alert("Failed to cancel case. Please try again.");
                        }
                    });
            }
        }

        // Initial load
        loadCases();
    </script>
</body>

</html>