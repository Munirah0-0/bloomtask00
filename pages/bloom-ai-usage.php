
<?php
// =============================================
// BLOOMTASK - BLOOM AI DAILY USAGE
// =============================================

require_once "../includes/auth.php";
require_once "../config/db.php";

header("Content-Type: application/json; charset=UTF-8");

function usageResponse($success, $data, $status = 200) {
    http_response_code($status);

    echo json_encode([
        "success" => $success,
        "data" => $data
    ]);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    usageResponse(false, "Invalid request method.", 405);
}

$userId = (int) $_SESSION["user_id"];

// Keep this limit consistent with bloom-ai-api.php.
$dailyLimit = 30;

try {
    $stmt = $pdo->prepare("
        SELECT message_count
        FROM ai_usage
        WHERE user_id = :user_id
          AND usage_date = CURDATE()
        LIMIT 1
    ");

    $stmt->execute([
        "user_id" => $userId
    ]);

    $usage = $stmt->fetch(PDO::FETCH_ASSOC);

    $used = (int) ($usage["message_count"] ?? 0);
    $remaining = max(0, $dailyLimit - $used);

    usageResponse(true, [
        "used" => $used,
        "limit" => $dailyLimit,
        "remaining" => $remaining
    ]);

} catch (PDOException $error) {
    error_log("Bloom AI usage error: " . $error->getMessage());

    usageResponse(false, "Unable to retrieve AI usage.", 500);
}
