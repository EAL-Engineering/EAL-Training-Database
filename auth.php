<?php
// auth.php
session_start();

function check_access($required_role) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] < $required_role) {
        header("Location: login.php");
        exit;
    }
}
?>
