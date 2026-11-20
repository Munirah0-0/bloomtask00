<?php
session_start();

require_once "config/db.php";
require_once "includes/functions.php";

if (isset($_SESSION["user_id"])) {
    redirect("pages/dashboard.php");
}

$error = "";
$username = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verify_csrf();

    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    // Validate username
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error = "Username must be 3-30 characters and contain only letters, numbers or underscores.";
    }

    // Validate email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }

    // Validate password
    elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";
    }

    // Confirm password
    elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    }

    else {

        // Check whether username or email already exists
        $stmt = $pdo->prepare(
            "SELECT id
             FROM users
             WHERE username = :username OR email = :email
             LIMIT 1"
        );

        $stmt->execute([
            "username" => $username,
            "email" => $email
        ]);

        if ($stmt->fetch()) {
            $error = "Username or email is already registered.";
        } else {

            // Securely hash password
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Save user information into MySQL
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, email, password)
                 VALUES (:username, :email, :password)"
            );

            $stmt->execute([
                "username" => $username,
                "email" => $email,
                "password" => $hashedPassword
            ]);

            // Automatically log in the new user
            session_regenerate_id(true);

            $_SESSION["user_id"] = $pdo->lastInsertId();
            $_SESSION["username"] = $username;
            $_SESSION["email"] = $email;

            redirect("pages/dashboard.php");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | BloomTask</title>

    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="auth-page">

    <div class="auth-card">

        <div class="auth-brand">
            <div class="brand-mark">B</div>

            <h1>BloomTask</h1>
            <p>YOUR PERSONAL WORKSPACE</p>
        </div>

        <h2>Create Your Account</h2>
        <p>Begin organising your life with intention.</p>

        <?php if ($error !== ""): ?>
            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label class="form-label" for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    placeholder="Choose a username"
                    value="<?= e($username) ?>"
                    autocomplete="username"
                    minlength="3"
                    maxlength="30"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="Enter your email"
                    value="<?= e($email) ?>"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Minimum 8 characters"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">
                    Confirm Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="form-control"
                    placeholder="Repeat your password"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Create Account
            </button>

        </form>

        <div class="auth-footer">
            Already have an account?
            <a href="login.php">Sign In</a>
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