<?php
/* =========================================
   BLOOMTASK - DASHBOARD
========================================= */

require_once "../includes/auth.php";
require_once "../includes/functions.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];
$username = $_SESSION["username"] ?? "User";

$today = date("Y-m-d");

// =========================================
// TASK STATISTICS
// =========================================

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Completed') AS completed,
        SUM(status = 'Pending') AS pending,
        SUM(status = 'Pending' AND due_date < :today) AS overdue
    FROM tasks
    WHERE user_id = :user_id
");

$stmt->execute([
    "today" => $today,
    "user_id" => $userId
]);

$stats = $stmt->fetch();

$totalTasks = (int) ($stats["total"] ?? 0);
$completedTasks = (int) ($stats["completed"] ?? 0);
$pendingTasks = (int) ($stats["pending"] ?? 0);
$overdueTasks = (int) ($stats["overdue"] ?? 0);

$completionRate = $totalTasks > 0
    ? round(($completedTasks / $totalTasks) * 100)
    : 0;

// =========================================
// RECENT TASKS
// =========================================

$stmt = $pdo->prepare("
    SELECT *
    FROM tasks
    WHERE user_id = :user_id
    ORDER BY created_at DESC
    LIMIT 5
");

$stmt->execute(["user_id" => $userId]);
$recentTasks = $stmt->fetchAll();

// =========================================
// UPCOMING TASKS
// =========================================

$stmt = $pdo->prepare("
    SELECT *
    FROM tasks
    WHERE user_id = :user_id
      AND status = 'Pending'
      AND due_date >= :today
    ORDER BY due_date ASC
    LIMIT 5
");

$stmt->execute([
    "user_id" => $userId,
    "today" => $today
]);

$upcomingTasks = $stmt->fetchAll();

// =========================================
// WEEKLY PRODUCTIVITY
// =========================================

$weekLabels = [];
$weekValues = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date("Y-m-d", strtotime("-$i days"));

    $weekLabels[] = date("D", strtotime($date));

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tasks
        WHERE user_id = :user_id
          AND status = 'Completed'
          AND DATE(completed_at) = :date
    ");

    $stmt->execute([
        "user_id" => $userId,
        "date" => $date
    ]);

    $weekValues[] = (int) $stmt->fetchColumn();

}

$weekLabelsJSON = json_encode($weekLabels);
$weekValuesJSON = json_encode($weekValues);

// =========================================
// GREETING
// =========================================

$hour = (int) date("H");

if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 18) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | BloomTask</title>

    <link rel="icon" type="image/svg+xml"
          href="../assets/images/favicon.svg">

    <link rel="stylesheet"
          href="../assets/css/style.css">

</head>

<body>

<div class="app-layout">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">

        <div class="page-container">

            <!-- =================================
                 PAGE HEADER
            ================================== -->

            <div class="page-header">

                <div class="page-header-top">

                    <button type="button"
                            class="mobile-menu-button"
                            id="mobileMenuButton">
                        ☰
                    </button>

                    <div>
                        <p class="page-subtitle">
                            <?= date("l, d F Y") ?>
                        </p>

                        <h1>
                            <?= e($greeting) ?>,
                            <span class="text-gold">
                                <?= e($username) ?>
                            </span>
                        </h1>

                        <p>
                            A new day is another opportunity
                            to accomplish something meaningful.
                        </p>
                    </div>

                </div>

                <div>
                    <a href="tasks.php"
                       class="btn btn-primary">
                        + Add New Task
                    </a>
                </div>

            </div>

            <!-- =================================
                 STATISTICS
            ================================== -->

            <section class="card-grid">

                <div class="stat-card">
                    <div class="stat-label">Total Tasks</div>

                    <div class="stat-value">
                        <?= $totalTasks ?>
                    </div>

                    <div class="stat-description">
                        All your tasks
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Completed</div>

                    <div class="stat-value">
                        <?= $completedTasks ?>
                    </div>

                    <div class="stat-description">
                        Successfully finished
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Pending</div>

                    <div class="stat-value">
                        <?= $pendingTasks ?>
                    </div>

                    <div class="stat-description">
                        Awaiting completion
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Overdue</div>

                    <div class="stat-value text-danger">
                        <?= $overdueTasks ?>
                    </div>

                    <div class="stat-description">
                        Past their due date
                    </div>
                </div>

            </section>

            <!-- =================================
                 PRODUCTIVITY + CHART
            ================================== -->

            <section class="dashboard-split">

                <div class="card">

                    <div class="card-header">
                        <h3>Productivity</h3>
                    </div>

                    <div class="text-center">

                        <div class="stat-value"
                             style="font-size: 48px;">
                            <?= $completionRate ?>%
                        </div>

                        <p>
                            Overall completion rate
                        </p>

                    </div>

                    <div class="progress mt-2">
                        <div class="progress-bar"
                             style="width: <?= $completionRate ?>%;">
                        </div>
                    </div>

                    <p class="mt-2">
                        <?= $completedTasks ?> of
                        <?= $totalTasks ?> tasks completed.
                    </p>

                </div>

                <div class="card">

                    <div class="card-header">
                        <h3>Weekly Productivity</h3>

                        <span class="badge badge-gold">
                            Last 7 Days
                        </span>
                    </div>

                    <div class="chart-container">
                        <canvas id="weeklyChart"></canvas>
                    </div>

                </div>

            </section>

            <!-- =================================
                 RECENT TASKS
                 ================================== -->

            <section class="card mb-3">

                <div class="card-header">

                    <h2>Recent Tasks</h2>

                    <a href="tasks.php"
                       class="btn btn-secondary btn-sm">
                        View All
                    </a>

                </div>

                <?php if (empty($recentTasks)): ?>

                    <div class="empty-state">

                        <div class="empty-icon">✦</div>

                        <h3>Your journey begins here</h3>

                        <p>
                            Create your first task and start
                            organising your day.
                        </p>

                        <a href="tasks.php"
                           class="btn btn-primary">
                            Create First Task
                        </a>

                    </div>

                <?php else: ?>

                    <div class="table-wrapper">

                        <table class="table">

                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php foreach ($recentTasks as $task): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?= e($task["title"]) ?>
                                        </strong>

                                    </td>

                                    <td>
                                        <?= e($task["category"] ?: "Others") ?>
                                    </td>

                                    <td>

                                        <?php
                                        $priorityClass = "badge-gold";

                                        if ($task["priority"] === "High") {
                                            $priorityClass = "badge-danger";
                                        } elseif ($task["priority"] === "Low") {
                                            $priorityClass = "badge-success";
                                        }
                                        ?>

                                        <span class="badge <?= $priorityClass ?>">
                                            <?= e($task["priority"]) ?>
                                        </span>

                                    </td>

                                    <td>
                                        <?php if ($task["status"] === "Completed"): ?>

                                            <span class="badge badge-success">
                                                Completed
                                            </span>

                                        <?php else: ?>

                                            <span class="badge badge-warning">
                                                Pending
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <?= e($task["due_date"] ?: "No date") ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </section>

            <!-- =================================
                 UPCOMING DEADLINES
                 ================================== -->

            <section class="card">

                <div class="card-header">

                    <h2>Upcoming Deadlines</h2>

                    <a href="calendar.php"
                       class="btn btn-secondary btn-sm">
                        View Calendar
                    </a>

                </div>

                <?php if (empty($upcomingTasks)): ?>

                    <div class="empty-state">

                        <div class="empty-icon">♧</div>
                        
                        <h3>Nothing due soon</h3>

                        <p>
                            Enjoy your breathing space
                            or plan your next task.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="task-list">

                        <?php foreach ($upcomingTasks as $task): ?>

                            <div class="task-item">

                                <div class="avatar">✦</div>

                                <div class="task-details">

                                    <div class="task-title">
                                        <?= e($task["title"]) ?>
                                    </div>

                                    <div class="task-meta">

                                        <?= e($task["category"] ?: "Others") ?>

                                        &nbsp;·&nbsp;

                                        <?= e($task["due_date"]) ?>

                                    </div>

                                </div>

                                <span class="badge badge-gold">
                                    <?= e($task["priority"]) ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </main>

</div>

<!-- =================================
     CHART.JS
================================== -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="../assets/js/script.js"></script>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const chartCanvas = document.getElementById("weeklyChart");

    if (chartCanvas && typeof Chart !== "undefined") {

        const labels = <?= $weekLabelsJSON ?>;
        const values = <?= $weekValuesJSON ?>;

        const gold = getComputedStyle(document.documentElement)
            .getPropertyValue("--gold").trim();

        const muted = getComputedStyle(document.documentElement)
            .getPropertyValue("--text-muted").trim();

        new Chart(chartCanvas, {

            type: "bar",

            data: {
                labels: labels,

                datasets: [{
                    label: "Completed Tasks",
                    data: values,
                    backgroundColor: gold,
                    borderRadius: 6,
                    barThickness: 22
                }]
            },

            options: {

                responsive: true,
                maintainAspectRatio: false,

                plugins: {
                    legend: {
                        display: false
                    }
                },

                scales: {

                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: muted
                        }
                    },

                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: muted
                        },
                        grid: {
                            color: "rgba(150,150,150,0.10)"
                        }
                    }

                }

            }

        });

    }

});

</script>

</body>
</html>