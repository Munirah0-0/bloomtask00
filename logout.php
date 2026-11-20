<?php
session_start();

// Remove all session data
$_SESSION = [];

// Destroy current session
session_destroy();

// Return to login page
header("Location: login.php");
exit;
?>