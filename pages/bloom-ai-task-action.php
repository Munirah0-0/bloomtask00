
<?php
// =============================================
// BLOOMTASK - BLOOM AI TASK ACTION
// CONFIRM AND CREATE TASK
// =============================================

require_once "../includes/auth.php";
require_once "../includes/functions.php";
require_once "../config/db.php";

header("Content-Type: application/json; charset=UTF-8");

function taskResponse($success, $data, $status = 200) {
    http_response_code($status);

    echo json_encode([
        "success" => $success,
        "data" => $data
    ]);

    exit;
}

// Only accept POST requests.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    taskResponse(false, "Invalid request method.", 405);
}

// Read JSON request.
$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    taskResponse(false, "Invalid request data.", 400);
}

// Verify CSRF token.
$submittedToken = $input["csrf_token"] ?? "";
$sessionToken = $_SESSION["csrf_token"] ?? "";

if (
    empty($submittedToken) ||
    empty($sessionToken) ||
    !hash_equals($sessionToken, $submittedToken)
) {
    taskResponse(false, "Security verification failed. Refresh the page.", 403);
}

// Only the logged-in user's ID is trusted.
$userId = (int) $_SESSION["user_id"];

$actionId = $input["action_id"] ?? "";

if (!is_string($actionId) || !preg_match('/^[a-f0-9]{64}$/', $actionId)) {
    taskResponse(false, "Invalid task confirmation.", 400);
}

// Retrieve the pending task proposal from this user's session.
$pending = $_SESSION["bloom_ai_pending_task"] ?? null;

if (
    !is_array($pending) ||
    !isset($pending["id"], $pending["task"], $pending["created_at"]) ||
    !hash_equals($pending["id"], $actionId)
) {
    taskResponse(false, "This task proposal is no longer available. Ask Bloom AI to prepare it again.", 404);
}

// Expire proposals after 10 minutes.
if (time() - (int) $pending["created_at"] > 600) {
    unset($_SESSION["bloom_ai_pending_task"]);

    taskResponse(false, "This task proposal has expired. Please prepare it again.", 410);
}

$task = $pending["task"];

$title = trim($task["title"] ?? "");
$description = trim($task["description"] ?? "");
$category = $task["category"] ?? "Others";
$priority = $task["priority"] ?? "Medium";
$dueDate = $task["due_date"] ?? "";

$categories = ["Study", "Personal", "Work", "Others"];
$priorities = ["Low", "Medium", "High"];

// Validate all task details again on the server.
if ($title === "" || mb_strlen($title) > 150) {
    taskResponse(false, "The task title is invalid.", 400);
}

if (!in_array($category, $categories, true)) {
    taskResponse(false, "Invalid task category.", 400);
}

if (!in_array($priority, $priorities, true)) {
    taskResponse(false, "Invalid task priority.", 400);
}

if (!is_string($description) || mb_strlen($description) > 5000) {
    taskResponse(false, "The task description is invalid.", 400);
}

if ($dueDate !== "") {
    $date = DateTime::createFromFormat("!Y-m-d", $dueDate);
    $dateErrors = DateTime::getLastErrors();

    if (
        !$date ||
        ($dateErrors !== false &&
        ($dateErrors["warning_count"] > 0 || $dateErrors["error_count"] > 0)) ||
        $date->format("Y-m-d") !== $dueDate
    ) {
        taskResponse(false, "The task due date is invalid.", 400);
    }
}

try {
    // Create the task for this logged-in user only.
    $stmt = $pdo->prepare("
        INSERT INTO tasks
            (user_id, title, description, category, priority, due_date, status)
        VALUES
            (:user_id, :title, :description, :category, :priority, :due_date, 'Pending')
    ");

    $stmt->execute([
        "user_id" => $userId,
        "title" => $title,
        "description" => $description,
        "category" => $category,
        "priority" => $priority,
        "due_date" => $dueDate !== "" ? $dueDate : null
    ]);

    $taskId = (int) $pdo->lastInsertId();

    // Remove the proposal so it cannot be confirmed twice.
    unset($_SESSION["bloom_ai_pending_task"]);

    taskResponse(true, [
        "message" => "Task created successfully.",
        "task_id" => $taskId,
        "title" => $title
    ]);

} catch (PDOException $error) {
    error_log("Bloom AI task creation error: " . $error->getMessage());

    taskResponse(false, "Unable to create the task. Please try again.", 500);
}
