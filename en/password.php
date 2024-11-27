<?php
    $hashedPassword = password_hash("s123", PASSWORD_BCRYPT);
    echo $hashedPassword;
?>