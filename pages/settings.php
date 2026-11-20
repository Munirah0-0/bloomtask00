
<?php
require_once "../includes/auth.php";
require_once "../includes/functions.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];

$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = :id");
$stmt->execute(["id" => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Settings | BloomTask</title>

    <link rel="icon" type="image/svg+xml"
          href="../assets/images/favicon.svg">

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="app-container">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Settings</h1>
                <p class="page-subtitle">
                    Personalise your BloomTask experience.
                </p>
            </div>
        </div>

        <div class="settings-container">

            <!-- APPEARANCE -->
            <div class="card settings-card">

                <div class="settings-heading">
                    <div class="settings-icon">◐</div>

                    <div>
                        <h2>Appearance</h2>
                        <p>Choose how BloomTask looks on your screen.</p>
                    </div>
                </div>

                <div class="theme-switch-buttons">

                    <button type="button"
                            class="theme-switch-btn"
                            data-theme-choice="light">
                        ☀ Light Mode
                    </button>

                    <button type="button"
                            class="theme-switch-btn"
                            data-theme-choice="dark">
                        ☾ Dark Mode
                    </button>

                </div>

                <p class="settings-note">
                    Your theme preference is saved automatically
                    on this browser.
                </p>

            </div>

            <!-- ACCOUNT -->
            <div class="card settings-card">

                <div class="settings-heading">
                    <div class="settings-icon">♙</div>

                    <div>
                        <h2>Account</h2>
                        <p>Manage your personal account information.</p>
                    </div>
                </div>

                <div class="settings-account-row">
                    <div>
                        <span class="settings-label">Username</span>
                        <strong><?= e($user["username"]) ?></strong>
                    </div>

                    <a href="profile.php" class="btn btn-secondary">
                        Edit Profile
                    </a>
                </div>

                <div class="settings-account-row">
                    <div>
                        <span class="settings-label">Email Address</span>
                        <strong><?= e($user["email"]) ?></strong>
                    </div>

                    <a href="profile.php" class="btn btn-secondary">
                        Edit
                    </a>
                </div>

            </div>

            <!-- PREFERENCES -->
            <div class="card settings-card">

                <div class="settings-heading">
                    <div class="settings-icon">⚙</div>

                    <div>
                        <h2>Preferences</h2>
                        <p>Personal preferences for your workspace.</p>
                    </div>
                </div>

                <div class="settings-account-row">
                    <div>
                        <span class="settings-label">Default Theme</span>
                        <strong id="current-theme-label">Dark Mode</strong>
                    </div>

                    <button type="button"
                            class="btn btn-secondary"
                            id="reset-theme">
                        Reset to Dark
                    </button>
                </div>

            </div>

        </div>

    </main>

</div>

<script src="../assets/js/script.js"></script>

</body>
</html>
