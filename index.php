<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: /PROJECT/login.php");
    exit;
}

header("Location: /PROJECT/dashboard/dashboard.php");
exit;

