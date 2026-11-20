
<?php
require_once "../includes/auth.php";
require_once "../includes/functions.php";
require_once "../config/db.php";

$userId = (int) $_SESSION["user_id"];

// =========================================
// TASK STATISTICS
// =========================================

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'Completed'), 0) AS completed,
        COALESCE(SUM(status = 'Pending'), 0) AS pending
    FROM tasks
    WHERE user_id = :user_id
");

$stmt->execute(["user_id" => $userId]);
$stats = $stmt->fetch();

$totalTasks = (int) $stats["total"];
$completedTasks = (int) $stats["completed"];
$pendingTasks = (int) $stats["pending"];

$productivityScore = $totalTasks > 0
    ? (int) round(($completedTasks / $totalTasks) * 100)
    : 0;

// =========================================
// COMPLETION DATES
// =========================================

$stmt = $pdo->prepare("
    SELECT DISTINCT DATE(completed_at) AS completion_date
    FROM tasks
    WHERE user_id = :user_id
      AND status = 'Completed'
      AND completed_at IS NOT NULL
    ORDER BY completion_date DESC
");

$stmt->execute(["user_id" => $userId]);
$completionDates = $stmt->fetchAll(PDO::FETCH_COLUMN);

$dateSet = array_flip($completionDates);

// =========================================
// CURRENT STREAK
// =========================================

$today = new DateTime("today");
$yesterday = (clone $today)->modify("-1 day");

$currentStreak = 0;
$checkDate = clone $today;

if (
    !isset($dateSet[$checkDate->format("Y-m-d")]) &&
    isset($dateSet[$yesterday->format("Y-m-d")])
) {
    $checkDate = clone $yesterday;
}

while (isset($dateSet[$checkDate->format("Y-m-d")])) {
    $currentStreak++;
    $checkDate->modify("-1 day");
}

// =========================================
// BEST STREAK
// =========================================

$bestStreak = 0;
$runningStreak = 0;
$previousDate = null;

foreach (array_reverse($completionDates) as $date) {

    $currentDate = new DateTime($date);

    if ($previousDate === null) {
        $runningStreak = 1;
    } else {
        $difference = (int) $previousDate
            ->diff($currentDate)->days;

        $runningStreak = ($difference === 1)
            ? $runningStreak + 1
            : 1;
    }

    $bestStreak = max($bestStreak, $runningStreak);
    $previousDate = $currentDate;
}

// =========================================
// TASK ACHIEVEMENTS
// =========================================

$achievements = [
    [
        "name" => "First Step",
        "description" => "Complete your first task.",
        "icon" => "🌱",
        "target" => 1
    ],
    [
        "name" => "Getting Started",
        "description" => "Complete 5 tasks.",
        "icon" => "🌿",
        "target" => 5
    ],
    [
        "name" => "Task Explorer",
        "description" => "Complete 10 tasks.",
        "icon" => "🌸",
        "target" => 10
    ],
    [
        "name" => "Productivity Pro",
        "description" => "Complete 25 tasks.",
        "icon" => "🌻",
        "target" => 25
    ],
    [
        "name" => "Bloom Master",
        "description" => "Complete 50 tasks.",
        "icon" => "🏆",
        "target" => 50
    ]
];

// =========================================
// STREAK ACHIEVEMENTS
// =========================================

$streakAchievements = [
    [
        "name" => "Consistency Begins",
        "description" => "Complete tasks for 3 consecutive days.",
        "icon" => "🔥",
        "target" => 3
    ],
    [
        "name" => "One Week Strong",
        "description" => "Complete tasks for 7 consecutive days.",
        "icon" => "⚡",
        "target" => 7
    ],
    [
        "name" => "Unstoppable",
        "description" => "Complete tasks for 14 consecutive days.",
        "icon" => "💎",
        "target" => 14
    ]
];

$unlockedAchievements = 0;

foreach ($achievements as $key => $achievement) {
    $achievements[$key]["unlocked"] =
        $completedTasks >= $achievement["target"];

    if ($achievements[$key]["unlocked"]) {
        $unlockedAchievements++;
    }
}

foreach ($streakAchievements as $key => $achievement) {
    $streakAchievements[$key]["unlocked"] =
        $bestStreak >= $achievement["target"];

    if ($streakAchievements[$key]["unlocked"]) {
        $unlockedAchievements++;
    }
}

$totalAchievements = count($achievements) + count($streakAchievements);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Achievements | BloomTask</title>

    <link rel="icon" type="image/svg+xml"
          href="../assets/images/favicon.svg">

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <style>
        /* =================================
           ACHIEVEMENTS PAGE
           BLOOMTASK THEME INTEGRATION
        ================================= */

        .achievement-intro {
            margin-bottom: 25px;
        }

        .achievement-intro p {
            margin-top: 5px;
        }

        /* Productivity hero */

        .achievement-hero {
            position: relative;
            overflow: hidden;
            padding: 30px;
            margin-bottom: 24px;
            border-radius: var(--radius);
            background:
                linear-gradient(
                    120deg,
                    var(--surface-2),
                    var(--surface-3)
                );
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .achievement-hero::after {
            content: "✦";
            position: absolute;
            right: 45px;
            top: 15px;
            font-size: 110px;
            color: var(--gold);
            opacity: .12;
            pointer-events: none;
        }

        .hero-label {
            color: var(--gold);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .hero-score {
            color: var(--gold);
            font-family: Georgia, serif;
            font-size: clamp(42px, 6vw, 64px);
            line-height: 1.2;
            margin: 14px 0;
            font-weight: 600;
        }

        .hero-description {
            color: var(--text-muted);
            margin: 0;
        }

        .hero-progress {
            max-width: 650px;
            margin-top: 22px;
        }

        .hero-progress .progress-track {
            height: 10px;
            background: var(--surface);
        }

        .hero-progress .progress-fill {
            background: var(--gold);
        }

        .hero-progress-text {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 10px;
            color: var(--text-muted);
            font-size: 12px;
        }

        /* Statistics */

        .achievement-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 32px;
        }

        .achievement-stat {
            position: relative;
            overflow: hidden;
            padding: 22px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            transition: transform .25s ease,
                        border-color .25s ease;
        }

        .achievement-stat:hover {
            transform: translateY(-4px);
            border-color: var(--gold);
        }

        .achievement-stat::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 3px;
            height: 100%;
            background: var(--gold);
        }

        .achievement-stat-icon {
            font-size: 23px;
            margin-bottom: 12px;
        }

        .achievement-stat-label {
            color: var(--text-muted);
            font-size: 12px;
        }

        .achievement-stat-number {
            color: var(--gold);
            font-family: Georgia, serif;
            font-size: 32px;
            margin: 5px 0;
        }

        .achievement-stat-description {
            color: var(--text-muted);
            font-size: 11px;
        }

        /* Section headings */

        .achievement-section {
            margin-bottom: 35px;
        }

        .achievement-section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 18px;
        }

        .achievement-section-heading h2 {
            margin-bottom: 4px;
        }

        .achievement-section-heading p {
            margin: 0;
            font-size: 13px;
        }

        .achievement-count {
            padding: 6px 12px;
            border: 1px solid var(--border);
            border-radius: 20px;
            color: var(--gold);
            background: var(--surface-2);
            font-size: 12px;
            white-space: nowrap;
        }

        /* Achievement cards */

        .achievement-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .achievement-card {
            position: relative;
            padding: 23px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            transition: transform .25s ease,
                        border-color .25s ease,
                        box-shadow .25s ease;
        }

        .achievement-card:hover {
            transform: translateY(-4px);
            border-color: var(--gold);
            box-shadow: var(--shadow);
        }

        .achievement-card.unlocked {
            background:
                linear-gradient(
                    145deg,
                    var(--surface),
                    var(--surface-2)
                );
            border-color: var(--gold-dark);
        }

        .achievement-card.locked {
            opacity: .72;
        }

        .achievement-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 18px;
        }

        .achievement-icon {
            width: 55px;
            height: 55px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            font-size: 27px;
        }

        .achievement-card.unlocked .achievement-icon {
            border-color: var(--gold-dark);
        }

        .achievement-lock {
            color: var(--text-muted);
            font-size: 17px;
        }

        .achievement-card h3 {
            color: var(--text);
            margin-bottom: 6px;
        }

        .achievement-card p {
            color: var(--text-muted);
            font-size: 12px;
            min-height: 38px;
            margin: 0 0 16px;
        }

        .progress-track {
            height: 7px;
            overflow: hidden;
            border-radius: 20px;
            background: var(--surface-2);
            border: 1px solid var(--border);
        }

        .progress-fill {
            height: 100%;
            border-radius: 20px;
            background: var(--gold);
            transition: width 1s ease;
        }

        .achievement-card-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .8px;
        }

        .achievement-status {
            color: var(--text-muted);
        }

        .achievement-card.unlocked .achievement-status {
            color: var(--success);
        }

        .achievement-progress-text {
            color: var(--text-muted);
        }

        /* Closing message */

        .achievement-note {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px 22px;
            margin-bottom: 25px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-left: 3px solid var(--gold);
            border-radius: var(--radius);
        }

        .achievement-note-icon {
            font-size: 28px;
        }

        .achievement-note strong {
            color: var(--gold);
        }

        .achievement-note p {
            margin: 3px 0 0;
            font-size: 12px;
        }

        /* Responsive */

        @media (max-width: 1000px) {
            .achievement-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .achievement-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .achievement-stats,
            .achievement-grid {
                grid-template-columns: 1fr;
            }

            .achievement-hero {
                padding: 23px;
            }

            .achievement-hero::after {
                right: 15px;
                font-size: 75px;
            }

            .achievement-section-heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

</head>

<body>

<div class="app-layout">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">

        <div class="page-container">

            <!-- PAGE HEADER -->

            <div class="page-header">

                <div class="page-header-top">

                    <button type="button"
                            class="mobile-menu-button"
                            id="mobileMenuButton">
                        ☰
                    </button>

                    <div>
                        <p class="page-subtitle">
                            YOUR PRODUCTIVITY JOURNEY
                        </p>

                        <h1>Achievements & Rewards</h1>

                        <p>
                            Every completed task is another step
                            towards becoming your best self.
                        </p>
                    </div>

                </div>

            </div>

            <!-- PRODUCTIVITY SCORE -->

            <section class="achievement-hero">

                <div class="hero-label">
                    ✦ Your Productivity Score
                </div>

                <div class="hero-score">
                    <?= $productivityScore ?>%
                </div>

                <p class="hero-description">
                    Keep completing tasks and watch your progress grow.
                </p>

                <div class="hero-progress">

                    <div class="progress-track">
                        <div class="progress-fill"
                             style="width: <?= $productivityScore ?>%">
                        </div>
                    </div>

                    <div class="hero-progress-text">
                        <span>
                            <?= $completedTasks ?> completed out of
                            <?= $totalTasks ?> total tasks
                        </span>

                        <span><?= $productivityScore ?>%</span>
                    </div>

                </div>

            </section>

            <!-- STATISTICS -->

            <section class="achievement-stats">

                <div class="achievement-stat">
                    <div class="achievement-stat-icon">✓</div>
                    <div class="achievement-stat-label">Completed Tasks</div>
                    <div class="achievement-stat-number">
                        <?= $completedTasks ?>
                    </div>
                    <div class="achievement-stat-description">
                        Successfully finished
                    </div>
                </div>

                <div class="achievement-stat">
                    <div class="achievement-stat-icon">◷</div>
                    <div class="achievement-stat-label">Pending Tasks</div>
                    <div class="achievement-stat-number">
                        <?= $pendingTasks ?>
                    </div>
                    <div class="achievement-stat-description">
                        Awaiting completion
                    </div>
                </div>

                <div class="achievement-stat">
                    <div class="achievement-stat-icon">🔥</div>
                    <div class="achievement-stat-label">Current Streak</div>
                    <div class="achievement-stat-number">
                        <?= $currentStreak ?>
                    </div>
                    <div class="achievement-stat-description">
                        Consecutive active days
                    </div>
                </div>

                <div class="achievement-stat">
                    <div class="achievement-stat-icon">🏆</div>
                    <div class="achievement-stat-label">Achievements</div>
                    <div class="achievement-stat-number">
                        <?= $unlockedAchievements ?>/<?= $totalAchievements ?>
                    </div>
                    <div class="achievement-stat-description">
                        Badges unlocked
                    </div>
                </div>

            </section>

            <!-- TASK MILESTONES -->

            <section class="achievement-section">

                <div class="achievement-section-heading">

                    <div>
                        <h2>Task Milestones</h2>
                        <p>Earn badges by completing more tasks.</p>
                    </div>

                    <span class="achievement-count">
                        <?= $completedTasks ?> Tasks Completed
                    </span>

                </div>

                <div class="achievement-grid">

                    <?php foreach ($achievements as $achievement): ?>

                        <?php
                        $progress = min(
                            100,
                            ($completedTasks / $achievement["target"]) * 100
                        );
                        ?>

                        <article class="achievement-card
                            <?= $achievement["unlocked"] ? "unlocked" : "locked" ?>">

                            <div class="achievement-card-top">

                                <div class="achievement-icon">
                                    <?= $achievement["icon"] ?>
                                </div>

                                <div class="achievement-lock">
                                    <?= $achievement["unlocked"] ? "✓" : "🔒" ?>
                                </div>

                            </div>

                            <h3><?= e($achievement["name"]) ?></h3>

                            <p><?= e($achievement["description"]) ?></p>

                            <div class="progress-track">
                                <div class="progress-fill"
                                     style="width: <?= $progress ?>%">
                                </div>
                            </div>

                            <div class="achievement-card-bottom">

                                <span class="achievement-status">
                                    <?= $achievement["unlocked"]
                                        ? "✓ UNLOCKED"
                                        : "LOCKED" ?>
                                </span>

                                <span class="achievement-progress-text">
                                    <?= min($completedTasks, $achievement["target"]) ?>
                                    /<?= $achievement["target"] ?>
                                </span>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            </section>

            <!-- STREAK MILESTONES -->

            <section class="achievement-section">

                <div class="achievement-section-heading">

                    <div>
                        <h2>Consistency Awards</h2>
                        <p>Build a daily habit and maintain your streak.</p>
                    </div>

                    <span class="achievement-count">
                        Best Streak: <?= $bestStreak ?> Days
                    </span>

                </div>

                <div class="achievement-grid">

                    <?php foreach ($streakAchievements as $achievement): ?>

                        <?php
                        $streakProgress = min(
                            100,
                            ($bestStreak / $achievement["target"]) * 100
                        );
                        ?>

                        <article class="achievement-card
                            <?= $achievement["unlocked"] ? "unlocked" : "locked" ?>">

                            <div class="achievement-card-top">

                                <div class="achievement-icon">
                                    <?= $achievement["icon"] ?>
                                </div>

                                <div class="achievement-lock">
                                    <?= $achievement["unlocked"] ? "✓" : "🔒" ?>
                                </div>

                            </div>

                            <h3><?= e($achievement["name"]) ?></h3>

                            <p><?= e($achievement["description"]) ?></p>

                            <div class="progress-track">
                                <div class="progress-fill"
                                     style="width: <?= $streakProgress ?>%">
                                </div>
                            </div>

                            <div class="achievement-card-bottom">

                                <span class="achievement-status">
                                    <?= $achievement["unlocked"]
                                        ? "✓ UNLOCKED"
                                        : "LOCKED" ?>
                                </span>

                                <span class="achievement-progress-text">
                                    <?= min($bestStreak, $achievement["target"]) ?>
                                    /<?= $achievement["target"] ?> DAYS
                                </span>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            </section>

            <!-- PRODUCTIVITY MESSAGE -->

            <div class="achievement-note">

                <div class="achievement-note-icon">🌱</div>

                <div>
                    <strong>Every small step counts.</strong>

                    <p>
                        Your productivity score reflects your completed
                        tasks. Your streak tracks consecutive calendar
                        days with at least one completed task.
                        Keep growing at your own pace!
                    </p>
                </div>

            </div>

        </div>

    </main>

</div>

<!-- IMPORTANT: Load BloomTask's existing JavaScript -->
<script src="../assets/js/script.js"></script>

</body>
</html>
