<?php
require_once "includes/securite.php"; 

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid CSRF token");
}

session_unset();
session_destroy();
header("Location: auth/login.php");
exit;
