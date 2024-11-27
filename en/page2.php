<!-- page2.html -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page 2</title>
</head>
<body>
    <h1>Page 2</h1>

    <?php
    session_start();
    if (isset($_SESSION['x'])) {
        echo "<p>The value of x is: " . $_SESSION['x'] . "</p>";
    } else {
        echo "<p>Session variable 'x' is not set.</p>";
    }
    ?>

    <form action="page1.html" method="get">
        <button type="submit">Go back to Page 1</button>
    </form>
</body>
</html>
