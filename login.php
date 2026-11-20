<?php
session_start();

require_once "config/db.php";
require_once "includes/functions.php";

if (isset($_SESSION["user_id"])) {
    redirect("pages/dashboard.php");
}

$error = "";
$username = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verify_csrf();

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {
        $error = "Please enter your username and password.";
    } else {

        $stmt = $pdo->prepare(
            "SELECT id, username, email, password
             FROM users
             WHERE username = :username
             LIMIT 1"
        );

        $stmt->execute([
            "username" => $username
        ]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password"])) {

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["email"] = $user["email"];

            redirect("pages/dashboard.php");

        } else {
            $error = "Incorrect username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | BloomTask</title>

    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="auth-page">

    <div class="auth-card">

        <div class="auth-brand">
    <img
        src="assets/images/bloomtask-logo.png"
        alt="BloomTask Logo"
        class="auth-logo"
        style="width: 110px !important; max-width: 110px !important; height: auto !important; display: block; margin: 0 auto 12px;"
    >

    <p>YOUR PERSONAL WORKSPACE</p>
</div>

        <h2>Welcome Back</h2>
        <p>Sign in to continue your journey.</p>

        <?php if ($error !== ""): ?>
            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        
<form method="POST" action="login.php">

    <input type="hidden"
           name="csrf_token"
           value="<?= e(csrf_token()) ?>">

    <!-- USERNAME -->
    <div class="form-group">
        <label class="form-label" for="username">
            Username
        </label>

        <input
            type="text"
            id="username"
            name="username"
            class="form-control"
            placeholder="Enter your username"
            value="<?= e($username) ?>"
            autocomplete="username"
            required
            autofocus
        >
    </div>

    

<!-- PASSWORD -->
<div class="form-group">
    <label class="form-label" for="password">Password</label>

    <div style="position:relative; width:100%;">
        <input
            type="password"
            id="password"
            name="password"
            class="form-control"
            placeholder="Enter your password"
            autocomplete="current-password"
            required
            style="width:100%; box-sizing:border-box; padding-right:55px;"
        >

        <button
            type="button"
            id="togglePassword"
            aria-label="Show password"
            title="Show password"
            style="
                position:absolute !important;
                right:14px !important;
                top:50% !important;
                left:auto !important;
                bottom:auto !important;
                transform:translateY(-50%) !important;
                width:28px !important;
                height:28px !important;
                min-width:28px !important;
                padding:0 !important;
                margin:0 !important;
                border:0 !important;
                background:transparent !important;
                box-shadow:none !important;
                color:var(--text-muted) !important;
                display:flex !important;
                align-items:center !important;
                justify-content:center !important;
                cursor:pointer !important;
                z-index:10 !important;
            "
        >
            <svg id="passwordEye"
                 xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 24 24"
                 width="21"
                 height="21"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="1.7"
                 stroke-linecap="round"
                 stroke-linejoin="round">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                <circle cx="12" cy="12" r="2.5"/>
            </svg>
        </button>
    </div>
</div>

    <button type="submit" class="btn btn-primary btn-block">
        Sign In
    </button>

</form>

        <div class="auth-footer">
            Don't have an account?
            <a href="register.php">Create Account</a>
        </div>

        <div class="auth-footer">
            <button type="button"
                    id="themeToggle"
                    class="btn btn-secondary btn-sm">
                <span id="themeIcon">☀</span>
                <span id="themeText">Light Mode</span>
            </button>
        </div>

    </div>

</div>

<script src="assets/js/script.js"></script>

</body>
</html>