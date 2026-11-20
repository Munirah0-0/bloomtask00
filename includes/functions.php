<?php
/* =========================================
   BLOOMTASK - CORE FUNCTIONS
========================================= */

// Safely display text
function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Redirect to another page
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Generate CSRF token
function csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

// Verify CSRF token
function verify_csrf() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $submittedToken = $_POST["csrf_token"] ?? "";
    $sessionToken = $_SESSION["csrf_token"] ?? "";

    if (
        empty($submittedToken) ||
        empty($sessionToken) ||
        !hash_equals($sessionToken, $submittedToken)
    ) {
        http_response_code(403);
        exit("Invalid security token. Please refresh the page.");
    }
}

// Store flash message
function set_flash($type, $message) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION["flash"] = [
        "type" => $type,
        "message" => $message
    ];
}

// Retrieve flash message
function get_flash() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION["flash"])) {
        return null;
    }

    $flash = $_SESSION["flash"];

    unset($_SESSION["flash"]);

    return $flash;
}
?>