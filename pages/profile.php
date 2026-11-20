
<?php
require_once "../includes/auth.php";
require_once "../includes/functions.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];

$stmt = $pdo->prepare("SELECT id, username, email, created_at FROM users WHERE id = :id");
$stmt->execute(["id" => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();

    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($username === "" || $email === "") {
        $error = "Username and email are required.";
    } elseif (strlen($username) > 50) {
        $error = "Username cannot exceed 50 characters.";
    } elseif (strlen($email) > 100) {
        $error = "Email cannot exceed 100 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $check = $pdo->prepare(
            "SELECT id FROM users
             WHERE (username = :username OR email = :email)
             AND id != :id"
        );

        $check->execute([
            "username" => $username,
            "email" => $email,
            "id" => $userId
        ]);

        if ($check->fetch()) {
            $error = "Username or email is already being used.";
        } else {
            $update = $pdo->prepare(
                "UPDATE users
                 SET username = :username, email = :email
                 WHERE id = :id"
            );

            $update->execute([
                "username" => $username,
                "email" => $email,
                "id" => $userId
            ]);

            $_SESSION["username"] = $username;

            set_flash("success", "Profile updated successfully!");
            redirect("profile.php");
        }
    }

    // Keep entered values if validation fails
    $user["username"] = $username;
    $user["email"] = $email;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | BloomTask</title>

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
                <h1>My Profile</h1>
                <p class="page-subtitle">
                    Manage your personal account information.
                </p>
            </div>
        </div>

        <?php if ($flash = get_flash()): ?>
            <div class="alert alert-<?= e($flash["type"]) ?>">
                <?= e($flash["message"]) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="alert alert-error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="profile-layout">

            <!-- Profile Summary -->
            <div class="card profile-summary">

                <div class="profile-avatar">
                    <?= strtoupper(substr($user["username"], 0, 1)) ?>
                </div>

                <h2><?= e($user["username"]) ?></h2>

                <p class="profile-email">
                    <?= e($user["email"]) ?>
                </p>

                <div class="profile-divider"></div>

                <div class="profile-info-row">
                    <span>Account Created</span>
                    <strong>
                        <?= date("d M Y", strtotime($user["created_at"])) ?>
                    </strong>
                </div>

                <div class="profile-info-row">
                    <span>Account ID</span>
                    <strong>#<?= (int) $user["id"] ?></strong>
                </div>

            </div>

            <!-- Edit Profile -->
            <div class="card profile-edit-card">

                <div class="card-header">
                    <h2>Edit Profile</h2>
                </div>

                <form method="POST">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= e(csrf_token()) ?>">

                    <div class="form-group">
                        <label class="form-label" for="username">
                            Username
                        </label>

                        <input type="text"
                               class="form-control"
                               id="username"
                               name="username"
                               maxlength="50"
                               value="<?= e($user["username"]) ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">
                            Email Address
                        </label>

                        <input type="email"
                               class="form-control"
                               id="email"
                               name="email"
                               maxlength="100"
                               value="<?= e($user["email"]) ?>"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>

                </form>

            </div>

        </div>

    </main>

</div>

<script src="../assets/js/script.js"></script>

</body>
</html>
