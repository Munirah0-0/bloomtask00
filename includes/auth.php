<?php
/* =========================================
   BLOOMTASK - AUTHENTICATION
========================================= */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Check whether the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}
?>