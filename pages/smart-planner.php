
<?php
require_once "../includes/auth.php";
require_once "../includes/functions.php";
require_once "../config/db.php";

$userId = (int) $_SESSION["user_id"];
$today = new DateTime("today");

/* =========================================
   SMART PRIORITY ENGINE
========================================= */

// Complete task directly from the planner
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verify_csrf();

    $taskId = (int) ($_POST["task_id"] ?? 0);

    if ($taskId > 0) {

        $stmt = $pdo->prepare("
            UPDATE tasks
            SET status = 'Completed',
                completed_at = NOW()
            WHERE id = :id
              AND user_id = :user_id
              AND status = 'Pending'
        ");

        $stmt->execute([
            "id" => $taskId,
            "user_id" => $userId
        ]);

        if ($stmt->rowCount() > 0) {
            set_flash("success", "Task completed successfully!");
        } else {
            set_flash("error", "Task could not be completed. Please refresh.");
        }
    }

    redirect("smart-planner.php");
}

// Retrieve pending tasks
$stmt = $pdo->prepare("
    SELECT *
    FROM tasks
    WHERE user_id = :user_id
      AND status = 'Pending'
");

$stmt->execute(["user_id" => $userId]);

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================
   PRIORITY SCORING ALGORITHM
========================================= */

$priorityPoints = [
    "High" => 30,
    "Medium" => 20,
    "Low" => 10
];

foreach ($tasks as &$task) {

    $score = 0;
    $reasons = [];

    // 1. Priority score
    $priority = $task["priority"] ?? "Medium";

    $priorityScore = $priorityPoints[$priority] ?? 20;

    $score += $priorityScore;

    $reasons[] = $priority . " priority (+" . $priorityScore . ")";

    // 2. Due date urgency score
    $daysRemaining = null;
    $dueScore = 0;

    if (!empty($task["due_date"])) {

        $dueDate = new DateTime($task["due_date"]);
        $difference = (int) $today->diff($dueDate)->format("%r%a");

        $daysRemaining = $difference;

        if ($difference < 0) {

            $dueScore = 50;
            $reasons[] = "Overdue by " . abs($difference) . " day(s)";

        } elseif ($difference === 0) {

            $dueScore = 45;
            $reasons[] = "Due today";

        } elseif ($difference === 1) {

            $dueScore = 35;
            $reasons[] = "Due tomorrow";

        } elseif ($difference <= 3) {

            $dueScore = 25;
            $reasons[] = "Due within 3 days";

        } elseif ($difference <= 7) {

            $dueScore = 15;
            $reasons[] = "Due within 7 days";

        } else {

            $dueScore = 5;
            $reasons[] = "More than 7 days remaining";
        }

    } else {

        $reasons[] = "No due date set";
    }

    $score += $dueScore;

    // Save calculated values
    $task["urgency_score"] = $score;
    $task["days_remaining"] = $daysRemaining;
    $task["reasons"] = $reasons;

    // Determine urgency level
    if ($score >= 70) {

        $task["urgency"] = "Critical";

    } elseif ($score >= 55) {

        $task["urgency"] = "High";

    } elseif ($score >= 35) {

        $task["urgency"] = "Moderate";

    } else {

        $task["urgency"] = "Low";
    }
}

unset($task);

// Sort tasks from highest score to lowest score
usort($tasks, function ($a, $b) {

    if ($a["urgency_score"] === $b["urgency_score"]) {

        // If scores are equal, earlier due date comes first
        $dateA = $a["due_date"] ?: "9999-12-31";
        $dateB = $b["due_date"] ?: "9999-12-31";

        return strcmp($dateA, $dateB);
    }

    return $b["urgency_score"] <=> $a["urgency_score"];
});

// Statistics
$totalPending = count($tasks);
$criticalTasks = 0;
$highTasks = 0;
$recommendedTask = $tasks[0] ?? null;

foreach ($tasks as $task) {

    if ($task["urgency"] === "Critical") {
        $criticalTasks++;
    }

    if ($task["urgency"] === "High") {
        $highTasks++;
    }
}

$flash = get_flash();

function plannerDateLabel($date) {

    if (empty($date)) {
        return "No due date";
    }

    $today = new DateTime("today");
    $dueDate = new DateTime($date);

    $difference = (int) $today->diff($dueDate)->format("%r%a");

    if ($difference < 0) {
        return abs($difference) . " day(s) overdue";
    }

    if ($difference === 0) {
        return "Due today";
    }

    if ($difference === 1) {
        return "Due tomorrow";
    }

    return date("d M Y", strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Smart Planner | BloomTask</title>

    <link rel="icon"
          type="image/svg+xml"
          href="../assets/images/favicon.svg">

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <style>

        /* =====================================
           SMART PLANNER DESIGN
        ===================================== */

        .smart-planner {
            display: flex;
            flex-direction: column;
            gap: 23px;
        }

        .planner-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .planner-heading h1 {
            margin: 0 0 7px;
        }

        .planner-heading p {
            margin: 0;
            color: var(--text-muted);
        }

        .planner-tag {
            padding: 9px 15px;
            border: 1px solid var(--border);
            border-radius: 30px;
            color: var(--gold);
            font-size: 12px;
            font-weight: 600;
        }

        /* SUMMARY */

        .planner-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .planner-stat {
            padding: 21px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: var(--surface);
            transition: transform .25s ease;
        }

        .planner-stat:hover {
            transform: translateY(-4px);
        }

        .planner-stat span {
            display: block;
            color: var(--text-muted);
            font-size: 12px;
        }

        .planner-stat strong {
            display: block;
            margin-top: 10px;
            color: var(--text);
            font-size: 30px;
        }

        /* RECOMMENDATION */

        .planner-recommendation {
            position: relative;
            overflow: hidden;
            padding: 25px;
            border: 1px solid rgba(198,161,91,.35);
            border-radius: 18px;
            background:
                linear-gradient(
                    120deg,
                    rgba(198,161,91,.17),
                    rgba(198,161,91,.035)
                );
        }

        .recommendation-label {
            color: var(--gold);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .planner-recommendation h2 {
            margin: 10px 0;
            color: var(--text);
            font-size: 21px;
        }

        .planner-recommendation p {
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.7;
            margin: 0;
        }

        .recommendation-score {
            display: inline-block;
            margin-top: 15px;
            padding: 7px 12px;
            border-radius: 8px;
            background: rgba(198,161,91,.16);
            color: var(--gold);
            font-size: 12px;
            font-weight: 700;
        }

        /* TASK LIST */

        .planner-list {
            display: flex;
            flex-direction: column;
            gap: 13px;
        }

        .planner-task {
            position: relative;
            overflow: hidden;
            padding: 20px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: var(--surface);
            transition: transform .25s ease,
                        border-color .25s ease;
            animation: plannerAppear .45s ease both;
        }

        .planner-task:hover {
            transform: translateY(-3px);
            border-color: var(--gold);
        }

        .planner-task:first-child {
            border-left: 4px solid var(--gold);
        }

        @keyframes plannerAppear {

            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }

        }

        .planner-task-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }

        .planner-task h3 {
            margin: 0 0 8px;
            color: var(--text);
            font-size: 16px;
        }

        .planner-task-description {
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .planner-task-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 13px;
        }

        .planner-chip {
            padding: 6px 10px;
            border-radius: 7px;
            background: var(--surface-2);
            color: var(--text-muted);
            font-size: 11px;
        }

        .urgency-badge {
            flex-shrink: 0;
            padding: 7px 11px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .urgency-critical {
            background: rgba(207,119,112,.16);
            color: #d77970;
        }

        .urgency-high {
            background: rgba(211,166,95,.17);
            color: #c99b50;
        }

        .urgency-moderate {
            background: rgba(112,151,184,.16);
            color: #7199b8;
        }

        .urgency-low {
            background: rgba(112,173,138,.16);
            color: #79ad8d;
        }

        /* SCORE */

        .planner-score-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 13px 0 7px;
        }

        .planner-score-row span {
            color: var(--text-muted);
            font-size: 11px;
        }

        .planner-score-row strong {
            color: var(--gold);
            font-size: 13px;
        }

        .planner-score-track {
            width: 100%;
            height: 7px;
            overflow: hidden;
            border-radius: 20px;
            background: var(--surface-2);
        }

        .planner-score-fill {
            height: 100%;
            border-radius: 20px;
            background: linear-gradient(
                90deg,
                #79ad8d,
                #c6a15b,
                #cf7770
            );
            animation: scoreGrow 1s ease both;
        }

        @keyframes scoreGrow {
            from { width: 0; }
        }

        /* REASONS */

        .planner-reasons {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 13px;
        }

        .planner-reason {
            padding: 5px 9px;
            border: 1px solid var(--border);
            border-radius: 20px;
            color: var(--text-muted);
            font-size: 10px;
        }

        .planner-complete-form {
            margin-top: 15px;
            display: flex;
            justify-content: flex-end;
        }

        .planner-complete-btn {
            padding: 9px 15px;
            border: 1px solid var(--gold);
            border-radius: 8px;
            background: transparent;
            color: var(--gold);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s ease;
        }

        .planner-complete-btn:hover {
            background: var(--gold);
            color: #17120a;
        }

        /* EMPTY STATE */

        .planner-empty {
            padding: 55px 20px;
            border: 1px dashed var(--border);
            border-radius: 17px;
            text-align: center;
            background: var(--surface);
        }

        .planner-empty-icon {
            font-size: 42px;
            margin-bottom: 12px;
        }

        .planner-empty h2 {
            color: var(--text);
            margin-bottom: 8px;
        }

        .planner-empty p {
            color: var(--text-muted);
            font-size: 13px;
        }

        .planner-empty a {
            display: inline-block;
            margin-top: 12px;
        }

        .planner-method {
            padding: 20px;
            border: 1px solid var(--border);
            border-radius: 15px;
            background: var(--surface);
        }

        .planner-method h3 {
            margin: 0 0 12px;
            color: var(--text);
            font-size: 14px;
        }

        .planner-method p {
            margin: 7px 0;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.6;
        }

        @media(max-width: 700px) {

            .planner-summary {
                grid-template-columns: 1fr;
            }

            .planner-task-top {
                flex-direction: column;
            }

            .planner-recommendation {
                padding: 19px;
            }

        }

    </style>

</head>

<body>

<div class="app-container">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content smart-planner">

        <!-- HEADER -->

        <div class="planner-heading">

            <div>
                <h1>Smart Priority Planner</h1>

                <p>
                    Let BloomTask analyse your tasks
                    and recommend what to do next.
                </p>
            </div>

            <div class="planner-tag">
                ✦ SMART ENGINE ACTIVE
            </div>

        </div>


        <!-- FLASH MESSAGE -->

        <?php if ($flash): ?>

            <div class="alert <?= e($flash["type"]) ?>">
                <?= e($flash["message"]) ?>
            </div>

        <?php endif; ?>


        <!-- SUMMARY -->

        <section class="planner-summary">

            <div class="planner-stat">
                <span>Total Pending Tasks</span>
                <strong><?= $totalPending ?></strong>
            </div>

            <div class="planner-stat">
                <span>Critical Tasks</span>
                <strong><?= $criticalTasks ?></strong>
            </div>

            <div class="planner-stat">
                <span>High Urgency Tasks</span>
                <strong><?= $highTasks ?></strong>
            </div>

        </section>


        <!-- RECOMMENDATION -->

        <?php if ($recommendedTask): ?>

            <section class="planner-recommendation">

                <div class="recommendation-label">
                    ✦ Recommended Next Task
                </div>

                <h2>
                    <?= e($recommendedTask["title"]) ?>
                </h2>

                <p>
                    BloomTask recommends this task because it
                    has the highest urgency score among your
                    pending tasks.
                </p>

                <div class="recommendation-score">
                    Priority Score:
                    <?= $recommendedTask["urgency_score"] ?> / 80
                </div>

            </section>

        <?php endif; ?>


        <!-- TASK RANKING -->

        <section>

            <div class="page-header">
                <div>
                    <h2>Ranked Task Recommendations</h2>
                    <p class="page-subtitle">
                        Tasks are automatically ranked
                        according to urgency.
                    </p>
                </div>
            </div>

            <?php if (empty($tasks)): ?>

                <div class="planner-empty">

                    <div class="planner-empty-icon">✦</div>

                    <h2>You're all caught up!</h2>

                    <p>
                        You have no pending tasks.
                        Add a new task to let BloomTask
                        analyse your workload.
                    </p>

                    <a href="tasks.php" class="btn btn-primary">
                        Create a Task
                    </a>

                </div>

            <?php else: ?>

                <div class="planner-list">

                    <?php foreach ($tasks as $index => $task): ?>

                        <?php
                        $scoreWidth = min(
                            100,
                            round(($task["urgency_score"] / 80) * 100)
                        );

                        $urgencyClass = strtolower($task["urgency"]);
                        ?>

                        <article class="planner-task"
                                 style="animation-delay: <?= $index * 0.07 ?>s">

                            <div class="planner-task-top">

                                <div>

                                    <h3>
                                        <?= $index + 1 ?>.
                                        <?= e($task["title"]) ?>
                                    </h3>

                                    <?php if (!empty($task["description"])): ?>

                                        <div class="planner-task-description">
                                            <?= e($task["description"]) ?>
                                        </div>

                                    <?php endif; ?>

                                </div>

                                <span class="urgency-badge urgency-<?= e($urgencyClass) ?>">
                                    <?= e($task["urgency"]) ?>
                                </span>

                            </div>


                            <div class="planner-task-meta">

                                <span class="planner-chip">
                                    <?= e($task["category"] ?: "Uncategorised") ?>
                                </span>

                                <span class="planner-chip">
                                    <?= e($task["priority"]) ?> Priority
                                </span>

                                <span class="planner-chip">
                                    <?= e(plannerDateLabel($task["due_date"])) ?>
                                </span>

                            </div>


                            <div class="planner-score-row">

                                <span>Calculated Urgency Score</span>

                                <strong>
                                    <?= $task["urgency_score"] ?> / 80
                                </strong>

                            </div>


                            <div class="planner-score-track">

                                <div class="planner-score-fill"
                                     style="width: <?= $scoreWidth ?>%">
                                </div>

                            </div>


                            <div class="planner-reasons">

                                <?php foreach ($task["reasons"] as $reason): ?>

                                    <span class="planner-reason">
                                        <?= e($reason) ?>
                                    </span>

                                <?php endforeach; ?>

                            </div>


                            <form method="POST"
                                  class="planner-complete-form"
                                  onsubmit="return confirm('Mark this task as completed?');">

                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?= e(csrf_token()) ?>">

                                <input type="hidden"
                                       name="task_id"
                                       value="<?= (int) $task["id"] ?>">

                                <button type="submit"
                                        class="planner-complete-btn">

                                    ✓ Mark as Completed

                                </button>

                            </form>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>


        <!-- ALGORITHM EXPLANATION -->

        <section class="planner-method">

            <h3>How the Smart Priority Engine Works</h3>

            <p>
                <strong>Priority points:</strong>
                High = 30, Medium = 20, Low = 10.
            </p>

            <p>
                <strong>Due date points:</strong>
                Overdue = 50, Today = 45, Tomorrow = 35,
                Within 3 days = 25, Within 7 days = 15,
                More than 7 days = 5.
            </p>

            <p>
                <strong>Urgency score:</strong>
                Priority points + Due date points.
            </p>

            <p>
                <strong>Urgency classification:</strong>
                Critical = 70 or above, High = 55–69,
                Moderate = 35–54, Low = below 35.
            </p>

            <p>
                Tasks without a due date receive no due-date
                points. When scores are equal, the earlier
                due date is ranked first.
            </p>

        </section>

    </main>

</div>

<script src="../assets/js/script.js"></script>

</body>
</html>
