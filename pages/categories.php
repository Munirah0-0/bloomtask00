
<?php
require_once "../includes/auth.php";
require_once "../includes/functions.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];

$categoryNames = ["Study", "Personal", "Work", "Others"];

// Get task counts for each category
$sql = "SELECT
            COALESCE(NULLIF(category, ''), 'Others') AS category_name,
            COUNT(*) AS total,
            SUM(status = 'Pending') AS pending,
            SUM(status = 'Completed') AS completed
        FROM tasks
        WHERE user_id = :user_id
        GROUP BY category_name";

$stmt = $pdo->prepare($sql);
$stmt->execute(["user_id" => $userId]);
$categoryResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare category data with default values
$categories = [];

foreach ($categoryNames as $categoryName) {
    $categories[$categoryName] = [
        "total" => 0,
        "pending" => 0,
        "completed" => 0
    ];
}

// Put database results into category data
foreach ($categoryResults as $result) {
    $name = $result["category_name"];

    if (isset($categories[$name])) {
        $categories[$name] = [
            "total" => (int) $result["total"],
            "pending" => (int) $result["pending"],
            "completed" => (int) $result["completed"]
        ];
    } else {
        // Unknown category names are counted under Others
        $categories["Others"]["total"] += (int) $result["total"];
        $categories["Others"]["pending"] += (int) $result["pending"];
        $categories["Others"]["completed"] += (int) $result["completed"];
    }
}

// Overall statistics
$totalTasks = 0;
$totalPending = 0;
$totalCompleted = 0;

foreach ($categories as $category) {
    $totalTasks += $category["total"];
    $totalPending += $category["pending"];
    $totalCompleted += $category["completed"];
}

$overallProgress = $totalTasks > 0
    ? round(($totalCompleted / $totalTasks) * 100)
    : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories | BloomTask</title>

    <link rel="icon" type="image/svg+xml"
          href="../assets/images/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="app-container">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-top">
                <div>
                    <h1>Categories</h1>
                    <p class="page-subtitle">
                        Organise your tasks and track your progress.
                    </p>
                </div>

                <a href="tasks.php" class="btn btn-primary">
                    + View Tasks
                </a>
            </div>
        </div>

        <!-- Overall Statistics -->
        <div class="card-grid">

            <div class="stat-card">
                <div class="stat-label">Total Tasks</div>
                <div class="stat-value">
                    <?= $totalTasks ?>
                </div>
                <div class="stat-description">
                    Across all categories
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Pending Tasks</div>
                <div class="stat-value">
                    <?= $totalPending ?>
                </div>
                <div class="stat-description">
                    Waiting to be completed
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Completed Tasks</div>
                <div class="stat-value">
                    <?= $totalCompleted ?>
                </div>
                <div class="stat-description">
                    Successfully completed
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Overall Progress</div>
                <div class="stat-value">
                    <?= $overallProgress ?>%
                </div>
                <div class="stat-description">
                    Tasks completed
                </div>
            </div>

        </div>

        <!-- Category Cards -->
        <div class="section-header">
            <h2>Your Categories</h2>
        </div>

        <div class="category-grid">

            <?php foreach ($categories as $name => $data): ?>

                <?php
                $progress = $data["total"] > 0
                    ? round(
                        ($data["completed"] / $data["total"]) * 100
                    )
                    : 0;
                ?>

                <div class="category-card">

                    <div class="category-card-header">
                        <div>
                            <span class="category-label">
                                CATEGORY
                            </span>

                            <h3>
                                <?= e($name) ?>
                            </h3>
                        </div>

                        <span class="category-number">
                            <?= $data["total"] ?>
                        </span>
                    </div>

                    <p class="category-description">
                        <?= $data["total"] ?> 
                        <?= $data["total"] === 1 ? "task" : "tasks" ?>
                        in this category
                    </p>

                    <div class="category-progress-info">
                        <span>Completion</span>
                        <span><?= $progress ?>%</span>
                    </div>

                    <div class="progress-track">
                        <div class="progress-fill"
                             style="width: <?= $progress ?>%;">
                        </div>
                    </div>

                    <div class="category-stats">
                        <div>
                            <span class="category-stat-number">
                                <?= $data["pending"] ?>
                            </span>
                            <span class="category-stat-label">
                                Pending
                            </span>
                        </div>

                        <div>
                            <span class="category-stat-number">
                                <?= $data["completed"] ?>
                            </span>
                            <span class="category-stat-label">
                                Completed
                            </span>
                        </div>
                    </div>

                    <a href="tasks.php?category=<?= urlencode($name) ?>"
                       class="btn btn-secondary category-button">
                        View Category →
                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    </main>

</div>

<script src="../assets/js/script.js"></script>

</body>
</html>
