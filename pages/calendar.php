
<?php
require_once "../includes/auth.php";
require_once "../includes/functions.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];

// Selected month
$month = isset($_GET["month"]) ? (int) $_GET["month"] : (int) date("n");
$year = isset($_GET["year"]) ? (int) $_GET["year"] : (int) date("Y");

// Validate month
if ($month < 1 || $month > 12) {
    $month = (int) date("n");
}

// Normalize year/month
$selectedDate = new DateTime("$year-$month-01");
$month = (int) $selectedDate->format("n");
$year = (int) $selectedDate->format("Y");

$monthName = $selectedDate->format("F");
$daysInMonth = (int) $selectedDate->format("t");
$firstDayOfWeek = (int) $selectedDate->format("w");

// Previous and next month
$previousMonth = (clone $selectedDate)->modify("-1 month");
$nextMonth = (clone $selectedDate)->modify("+1 month");

// Fetch tasks with due dates in selected month
$startDate = $selectedDate->format("Y-m-01");
$endDate = $selectedDate->format("Y-m-t");

$sql = "SELECT id, title, status, priority, due_date
        FROM tasks
        WHERE user_id = :user_id
        AND due_date BETWEEN :start_date AND :end_date
        ORDER BY due_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    "user_id" => $userId,
    "start_date" => $startDate,
    "end_date" => $endDate
]);

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group tasks by date
$tasksByDate = [];

foreach ($tasks as $task) {
    $date = $task["due_date"];

    if (!isset($tasksByDate[$date])) {
        $tasksByDate[$date] = [];
    }

    $tasksByDate[$date][] = $task;
}

// Weekday names
$weekdays = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Calendar | BloomTask</title>

    <link rel="icon" type="image/svg+xml"
          href="../assets/images/favicon.svg">

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="app-container">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">

        <!-- Header -->
        <div class="page-header">
            <div class="page-header-top">
                <div>
                    <h1>Calendar</h1>
                    <p class="page-subtitle">
                        Keep track of your deadlines and plans.
                    </p>
                </div>

                <a href="tasks.php" class="btn btn-primary">
                    + View Tasks
                </a>
            </div>
        </div>

        <!-- Calendar Summary -->
        <div class="card-grid">

            <div class="stat-card">
                <div class="stat-label">Monthly Tasks</div>
                <div class="stat-value">
                    <?= count($tasks) ?>
                </div>
                <div class="stat-description">
                    Tasks with deadlines this month
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Pending</div>
                <div class="stat-value">
                <?php
                echo count(array_filter($tasks, function ($task) {
                    return $task["status"] === "Pending";
                }));
                ?>
                </div>
                <div class="stat-description">
                    Still need to be completed
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Completed</div>
                <div class="stat-value">
                <?php
                echo count(array_filter($tasks, function ($task) {
                    return $task["status"] === "Completed";
                }));
                ?>
                </div>
                <div class="stat-description">
                    Finished this month
                </div>
            </div>

        </div>

        <!-- Calendar -->
        <div class="calendar-container">

            <div class="calendar-header">

                <a class="calendar-nav"
                   href="calendar.php?month=<?= $previousMonth->format("n") ?>&year=<?= $previousMonth->format("Y") ?>">
                    ‹
                </a>

                <h2><?= e($monthName) ?> <?= $year ?></h2>

                <a class="calendar-nav"
                   href="calendar.php?month=<?= $nextMonth->format("n") ?>&year=<?= $nextMonth->format("Y") ?>">
                    ›
                </a>

            </div>

            <div class="calendar-weekdays">
                <?php foreach ($weekdays as $weekday): ?>
                    <div><?= $weekday ?></div>
                <?php endforeach; ?>
            </div>

            <div class="calendar-grid">

                <?php
                // Empty cells before the first day
                for ($i = 0; $i < $firstDayOfWeek; $i++):
                ?>
                    <div class="calendar-day empty-day"></div>
                <?php endfor; ?>

                <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>

                    <?php
                    $dateKey = sprintf("%04d-%02d-%02d", $year, $month, $day);
                    $isToday = $dateKey === date("Y-m-d");
                    $dayTasks = $tasksByDate[$dateKey] ?? [];
                    ?>

                    <div class="calendar-day <?= $isToday ? "today" : "" ?>">

                        <div class="calendar-day-number">
                            <?= $day ?>
                            <?php if ($isToday): ?>
                                <span class="today-label">Today</span>
                            <?php endif; ?>
                        </div>

                        <div class="calendar-day-tasks">

                            <?php foreach (array_slice($dayTasks, 0, 3) as $task): ?>

                                <a href="tasks.php"
                                   class="calendar-task <?= $task["status"] === "Completed" ? "completed-task" : "pending-task" ?>"
                                   title="<?= e($task["title"]) ?>">

                                    <?= e($task["title"]) ?>

                                </a>

                            <?php endforeach; ?>

                            <?php if (count($dayTasks) > 3): ?>
                                <div class="calendar-more">
                                    +<?= count($dayTasks) - 3 ?> more
                                </div>
                            <?php endif; ?>

                        </div>

                    </div>

                <?php endfor; ?>

            </div>

            <!-- Legend -->
            <div class="calendar-legend">

                <div>
                    <span class="legend-dot pending-dot"></span>
                    Pending
                </div>

                <div>
                    <span class="legend-dot completed-dot"></span>
                    Completed
                </div>

                <div>
                    <span class="legend-dot today-dot"></span>
                    Today
                </div>

            </div>

        </div>

    </main>

</div>

<script src="../assets/js/script.js"></script>

</body>
</html>
