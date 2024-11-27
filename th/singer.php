<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลักนักร้อง</title>
    <link rel="stylesheet" href="../style/basic.css">
    <style>
        .top-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: rgba(0, 0, 0, 0.8);
        }
        tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.1);
        }
        tr:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <!-- Top Section -->
    <div class="top-section">
        <div class="top-left">
            <h2>หน้าหลักนักร้อง</h2>
        </div>
        <div class="top-right">
            <button onclick="location.href='main_page.php'">ย้อนกลับ</button>
            <button onclick="location.href='new_song.php'">เพิ่มเพลงใหม่</button>
        </div>
    </div>


    <!-- Content Section -->
    <div class="content">
        <!-- Left Section -->
        <div class="left-section">
            <!-- Top Controls -->
            <div class="top-controls">
                <button id="viewButton" onclick="viewSong()" disabled>ดูเพลง</button>
                <button id="cancelButton" onclick="songCancel()" disabled>ยกเลิก</button>
                <select id="toggleView" class="select-short" onchange="loadCases()">
                    <option value="current">เพลงปัจจุบัน</option>
                    <option value="all_case">เพลงทั้งหมด</option>
                    <option value="all_user_case" id="allUserOption" hidden>เพลงของนักร้องทั้งหมด</option>
                </select>
            </div>

            <!-- Case List Table -->
            <table>
                <thead>
                    <tr>
                        <th></th>
                        <th>นักร้อง</th>
                        <th>ชื่อเพลง</th>
                        <th>วันที่แต่งเพลง</th>
                        <th>ผู้ช่วยปรับแต่งเพลง</th>
                        <th>วันที่ตอบรับเพลง</th>
                        <th>สถานะของเพลง</th>
                    </tr>
                </thead>
                <tbody id="caseList"></tbody>
            </table>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <img src="../pic/Sri_logo_1.webp" alt="Sri Logo">
            <img src="../pic/sri_1.png" alt="Sri 1">
        </div>
    </div>

    <script>
        const userRole = <?php echo json_encode($_SESSION['role']); ?>;
        const userId = <?php echo json_encode($_SESSION['user_id']); ?>;

        // Toggle "All User Case" option for admin only
        if (userRole === 4) {
            document.getElementById('allUserOption').hidden = false;
        }

        // Load cases and populate the table
        function loadCases() {
            const viewType = document.getElementById('toggleView').value;
            const caseList = document.getElementById('caseList');
            caseList.innerHTML = '';  // Clear current table rows

            fetch(`fetch_cases.php?viewType=${viewType}&userId=${userId}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(caseItem => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>
                                <input type="radio" name="caseSelect" value="${caseItem.case_id}" onchange="updateButtonStates(${caseItem.status})">
                            </td>
                            <td>${caseItem.user_name || "The Mask Singer"}</td>
                            <td>${caseItem.case_title}</td>
                            <td>${caseItem.created_at.split(' ')[0]}</td>
                            <td>${caseItem.fixer_name || 'N/A'}</td>
                            <td>${caseItem.acc_at || 'N/A'}</td>
                            <td>${getStatusText(caseItem.status)}</td>
                        `;
                        caseList.appendChild(row);
                    });
                });
        }

        // Update button states based on selected case status
        function updateButtonStates(status) {
            document.getElementById('viewButton').disabled = false;
            document.getElementById('cancelButton').disabled = !(status === 0 || status === 1 || status === 2);
        }

        // Map status codes to text
        function getStatusText(status) {
            const statusText = ["Create", "Acknowledge", "Ongoing", "Close", "Cancel", "Force Close"];
            return statusText[status] || "Unknown";
        }

        // View selected case
        function viewSong() {
            const selectedCaseId = document.querySelector('input[name="caseSelect"]:checked').value;

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
            refInput.value = "singer";

            form.appendChild(caseInput);
            form.appendChild(refInput);
            document.body.appendChild(form);
            form.submit();
        }


        // Cancel selected case
        function songCancel() {
            const selectedCaseId = document.querySelector('input[name="caseSelect"]:checked').value;
            if (confirm(`Do you want to cancel this case?`)) {
                fetch('cancel_case.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ case_id: selectedCaseId })
                }).then(response => response.json())
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
