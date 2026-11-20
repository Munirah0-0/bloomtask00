<?php
require_once "../includes/auth.php";
require_once "../includes/functions.php";
require_once "../config/db.php";

$userId = $_SESSION["user_id"];

$categories = ["Study", "Personal", "Work", "Others"];
$priorities = ["Low", "Medium", "High"];

$error = "";

// =========================================
// POST ACTIONS
// =========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verify_csrf();

    $action = $_POST["action"] ?? "";

    // ADD TASK
    if ($action === "add") {

        $title = trim($_POST["title"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $category = $_POST["category"] ?? "Others";
        $priority = $_POST["priority"] ?? "Medium";
        $dueDate = $_POST["due_date"] ?? "";

        if ($title === "") {
            $error = "Task title is required.";
        } elseif (strlen($title) > 150) {
            $error = "Title cannot exceed 150 characters.";
        } elseif (!in_array($category, $categories, true)) {
            $error = "Invalid category.";
        } elseif (!in_array($priority, $priorities, true)) {
            $error = "Invalid priority.";
        } elseif ($dueDate !== "" &&
            !DateTime::createFromFormat("Y-m-d", $dueDate)) {
            $error = "Invalid due date.";
        } else {

            $stmt = $pdo->prepare("
                INSERT INTO tasks
                (user_id,title,description,category,priority,due_date,status)
                VALUES
                (:user_id,:title,:description,:category,:priority,:due_date,'Pending')
            ");

            $stmt->execute([
                "user_id" => $userId,
                "title" => $title,
                "description" => $description,
                "category" => $category,
                "priority" => $priority,
                "due_date" => $dueDate ?: null
            ]);

            set_flash("success", "Task created successfully.");
            redirect("tasks.php");
        }
    }

    // UPDATE TASK
    elseif ($action === "update") {

        $taskId = (int)($_POST["task_id"] ?? 0);
        $title = trim($_POST["title"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $category = $_POST["category"] ?? "Others";
        $priority = $_POST["priority"] ?? "Medium";
        $dueDate = $_POST["due_date"] ?? "";

        if ($taskId <= 0 || $title === "") {
            $error = "Please provide a valid task title.";
        } elseif (strlen($title) > 150) {
            $error = "Title cannot exceed 150 characters.";
        } elseif (!in_array($category, $categories, true)) {
            $error = "Invalid category.";
        } elseif (!in_array($priority, $priorities, true)) {
            $error = "Invalid priority.";
        } elseif ($dueDate !== "" &&
            !DateTime::createFromFormat("Y-m-d", $dueDate)) {
            $error = "Invalid due date.";
        } else {

            $stmt = $pdo->prepare("
                UPDATE tasks
                SET title=:title,
                    description=:description,
                    category=:category,
                    priority=:priority,
                    due_date=:due_date
                WHERE id=:id AND user_id=:user_id
            ");

            $stmt->execute([
                "title" => $title,
                "description" => $description,
                "category" => $category,
                "priority" => $priority,
                "due_date" => $dueDate ?: null,
                "id" => $taskId,
                "user_id" => $userId
            ]);

            set_flash("success", "Task updated successfully.");
            redirect("tasks.php");
        }
    }

    // COMPLETE / UNDO
    elseif ($action === "toggle") {

        $taskId = (int)($_POST["task_id"] ?? 0);

        $stmt = $pdo->prepare("
            SELECT status FROM tasks
            WHERE id=:id AND user_id=:user_id
        ");

        $stmt->execute([
            "id" => $taskId,
            "user_id" => $userId
        ]);

        $task = $stmt->fetch();

        if ($task) {

            if ($task["status"] === "Completed") {
                $stmt = $pdo->prepare("
                    UPDATE tasks
                    SET status='Pending', completed_at=NULL
                    WHERE id=:id AND user_id=:user_id
                ");

                $message = "Task moved back to pending.";

            } else {
                $stmt = $pdo->prepare("
                    UPDATE tasks
                    SET status='Completed', completed_at=NOW()
                    WHERE id=:id AND user_id=:user_id
                ");

                $message = "Congratulations! Task completed.";
            }

            $stmt->execute([
                "id" => $taskId,
                "user_id" => $userId
            ]);

            set_flash("success", $message);

        } else {
            set_flash("error", "Task not found.");
        }

        redirect("tasks.php");
    }

    // DELETE
    elseif ($action === "delete") {

        $taskId = (int)($_POST["task_id"] ?? 0);

        $stmt = $pdo->prepare("
            DELETE FROM tasks
            WHERE id=:id AND user_id=:user_id
        ");

        $stmt->execute([
            "id" => $taskId,
            "user_id" => $userId
        ]);

        set_flash("success", "Task deleted successfully.");
        redirect("tasks.php");
    }
}

// =========================================
// FLASH
// =========================================

$flash = get_flash();

// =========================================
// EDIT TASK
// =========================================

$editTask = null;

if (isset($_GET["edit"])) {

    $stmt = $pdo->prepare("
        SELECT * FROM tasks
        WHERE id=:id AND user_id=:user_id
    ");

    $stmt->execute([
        "id" => (int)$_GET["edit"],
        "user_id" => $userId
    ]);

    $editTask = $stmt->fetch();

    if (!$editTask) {
        set_flash("error", "Task not found.");
        redirect("tasks.php");
    }
}

// =========================================
// SEARCH / FILTER / SORT
// =========================================

$search = trim($_GET["search"] ?? "");
$searchMode = $search !== "";

$statusFilter = $_GET["status"] ?? "";
$priorityFilter = $_GET["priority"] ?? "";
$categoryFilter = $_GET["category"] ?? "";
$sort = $_GET["sort"] ?? "newest";

$sql = "SELECT * FROM tasks WHERE user_id=:user_id";
$params = ["user_id" => $userId];

if ($search !== "") {
    $sql .= " AND (title LIKE :search_title OR description LIKE :search_description)";
    $params["search_title"] = "%".$search."%";
    $params["search_description"] = "%".$search."%";
}

if (in_array($statusFilter, ["Pending","Completed"], true)) {
    $sql .= " AND status=:status";
    $params["status"] = $statusFilter;
}

if (in_array($priorityFilter, $priorities, true)) {
    $sql .= " AND priority=:priority";
    $params["priority"] = $priorityFilter;
}

if (in_array($categoryFilter, $categories, true)) {
    $sql .= " AND category=:category";
    $params["category"] = $categoryFilter;
}

$orderOptions = [
    "newest" => "created_at DESC",
    "oldest" => "created_at ASC",
    "due_date" => "due_date IS NULL, due_date ASC",
    "priority" => "FIELD(priority,'High','Medium','Low')"
];

$sql .= " ORDER BY ".$orderOptions[$sort] ?? $orderOptions["newest"];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// =========================================
// CSV EXPORT
// =========================================

if (isset($_GET["export"]) && $_GET["export"] === "csv") {

    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=bloomtask_tasks.csv");

    $output = fopen("php://output", "w");
    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        "Title","Description","Category",
        "Priority","Due Date","Status","Created At"
    ]);

    foreach ($tasks as $task) {

    $title = preg_match('/^[=+\-@]/', $task["title"])
            ? "'".$task["title"] : $task["title"];

        $description = $task["description"] ?? "";

        if (preg_match('/^[=+\-@]/', $description)) {
            $description = "'".$description;
        }

        fputcsv($output, [
            $title,
            $description,
            $task["category"],
            $task["priority"],
            $task["due_date"],
            $task["status"],
            $task["created_at"]
        ]);
    }

    fclose($output);
    exit;
}

// =========================================
// TASK COUNTS
// =========================================

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total,
           SUM(status='Pending') AS pending,
           SUM(status='Completed') AS completed
    FROM tasks WHERE user_id=:user_id
");

$stmt->execute(["user_id"=>$userId]);
$counts = $stmt->fetch();
$totalTasks = (int)($counts["total"] ?? 0);
$pendingTasks = (int)($counts["pending"] ?? 0);
$completedTasks = (int)($counts["completed"] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Tasks | BloomTask</title>
<link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
<link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="app-layout">

<?php include "../includes/sidebar.php"; ?>

<main class="main-content">
<div class="page-container">

<div class="page-header">
<div class="page-header-top">
<button type="button" class="mobile-menu-button" id="mobileMenuButton">☰</button>
<div>
<h1><?= $searchMode ? "Search Tasks" : "My Tasks" ?></h1>
<p class="page-subtitle">
<?= $searchMode ? "Find exactly what you are looking for." : "Organise your responsibilities and make progress." ?>
</p>
</div>
</div>

<?php if (!$searchMode): ?>
<a href="tasks.php#taskForm" class="btn btn-primary">+ New Task</a>
<?php endif; ?>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flash["type"]) ?>" data-auto-dismiss>
<?= e($flash["message"]) ?>
</div>
<?php endif; ?>

<?php if ($error !== ""): ?>
<div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<!-- NORMAL MODE -->
<?php if (!$searchMode): ?>

<section class="card-grid">
<div class="stat-card">
<div class="stat-label">All Tasks</div>
<div class="stat-value"><?= $totalTasks ?></div>
</div>

<div class="stat-card">
<div class="stat-label">Pending</div>
<div class="stat-value"><?= $pendingTasks ?></div>
</div>

<div class="stat-card">
<div class="stat-label">Completed</div>
<div class="stat-value"><?= $completedTasks ?></div>
</div>

<div class="stat-card">
<div class="stat-label">Showing</div>
<div class="stat-value"><?= count($tasks) ?></div>
</div>
</section>

<section class="card mb-3" id="taskForm">
<div class="card-header">
<h2><?= $editTask ? "Edit Task" : "Create a New Task" ?></h2>
<?php if ($editTask): ?>
<a href="tasks.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
<?php endif; ?>
</div>

<form method="POST" action="tasks.php">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="<?= $editTask ? "update" : "add" ?>">

<?php if ($editTask): ?>
<input type="hidden" name="task_id" value="<?= (int)$editTask["id"] ?>">
<?php endif; ?>

<div class="form-group">
<label class="form-label">Task Title *</label>
<input type="text" name="title" class="form-control" maxlength="150"
value="<?= e($editTask["title"] ?? "") ?>" required>
</div>

<div class="form-group">
<label class="form-label">Description</label>
<textarea name="description" class="form-control"><?= e($editTask["description"] ?? "") ?></textarea>
</div>

<div class="form-row">
<div class="form-group">
<label class="form-label">Category</label>
<select name="category" class="form-control">
<?php foreach ($categories as $category): ?>
<option value="<?= e($category) ?>"
<?= ($editTask["category"] ?? "Others")===$category ? "selected":"" ?>>
<?= e($category) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group">
<label class="form-label">Priority</label>
<select name="priority" class="form-control">
<?php foreach ($priorities as $priority): ?>
<option value="<?= e($priority) ?>"
<?= ($editTask["priority"] ?? "Medium")===$priority ? "selected":"" ?>>
<?= e($priority) ?>
</option>
<?php endforeach; ?>
</select>
</div>
</div>

<div class="form-group">
<label class="form-label">Due Date</label>
<input type="date" name="due_date" class="form-control"
value="<?= e($editTask["due_date"] ?? "") ?>">
</div>

<button type="submit" class="btn btn-primary">
<?= $editTask ? "Save Changes" : "Create Task" ?>
</button>
</form>
</section>

<section class="card mb-3">
<div class="card-header"><h3>Find Your Tasks</h3></div>

<form method="GET" action="tasks.php">
<div class="filter-bar">

<input type="search" name="search" class="form-control search-box"
placeholder="Search tasks..." value="<?= e($search) ?>">

<select name="status" class="form-control">
<option value="">All Statuses</option>
<option value="Pending" <?= $statusFilter==="Pending"?"selected":"" ?>>Pending</option>
<option value="Completed" <?= $statusFilter==="Completed"?"selected":"" ?>>Completed</option>
</select>

<select name="priority" class="form-control">
<option value="">All Priorities</option>
<?php foreach ($priorities as $priority): ?>
<option value="<?= e($priority) ?>" <?= $priorityFilter===$priority?"selected":"" ?>>
<?= e($priority) ?>
</option>
<?php endforeach; ?>
</select>

<select name="category" class="form-control">
<option value="">All Categories</option>
<?php foreach ($categories as $category): ?>
<option value="<?= e($category) ?>" <?= $categoryFilter===$category?"selected":"" ?>>
<?= e($category) ?>
</option>
<?php endforeach; ?>
</select>

<select name="sort" class="form-control">
<option value="newest">Newest</option>
<option value="oldest" <?= $sort==="oldest"?"selected":"" ?>>Oldest</option>
<option value="due_date" <?= $sort==="due_date"?"selected":"" ?>>Due Date</option>
<option value="priority" <?= $sort==="priority"?"selected":"" ?>>Priority</option>
</select>

<button type="submit" class="btn btn-primary">Apply</button>
<a href="tasks.php" class="btn btn-secondary">Reset</a>

</div>
</form>
</section>

<?php else: ?>

<section class="card mb-3">
<div class="card-header">
<h2>Search Results</h2>
<a href="tasks.php" class="btn btn-secondary btn-sm">Clear Search</a>
</div>

<p class="page-subtitle">
<?= count($tasks) ?> result(s) found for
<strong>"<?= e($search) ?>"</strong>
</p>

<form method="GET" action="tasks.php">
<div class="filter-bar">
<input type="search" name="search" class="form-control search-box"
value="<?= e($search) ?>" placeholder="Search tasks..." required>
<button type="submit" class="btn btn-primary">Search Again</button>
</div>
</form>
</section>

<?php endif; ?>

<!-- TASK RESULTS -->
<section class="card">

<div class="card-header">
<h2><?= $searchMode ? "Matching Tasks" : "Your Task Collection" ?></h2>

<form method="GET" action="tasks.php">
<?php foreach ($_GET as $key=>$value): ?>
<?php if ($key!=="export"): ?>
<input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
<?php endif; ?>
<?php endforeach; ?>
<input type="hidden" name="export" value="csv">
<button type="submit" class="btn btn-secondary btn-sm">Export CSV</button>
</form>
</div>

<?php if (empty($tasks)): ?>

<div class="empty-state">
<div class="empty-icon">✦</div>
<h3><?= $searchMode ? "No matching tasks found" : "No tasks found" ?></h3>
<p><?= $searchMode ? "Try another keyword." : "Create your first task to get started." ?></p>
</div>

<?php else: ?>

<div class="task-list">

<?php foreach ($tasks as $task): ?>

<?php
$isCompleted = $task["status"]==="Completed";
$isOverdue = !$isCompleted && !empty($task["due_date"]) && $task["due_date"]<date("Y-m-d");

$priorityClass = $task["priority"]==="High" ? "badge-danger" :
                 ($task["priority"]==="Low" ? "badge-success" : "badge-gold");
?>

<div class="task-item <?= $isCompleted ? "completed" : "" ?>">

<form method="POST" action="tasks.php">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="toggle">
<input type="hidden" name="task_id" value="<?= (int)$task["id"] ?>">
<button type="submit" class="btn btn-secondary btn-sm">
<?= $isCompleted ? "↶ Undo" : "✓ Done" ?>
</button>
</form>

<div class="task-details">
<div class="task-title"><?= e($task["title"]) ?></div>

<?php if (!empty($task["description"])): ?>
<div class="task-meta"><?= e($task["description"]) ?></div>
<?php endif; ?>

<div class="task-meta">
<?= e($task["category"] ?: "Others") ?> ·
<?= e($task["due_date"] ?: "No deadline") ?>
</div>

<div class="flex gap-1 mt-1">
<span class="badge <?= $priorityClass ?>"><?= e($task["priority"]) ?></span>

<?php if ($isCompleted): ?>
<span class="badge badge-success">Completed</span>
<?php elseif ($isOverdue): ?>
<span class="badge badge-danger">Overdue</span>
<?php else: ?>
<span class="badge badge-warning">Pending</span>
<?php endif; ?>
</div>
</div>

<div class="task-actions">
<a href="tasks.php?edit=<?= (int)$task["id"] ?>#taskForm"
class="btn btn-secondary btn-sm">Edit</a>

<form method="POST" action="tasks.php"
onsubmit="return confirm('Delete this task?');">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="task_id" value="<?= (int)$task["id"] ?>">
<button type="submit" class="btn btn-danger btn-sm">Delete</button>
</form>
</div>

</div>

<?php endforeach; ?>

</div>
<?php endif; ?>

</section>

</div>
</main>
</div>

<script src="../assets/js/script.js"></script>
</body>
</html>