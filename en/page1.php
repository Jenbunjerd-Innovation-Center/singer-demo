<!-- page1.php -->
<?php
session_start();
$_SESSION['x'] = 1; // Create session variable 'x' with value 1
header("Location: page2.php"); // Redirect to page2.html
exit();
?>
