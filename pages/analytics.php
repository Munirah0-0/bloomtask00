
<?php
require_once "../includes/auth.php";
require_once "../includes/functions.php";
require_once "../config/db.php";

$userId = (int) $_SESSION["user_id"];

/* =========================================
   BLOOMTASK 2.0 - ANALYTICS
========================================= */

// Overall task statistics
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'Pending'
            AND due_date < CURDATE() THEN 1 ELSE 0 END) AS overdue
    FROM tasks
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$total = (int) ($stats["total"] ?? 0);
$completed = (int) ($stats["completed"] ?? 0);
$pending = (int) ($stats["pending"] ?? 0);
$overdue = (int) ($stats["overdue"] ?? 0);

$completionRate = $total > 0
    ? round(($completed / $total) * 100)
    : 0;

// Priority distribution
$stmt = $pdo->prepare("
    SELECT priority, COUNT(*) AS total
    FROM tasks
    WHERE user_id = ?
    GROUP BY priority
");
$stmt->execute([$userId]);

$priorities = [
    "High" => 0,
    "Medium" => 0,
    "Low" => 0
];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (isset($priorities[$row["priority"]])) {
        $priorities[$row["priority"]] = (int) $row["total"];
    }
}

// Category distribution
$stmt = $pdo->prepare("
    SELECT
        COALESCE(NULLIF(category, ''), 'Uncategorised') AS name,
        COUNT(*) AS total
    FROM tasks
    WHERE user_id = ?
    GROUP BY COALESCE(NULLIF(category, ''), 'Uncategorised')
");
$stmt->execute([$userId]);

$categories = [];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $categories[] = [
        "x" => $row["name"],
        "y" => (int) $row["total"]
    ];
}

// Monthly productivity (last 6 months)
$months = [];
$created = [];
$finished = [];

for ($i = 5; $i >= 0; $i--) {
    $date = new DateTime("first day of this month");
    $date->modify("-$i months");

    $key = $date->format("Y-m");

    $months[$key] = $date->format("M Y");
    $created[$key] = 0;
    $finished[$key] = 0;
}

$startDate = array_key_first($months) . "-01";

$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
           COUNT(*) AS total
    FROM tasks
    WHERE user_id = ?
      AND created_at >= ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
");
$stmt->execute([$userId, $startDate]);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (isset($created[$row["month"]])) {
        $created[$row["month"]] = (int) $row["total"];
    }
}

$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(completed_at, '%Y-%m') AS month,
           COUNT(*) AS total
    FROM tasks
    WHERE user_id = ?
      AND status = 'Completed'
      AND completed_at IS NOT NULL
      AND completed_at >= ?
    GROUP BY DATE_FORMAT(completed_at, '%Y-%m')
");
$stmt->execute([$userId, $startDate]);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (isset($finished[$row["month"]])) {
        $finished[$row["month"]] = (int) $row["total"];
    }
}

// 13-week completion activity heatmap
$weekStart = new DateTime("monday this week");
$weekStart->modify("-12 weeks");

$stmt = $pdo->prepare("
    SELECT DATE(completed_at) AS day,
           COUNT(*) AS total
    FROM tasks
    WHERE user_id = ?
      AND status = 'Completed'
      AND completed_at >= ?
    GROUP BY DATE(completed_at)
");
$stmt->execute([
    $userId,
    $weekStart->format("Y-m-d")
]);

$activity = [];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $activity[$row["day"]] = (int) $row["total"];
}

$weekdays = [
    "Mon", "Tue", "Wed", "Thu",
    "Fri", "Sat", "Sun"
];

$heatmap = [];

foreach ($weekdays as $dayNumber => $dayName) {

    $points = [];

    for ($week = 0; $week < 13; $week++) {

        $date = clone $weekStart;
        $date->modify("+$week weeks");
        $date->modify("+$dayNumber days");

        $key = $date->format("Y-m-d");

        $points[] = [
            "x" => "W" . ($week + 1),
            "y" => $activity[$key] ?? 0
        ];
    }

    $heatmap[] = [
        "name" => $dayName,
        "data" => $points
    ];
}

// Productivity insight
if ($total === 0) {
    $insight = "Add your first task to start tracking your productivity.";
} elseif ($overdue > 0) {
    $insight = "You have $overdue overdue task(s). Consider completing these first.";
} elseif ($completionRate >= 75) {
    $insight = "Excellent progress! You have completed $completionRate% of your tasks.";
} else {
    $insight = "You have $pending pending task(s). Keep building your productivity!";
}

function chartJSON($data) {
    return json_encode(
        $data,
        JSON_HEX_TAG | JSON_HEX_APOS |
        JSON_HEX_AMP | JSON_HEX_QUOT
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Analytics | BloomTask</title>

    <link rel="icon"
          type="image/svg+xml"
          href="../assets/images/favicon.svg">

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        /* BLOOMTASK ADVANCED ANALYTICS */

        .analytics-page {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .analytics-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .analytics-heading h1 {
            margin: 0 0 7px;
        }

        .analytics-heading p {
            color: var(--text-muted);
            margin: 0;
        }

        .analytics-badge {
            border: 1px solid var(--border);
            border-radius: 30px;
            padding: 9px 14px;
            font-size: 12px;
            color: var(--gold);
        }

        .analytics-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .analytics-stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 22px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .analytics-stat-card:hover {
            transform: translateY(-5px);
        }

        .analytics-stat-label {
            color: var(--text-muted);
            font-size: 13px;
        }

        .analytics-stat-number {
            font-size: 34px;
            font-weight: 800;
            color: var(--text);
            margin: 12px 0;
        }

        .analytics-stat-note {
            color: var(--text-muted);
            font-size: 12px;
        }

        .analytics-stat-card::after {
            content: "";
            position: absolute;
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: rgba(198, 161, 91, 0.08);
            right: -35px;
            top: -35px;
        }

        .analytics-insight {
            display: flex;
            gap: 15px;
            align-items: center;
            padding: 20px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-left: 4px solid var(--gold);
            border-radius: 15px;
        }

        .analytics-insight-icon {
            font-size: 28px;
            color: var(--gold);
        }

        .analytics-insight h3 {
            margin: 0 0 5px;
            color: var(--text);
        }

        .analytics-insight p {
            margin: 0;
            font-size: 13px;
            color: var(--text-muted);
        }

        .analytics-charts {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .analytics-chart-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 22px;
            min-width: 0;
            transition: box-shadow 0.3s ease;
        }

        .analytics-chart-card:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .analytics-chart-card.wide {
            grid-column: 1 / -1;
        }

        .analytics-chart-header {
            margin-bottom: 15px;
        }

        .analytics-chart-header h2 {
            font-size: 17px;
            margin: 0 0 6px;
            color: var(--text);
        }

        .analytics-chart-header p {
            font-size: 12px;
            color: var(--text-muted);
            margin: 0;
        }

        .analytics-chart {
            width: 100%;
            min-height: 300px;
        }

        .analytics-chart.large {
            min-height: 350px;
        }

        .chart-error {
            color: var(--text-muted);
            text-align: center;
            padding: 80px 15px;
        }

        @media (max-width: 950px) {
            .analytics-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 650px) {
            .analytics-charts {
                grid-template-columns: 1fr;
            }

            .analytics-chart-card.wide {
                grid-column: auto;
            }

            .analytics-stat-number {
                font-size: 27px;
            }

            .analytics-chart-card {
                padding: 15px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .analytics-page * {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body>

<div class="app-container">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content analytics-page">

        <!-- PAGE HEADER -->
        <div class="analytics-heading">

            <div>
                <h1>Productivity Analytics</h1>
                <p>Explore your productivity performance and task patterns.</p>
            </div>

            <div class="analytics-badge">
                ✦ BloomTask Intelligence
            </div>

        </div>

        <!-- STATISTICS -->
        <section class="analytics-stats">

            <div class="analytics-stat-card">
                <div class="analytics-stat-label">Total Tasks</div>
                <div class="analytics-stat-number"
                     data-count="<?= $total ?>">0</div>
                <div class="analytics-stat-note">All recorded tasks</div>
            </div>

            <div class="analytics-stat-card">
                <div class="analytics-stat-label">Completed Tasks</div>
                <div class="analytics-stat-number"
                     data-count="<?= $completed ?>">0</div>
                <div class="analytics-stat-note">Successfully completed</div>
            </div>

            <div class="analytics-stat-card">
                <div class="analytics-stat-label">Pending Tasks</div>
                <div class="analytics-stat-number"
                     data-count="<?= $pending ?>">0</div>
                <div class="analytics-stat-note">Waiting for completion</div>
            </div>

            <div class="analytics-stat-card">
                <div class="analytics-stat-label">Overdue Tasks</div>
                <div class="analytics-stat-number"
                     data-count="<?= $overdue ?>">0</div>
                <div class="analytics-stat-note">Past their due dates</div>
            </div>

        </section>

        <!-- INTELLIGENT INSIGHT -->
        <section class="analytics-insight">

            <div class="analytics-insight-icon">✦</div>

            <div>
                <h3>Bloom Productivity Insight</h3>
                <p><?= e($insight) ?></p>
            </div>

        </section>

        <!-- ADVANCED CHARTS -->
        <section class="analytics-charts">

            <div class="analytics-chart-card">

                <div class="analytics-chart-header">
                    <h2>Completion Performance</h2>
                    <p>Animated radial productivity gauge</p>
                </div>

                <div id="completionChart"
                     class="analytics-chart"></div>

            </div>

            <div class="analytics-chart-card">

                <div class="analytics-chart-header">
                    <h2>Task Priority Distribution</h2>
                    <p>Interactive priority breakdown</p>
                </div>

                <div id="priorityChart"
                     class="analytics-chart"></div>

            </div>

            <div class="analytics-chart-card wide">

                <div class="analytics-chart-header">
                    <h2>Monthly Productivity Trends</h2>
                    <p>Created tasks versus completed tasks over six months</p>
                </div>

                <div id="monthlyChart"
                     class="analytics-chart large"></div>

            </div>

            <div class="analytics-chart-card wide">

                <div class="analytics-chart-header">
                    <h2>Productivity Activity Heatmap</h2>
                    <p>Task completion activity over the last 13 weeks</p>
                </div>

                <div id="heatmapChart"
                     class="analytics-chart large"></div>

            </div>

            <div class="analytics-chart-card wide">

                <div class="analytics-chart-header">
                    <h2>Category Workload Treemap</h2>
                    <p>Visual breakdown of tasks by category</p>
                </div>

                <div id="categoryChart"
                     class="analytics-chart large"></div>

            </div>

        </section>

    </main>

</div>

<script src="../assets/js/script.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    /* =========================================
       APEXCHARTS SETUP
    ========================================= */

    if (typeof ApexCharts === "undefined") {
        document.querySelectorAll(".analytics-chart").forEach(function (element) {
            element.innerHTML =
                '<div class="chart-error">Unable to load charts. Please check your internet connection.</div>';
        });
        return;
    }

    const lightMode =
        document.documentElement.getAttribute("data-theme") === "light";

    const textColor = lightMode ? "#756d61" : "#c5bfb5";
    const gridColor = lightMode ? "#e6ded2" : "#38332e";

    const gold = "#c6a15b";
    const green = "#79ad8d";
    const red = "#cf7770";

    const baseChart = {
        chart: {
            background: "transparent",
            foreColor: textColor,
            fontFamily: "Segoe UI, Arial, sans-serif",

            animations: {
                enabled: true,
                easing: "easeinout",
                speed: 1200,
                animateGradually: {
                    enabled: true,
                    delay: 150
                },
                dynamicAnimation: {
                    enabled: true,
                    speed: 500
                }
            },

            toolbar: {
                show: true
            }
        },

        grid: {
            borderColor: gridColor,
            strokeDashArray: 4
        },

        tooltip: {
            theme: lightMode ? "light" : "dark"
        },

        noData: {
            text: "No task data available yet"
        }
    };

    /* =========================================
       ANIMATED STATISTIC NUMBERS
    ========================================= */

    document.querySelectorAll("[data-count]").forEach(function (element) {

        const target = Number(element.dataset.count);
        const duration = 1000;
        const start = performance.now();

        function animate(now) {

            const progress = Math.min((now - start) / duration, 1);

            const eased = 1 - Math.pow(1 - progress, 3);

            element.textContent =
                Math.round(target * eased).toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(animate);
            }

        }

        requestAnimationFrame(animate);

    });

    /* =========================================
       1. RADIAL COMPLETION CHART
    ========================================= */

    new ApexCharts(
        document.querySelector("#completionChart"),
        {
            ...baseChart,

            series: [<?= $completionRate ?>],

            chart: {
                ...baseChart.chart,
                type: "radialBar",
                height: 310
            },

            colors: [green],

            plotOptions: {
                radialBar: {
                    startAngle: -135,
                    endAngle: 225,

                    hollow: {
                        size: "65%"
                    },

                    track: {
                        background: gridColor
                    },

                    dataLabels: {
                        name: {
                            show: true,
                            color: textColor,
                            offsetY: 25
                        },

                        value: {
                            show: true,
                            fontSize: "36px",
                            fontWeight: 800,
                            offsetY: -15,

                            formatter: function (value) {
                                return Math.round(value) + "%";
                            }
                        }
                    }
                }
            },

            stroke: {
                lineCap: "round"
            },

            labels: ["Completed"]
        }
    ).render();

    /* =========================================
       2. PRIORITY DONUT CHART
    ========================================= */

    new ApexCharts(
        document.querySelector("#priorityChart"),
        {
            ...baseChart,

            series: <?= chartJSON(array_values($priorities)) ?>,

            labels: ["High", "Medium", "Low"],

            chart: {
                ...baseChart.chart,
                type: "donut",
                height: 310
            },

            colors: [red, gold, green],

            plotOptions: {
                pie: {
                    donut: {
                        size: "68%",

                        labels: {
                            show: true,

                            total: {
                                show: true,
                                label: "Total Tasks"
                            }
                        }
                    }
                }
            },

            legend: {
                position: "bottom"
            },

            dataLabels: {
                enabled: true
            }
        }
    ).render();

    /* =========================================
       3. MONTHLY MIXED CHART
    ========================================= */

    new ApexCharts(
        document.querySelector("#monthlyChart"),
        {
            ...baseChart,

            series: [
                {
                    name: "Created",
                    type: "column",
                    data: <?= chartJSON(array_values($created)) ?>
                },
                {
                    name: "Completed",
                    type: "area",
                    data: <?= chartJSON(array_values($finished)) ?>
                }
            ],

            chart: {
                ...baseChart.chart,
                type: "line",
                height: 350
            },

            colors: [gold, green],

            stroke: {
                width: [0, 3],
                curve: "smooth"
            },

            fill: {
                type: "gradient",
                opacity: [0.9, 0.3],

                gradient: {
                    opacityFrom: 0.6,
                    opacityTo: 0.05
                }
            },

            plotOptions: {
                bar: {
                    columnWidth: "40%",
                    borderRadius: 6
                }
            },

            markers: {
                size: [0, 5]
            },

            xaxis: {
                categories: <?= chartJSON(array_values($months)) ?>
            },

            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: {
                    formatter: function (value) {
                        return Math.round(value);
                    }
                }
            },

            legend: {
                position: "top"
            }
        }
    ).render();

    /* =========================================
       4. ACTIVITY HEATMAP
    ========================================= */

    new ApexCharts(
        document.querySelector("#heatmapChart"),
        {
            ...baseChart,

            series: <?= chartJSON($heatmap) ?>,

            chart: {
                ...baseChart.chart,
                type: "heatmap",
                height: 350
            },

            dataLabels: {
                enabled: false
            },

            plotOptions: {
                heatmap: {
                    radius: 5,
                    shadeIntensity: 0.5,

                    colorScale: {
                        ranges: [
                            {
                                from: 0,
                                to: 0,
                                color: "#39352f",
                                name: "No Activity"
                            },
                            {
                                from: 1,
                                to: 1,
                                color: "#30281d",
                                name: "Low"
                            },
                            {
                                from: 2,
                                to: 3,
                                color: "#c6a15b",
                                name: "Medium"
                            },
                            {
                                from: 4,
                                to: 999,
                                color: "#79ad8d",
                                name: "High"
                            }
                        ]
                    }
                }
            },

            stroke: {
                width: 2
            },

            tooltip: {
                theme: lightMode ? "light" : "dark",

                y: {
                    formatter: function (value) {
                        return value + " task(s) completed";
                    }
                }
            }
        }
    ).render();

    /* =========================================
       5. CATEGORY TREEMAP
    ========================================= */

    new ApexCharts(
        document.querySelector("#categoryChart"),
        {
            ...baseChart,

            series: [
                {
                    data: <?= chartJSON($categories) ?>
                }
            ],

            chart: {
                ...baseChart.chart,
                type: "treemap",
                height: 350
            },

            colors: [
                "#c6a15b",
                "#79ad8d",
                "#c97870",
                "#8198b5",
                "#a68ab7",
                "#d4a373"
            ],

            plotOptions: {
                treemap: {
                    distributed: true,
                    enableShades: true,
                    shadeIntensity: 0.4
                }
            },

            dataLabels: {
                enabled: true,

                style: {
                    fontSize: "13px",
                    fontWeight: 700
                }
            },

            legend: {
                show: false
            }
        }
    ).render();

    /* =========================================
       THEME REFRESH FOR CHART COLOURS
    ========================================= */

    document.addEventListener("click", function (event) {

        if (event.target.closest(
            "[data-theme-toggle], #themeToggle, [data-theme-choice], #reset-theme"
        )) {
            setTimeout(function () {
                window.location.reload();
            }, 200);
        }

    });

});
</script>

</body>
</html>