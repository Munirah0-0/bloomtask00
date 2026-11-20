
<?php
require_once "../includes/auth.php";
require_once "../includes/functions.php";
require_once "../config/db.php";

$userId = (int) $_SESSION["user_id"];

// Get pending tasks belonging to the logged-in user
$stmt = $pdo->prepare("
    SELECT id, title, priority, due_date
    FROM tasks
    WHERE user_id = :user_id
      AND status = 'Pending'
    ORDER BY
        CASE priority
            WHEN 'High' THEN 1
            WHEN 'Medium' THEN 2
            ELSE 3
        END,
        due_date ASC
");

$stmt->execute(["user_id" => $userId]);
$pendingTasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Focus Mode | BloomTask</title>

    <link rel="icon" type="image/svg+xml"
          href="../assets/images/favicon.svg">

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <style>
        /* ==================================
           BLOOMTASK FOCUS MODE
        ================================== */

        .focus-intro {
            margin-bottom: 25px;
        }

        .focus-intro p {
            margin-top: 5px;
        }

        .focus-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(260px, .8fr);
            gap: 22px;
            align-items: start;
        }

        .focus-main-card {
            padding: 30px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .focus-mode-label {
            color: var(--gold);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-align: center;
        }

        .focus-mode-options {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 9px;
            margin: 22px 0 30px;
        }

        .focus-mode-btn {
            padding: 9px 15px;
            background: var(--surface-2);
            color: var(--text-muted);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 12px;
            transition: .2s;
        }

        .focus-mode-btn:hover {
            color: var(--gold);
            border-color: var(--gold);
        }

        .focus-mode-btn.active {
            background: var(--gold);
            color: #17120a;
            border-color: var(--gold);
            font-weight: 700;
        }

        /* TIMER */

        .timer-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 10px auto 25px;
        }

        .timer-circle {
            width: min(290px, 75vw);
            aspect-ratio: 1;
            position: relative;
            display: grid;
            place-items: center;
        }

        .timer-circle svg {
            position: absolute;
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .timer-track {
            fill: none;
            stroke: var(--surface-2);
            stroke-width: 7;
        }

        .timer-progress {
            fill: none;
            stroke: var(--gold);
            stroke-width: 7;
            stroke-linecap: round;
            stroke-dasharray: 565.49;
            stroke-dashoffset: 0;
            transition: stroke-dashoffset .8s linear;
        }

        .timer-content {
            text-align: center;
            z-index: 1;
        }

        .timer-display {
            color: var(--text);
            font-family: Georgia, serif;
            font-size: clamp(52px, 8vw, 72px);
            font-weight: 600;
            line-height: 1.2;
            font-variant-numeric: tabular-nums;
        }

        .timer-status {
            color: var(--text-muted);
            font-size: 12px;
            margin-top: 8px;
        }

        .timer-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin: 25px 0;
        }

        .timer-buttons .btn {
            min-width: 105px;
        }

        .focus-message {
            text-align: center;
            color: var(--text-muted);
            font-size: 12px;
            margin-top: 15px;
        }

        /* TASK SELECTION */

        .focus-task-section {
            margin-top: 28px;
            padding-top: 23px;
            border-top: 1px solid var(--border);
        }

        .focus-task-section h3 {
            margin-bottom: 12px;
        }

        .focus-task-section label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-muted);
            font-size: 12px;
        }

        .focus-task-select {
            width: 100%;
            padding: 12px 14px;
            background: var(--surface-2);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 8px;
            outline: none;
        }

        .focus-task-select:focus {
            border-color: var(--gold);
        }

        /* SIDE PANEL */

        .focus-side {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .focus-side-card {
            padding: 23px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
        }

        .focus-side-card h3 {
            margin-bottom: 18px;
        }

        .focus-stat {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid var(--border);
        }

        .focus-stat:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .focus-stat-label {
            color: var(--text-muted);
            font-size: 12px;
        }

        .focus-stat-value {
            color: var(--gold);
            font-family: Georgia, serif;
            font-size: 22px;
        }

        /* SESSION DOTS */

        .session-dots {
            display: flex;
            gap: 10px;
            margin: 15px 0;
        }

        .session-dot {
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: var(--surface-3);
            border: 1px solid var(--border);
        }

        .session-dot.completed {
            background: var(--gold);
            border-color: var(--gold);
        }

        .focus-tip {
            padding: 18px;
            background: var(--surface-2);
            border-left: 3px solid var(--gold);
            border-radius: 8px;
        }

        .focus-tip strong {
            color: var(--gold);
        }

        .focus-tip p {
            font-size: 12px;
            margin: 6px 0 0;
        }

        .focus-reset-stats {
            margin-top: 15px;
            width: 100%;
        }

        @media (max-width: 900px) {
            .focus-layout {
                grid-template-columns: 1fr;
            }

            .focus-side {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 550px) {
            .focus-main-card {
                padding: 20px 14px;
            }

            .focus-side {
                grid-template-columns: 1fr;
            }

            .timer-buttons {
                gap: 8px;
            }

            .timer-buttons .btn {
                min-width: 90px;
            }
        }
    </style>

</head>

<body>

<div class="app-layout">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">

        <div class="page-container">

            <!-- HEADER -->

            <div class="page-header">

                <div class="page-header-top">

                    <button type="button"
                            class="mobile-menu-button"
                            id="mobileMenuButton">
                        ☰
                    </button>

                    <div class="focus-intro">
                        <p class="page-subtitle">TIME TO CONCENTRATE</p>

                        <h1>Focus Mode</h1>

                        <p>
                            One task at a time. Give your best attention
                            to what matters.
                        </p>
                    </div>

                </div>

            </div>

            <div class="focus-layout">

                <!-- MAIN TIMER -->

                <section class="focus-main-card">

                    <div class="focus-mode-label" id="modeHeading">
                        FOCUS SESSION
                    </div>

                    <div class="focus-mode-options">

                        <button type="button"
                                class="focus-mode-btn active"
                                data-mode="focus">
                            Focus · 25 min
                        </button>

                        <button type="button"
                                class="focus-mode-btn"
                                data-mode="short">
                            Short Break · 5 min
                        </button>

                        <button type="button"
                                class="focus-mode-btn"
                                data-mode="long">
                            Long Break · 15 min
                        </button>

                    </div>

                    <!-- CIRCULAR TIMER -->

                    <div class="timer-container">

                        <div class="timer-circle">

                            <svg viewBox="0 0 200 200">

                                <circle
                                    class="timer-track"
                                    cx="100"
                                    cy="100"
                                    r="90">
                                </circle>

                                <circle
                                    class="timer-progress"
                                    id="timerProgress"
                                    cx="100"
                                    cy="100"
                                    r="90">
                                </circle>

                            </svg>

                            <div class="timer-content">

                                <div class="timer-display"
                                     id="timerDisplay">
                                    25:00
                                </div>

                                <div class="timer-status"
                                     id="timerStatus">
                                    Ready when you are
                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- TIMER CONTROLS -->

                    <div class="timer-buttons">

                        <button type="button"
                                class="btn btn-primary"
                                id="startButton">
                            ▶ Start
                        </button>

                        <button type="button"
                                class="btn btn-secondary"
                                id="resetButton">
                            ↻ Reset
                        </button>

                        <button type="button"
                                class="btn btn-secondary"
                                id="skipButton">
                            Skip →
                        </button>

                    </div>

                    <p class="focus-message" id="focusMessage">
                        Stay focused. Your progress starts here.
                    </p>

                    <!-- TASK SELECTION -->

                    <div class="focus-task-section">

                        <h3>What are you working on?</h3>

                        <label for="focusTask">
                            Select a pending task for this session
                        </label>

                        <select class="focus-task-select" id="focusTask">

                            <option value="">
                                General focus session
                            </option>

                            <?php foreach ($pendingTasks as $task): ?>

                                <option value="<?= (int) $task["id"] ?>">
                                    <?= e($task["title"]) ?>
                                    (<?= e($task["priority"]) ?>)
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </section>

                <!-- RIGHT SIDE -->

                <aside class="focus-side">

                    <!-- STATISTICS -->

                    <section class="focus-side-card">

                        <h3>Your Focus Statistics</h3>

                        <div class="focus-stat">

                            <span class="focus-stat-label">
                                Completed Sessions
                            </span>

                            <strong class="focus-stat-value"
                                    id="completedSessions">
                                0
                            </strong>

                        </div>

                        <div class="focus-stat">

                            <span class="focus-stat-label">
                                Total Focus Time
                            </span>

                            <strong class="focus-stat-value"
                                    id="totalFocusTime">
                                0 min
                            </strong>

                        </div>

                        <div class="focus-stat">

                            <span class="focus-stat-label">
                                Current Session
                            </span>

                            <strong class="focus-stat-value"
                                    id="sessionNumber">
                                1/4
                            </strong>

                        </div>

                        <button type="button"
                                class="btn btn-secondary btn-sm focus-reset-stats"
                                id="clearStatsButton">
                            Reset Statistics
                        </button>

                    </section>

                    <!-- POMODORO PROGRESS -->

                    <section class="focus-side-card">

                        <h3>Pomodoro Progress</h3>

                        <p class="page-subtitle">
                            Complete four focus sessions for a long break.
                        </p>

                        <div class="session-dots">

                            <span class="session-dot" id="dot1"></span>
                            <span class="session-dot" id="dot2"></span>
                            <span class="session-dot" id="dot3"></span>
                            <span class="session-dot" id="dot4"></span>

                        </div>

                        <p class="page-subtitle" id="pomodoroMessage">
                            Your first session is waiting.
                        </p>

                    </section>

                    <!-- FOCUS TIP -->

                    <section class="focus-tip">

                        <strong>✦ A little reminder</strong>

                        <p>
                            Put away distractions, focus on one task,
                            and give yourself permission to take breaks.
                        </p>

                    </section>

                </aside>

            </div>

        </div>

    </main>

</div>

<script>
(function () {

    // =====================================
    // USER-SPECIFIC LOCAL STORAGE
    // =====================================

    const STORAGE_KEY = "bloomtask-focus-<?= $userId ?>";

    const defaultStats = {
        sessions: 0,
        minutes: 0
    };

    let stats = loadStats();

    function loadStats() {
        try {
            const saved = JSON.parse(
                localStorage.getItem(STORAGE_KEY)
            );

            if (
                saved &&
                Number.isFinite(saved.sessions) &&
                Number.isFinite(saved.minutes) &&
                saved.sessions >= 0 &&
                saved.minutes >= 0
            ) {
                return saved;
            }
        } catch (error) {
            console.warn("Unable to read focus statistics.");
        }

        return { ...defaultStats };
    }

    function saveStats() {
        localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify(stats)
        );
    }

    // =====================================
    // TIMER SETTINGS
    // =====================================

    const modes = {
        focus: {
            duration: 25 * 60,
            label: "FOCUS SESSION"
        },
        short: {
            duration: 5 * 60,
            label: "SHORT BREAK"
        },
        long: {
            duration: 15 * 60,
            label: "LONG BREAK"
        }
    };

    let currentMode = "focus";
    let remainingSeconds = modes.focus.duration;
    let timerInterval = null;
    let isRunning = false;
    let focusSessionsInCycle = 0;

    // =====================================
    // ELEMENTS
    // =====================================

    const timerDisplay =
        document.getElementById("timerDisplay");

    const timerStatus =
        document.getElementById("timerStatus");

    const timerProgress =
        document.getElementById("timerProgress");

    const modeHeading =
        document.getElementById("modeHeading");

    const startButton =
        document.getElementById("startButton");

    const resetButton =
        document.getElementById("resetButton");

    const skipButton =
        document.getElementById("skipButton");

    const focusMessage =
        document.getElementById("focusMessage");

    const completedSessions =
        document.getElementById("completedSessions");

    const totalFocusTime =
        document.getElementById("totalFocusTime");

    const sessionNumber =
        document.getElementById("sessionNumber");

    const pomodoroMessage =
        document.getElementById("pomodoroMessage");

    const circleLength = 2 * Math.PI * 90;

    // =====================================
    // DISPLAY TIMER
    // =====================================

    function updateTimerDisplay() {

        const minutes = Math.floor(
            remainingSeconds / 60
        );

        const seconds = remainingSeconds % 60;

        timerDisplay.textContent =
            String(minutes).padStart(2, "0") +
            ":" +
            String(seconds).padStart(2, "0");

        const duration = modes[currentMode].duration;

        const progress =
            (duration - remainingSeconds) / duration;

        timerProgress.style.strokeDashoffset =
            circleLength * progress;

        document.title =
            timerDisplay.textContent + " | BloomTask Focus";
    }

    // =====================================
    // DISPLAY STATISTICS
    // =====================================

    function updateStatistics() {

        completedSessions.textContent =
            stats.sessions;

        totalFocusTime.textContent =
            stats.minutes + " min";

        sessionNumber.textContent =
            (focusSessionsInCycle + 1) + "/4";

        for (let i = 1; i <= 4; i++) {

            const dot = document.getElementById("dot" + i);

            dot.classList.toggle(
                "completed",
                i <= focusSessionsInCycle
            );
        }

        if (focusSessionsInCycle === 4) {
            pomodoroMessage.textContent =
                "Four focus sessions completed! Enjoy your long break.";
        } else {
            pomodoroMessage.textContent =
                focusSessionsInCycle +
                " of 4 focus sessions completed in this cycle.";
        }
    }

    // =====================================
    // STOP TIMER
    // =====================================

    function stopTimer() {

        if (timerInterval !== null) {
            clearInterval(timerInterval);
            timerInterval = null;
        }

        isRunning = false;

        startButton.textContent = "▶ Start";
        timerStatus.textContent = "Paused";
    }

    // =====================================
    // SELECT MODE
    // =====================================

    function selectMode(mode) {

        if (!modes[mode]) return;

        stopTimer();

        currentMode = mode;
        remainingSeconds = modes[mode].duration;

        modeHeading.textContent =
            modes[mode].label;

        document.querySelectorAll("[data-mode]").forEach(
            function (button) {

                button.classList.toggle(
                    "active",
                    button.dataset.mode === mode
                );

            }
        );

        timerStatus.textContent = "Ready when you are";

        focusMessage.textContent =
            mode === "focus"
                ? "Stay focused. Your progress starts here."
                : "Take a moment to relax and recharge.";

        updateTimerDisplay();
    }

    // =====================================
    // COMPLETE SESSION
    // =====================================

    function completeSession() {

        stopTimer();

        remainingSeconds = 0;
        updateTimerDisplay();

        if (currentMode === "focus") {

            stats.sessions++;
            stats.minutes += 25;

            focusSessionsInCycle++;

            if (focusSessionsInCycle >= 4) {

                focusSessionsInCycle = 0;

                saveStats();
                updateStatistics();

                timerStatus.textContent = "Focus session completed!";

                focusMessage.textContent =
                    "Excellent work! Time for a long break.";

                if (typeof window.showToast === "function") {
                    window.showToast(
                        "Focus session completed! Enjoy your long break.",
                        "success"
                    );
                }

                selectMode("long");
                timerStatus.textContent = "Long break is ready";

            } else {

                saveStats();
                updateStatistics();

                timerStatus.textContent = "Focus session completed!";

                focusMessage.textContent =
                    "Well done! Take a short break.";

                if (typeof window.showToast === "function") {
                    window.showToast(
                        "Focus session completed! Great work.",
                        "success"
                    );
                }

                selectMode("short");
                timerStatus.textContent = "Short break is ready";
            }

        } else {

            timerStatus.textContent = "Break completed!";

            focusMessage.textContent =
                "You're refreshed. Ready for another focus session?";

            if (typeof window.showToast === "function") {
                window.showToast(
                    "Break completed. Time to get back to work!",
                    "success"
                );
            }

            selectMode("focus");
            timerStatus.textContent = "Ready for another focus session";
        }

        updateStatistics();
    }

    // =====================================
    // TIMER TICK
    // =====================================

    function tick() {

        if (remainingSeconds > 0) {
            remainingSeconds--;
            updateTimerDisplay();
        }

        if (remainingSeconds <= 0) {
            completeSession();
        }

    }

    // =====================================
    // START / PAUSE
    // =====================================

    startButton.addEventListener("click", function () {

        if (isRunning) {

            stopTimer();

            focusMessage.textContent =
                "Timer paused. Continue whenever you're ready.";

            return;
        }

        isRunning = true;

        startButton.textContent = "Ⅱ Pause";
        timerStatus.textContent = "Timer is running";

        focusMessage.textContent =
            currentMode === "focus"
                ? "Stay focused and keep going."
                : "Relax and enjoy your break.";

        timerInterval = setInterval(tick, 1000);

    });

    // =====================================
    // RESET
    // =====================================

    resetButton.addEventListener("click", function () {

        stopTimer();

        remainingSeconds = modes[currentMode].duration;

        timerStatus.textContent = "Timer reset";

        focusMessage.textContent =
            "Ready when you are.";

        updateTimerDisplay();

    });

    // =====================================
    // SKIP
    // =====================================

    skipButton.addEventListener("click", function () {

        if (currentMode === "focus") {

            if (!confirm(
                "Skip this focus session? It will not count as completed."
            )) {
                return;
            }

            selectMode("short");

        } else {

            selectMode("focus");

        }

    });

    // =====================================
    // MODE BUTTONS
    // =====================================

    document.querySelectorAll("[data-mode]").forEach(
        function (button) {

            button.addEventListener("click", function () {

                selectMode(button.dataset.mode);

            });

        }
    );

    // =====================================
    // RESET STATISTICS
    // =====================================

    document.getElementById("clearStatsButton")
        .addEventListener("click", function () {

            if (!confirm(
                "Are you sure you want to reset your focus statistics?"
            )) {
                return;
            }

            stats = { ...defaultStats };
            focusSessionsInCycle = 0;

            saveStats();
            updateStatistics();

            if (typeof window.showToast === "function") {
                window.showToast(
                    "Focus statistics have been reset.",
                    "success"
                );
            }

        });

    // =====================================
    // INITIALISE
    // =====================================

    timerProgress.style.strokeDasharray = circleLength;

    updateTimerDisplay();
    updateStatistics();

})();
</script>

<script src="../assets/js/script.js"></script>

</body>
</html>
