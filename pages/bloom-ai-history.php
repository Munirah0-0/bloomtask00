
<?php
// =============================================
// BLOOMTASK - BLOOM AI CONVERSATION HISTORY
// =============================================

require_once "../includes/auth.php";
require_once "../includes/functions.php";
require_once "../config/db.php";

header("Content-Type: application/json; charset=UTF-8");

$userId = (int) $_SESSION["user_id"];

// =============================================
// JSON RESPONSE
// =============================================

function historyResponse($success, $data, $status = 200) {

    http_response_code($status);

    echo json_encode([
        "success" => $success,
        "data" => $data
    ]);

    exit;
}

// =============================================
// CSRF TOKEN
// =============================================

if (empty($_SESSION["bloom_ai_csrf"])) {
    $_SESSION["bloom_ai_csrf"] = bin2hex(random_bytes(32));
}

// =============================================
// REQUEST ACTION
// =============================================

$method = $_SERVER["REQUEST_METHOD"];

$action = $method === "GET"
    ? ($_GET["action"] ?? "list")
    : "";

try {

    // =========================================
    // GET CSRF TOKEN
    // =========================================

    if ($method === "GET" && $action === "token") {

        historyResponse(true, [
            "csrf_token" => $_SESSION["bloom_ai_csrf"]
        ]);
    }

    // =========================================
    // LIST CONVERSATIONS
    // =========================================

    if ($method === "GET" && $action === "list") {

        $stmt = $pdo->prepare("
            SELECT
                id,
                title,
                created_at,
                updated_at
            FROM ai_conversations
            WHERE user_id = :user_id
            ORDER BY updated_at DESC
            LIMIT 100
        ");

        $stmt->execute([
            "user_id" => $userId
        ]);

        historyResponse(true, [
            "conversations" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    // =========================================
    // LOAD CONVERSATION
    // =========================================

    if ($method === "GET" && $action === "load") {

        $conversationId = filter_input(
            INPUT_GET,
            "conversation_id",
            FILTER_VALIDATE_INT
        );

        if (!$conversationId || $conversationId < 1) {
            historyResponse(false, "Invalid conversation ID.", 400);
        }

        $stmt = $pdo->prepare("
            SELECT id, title, created_at, updated_at
            FROM ai_conversations
            WHERE id = :conversation_id
              AND user_id = :user_id
            LIMIT 1
        ");

        $stmt->execute([
            "conversation_id" => $conversationId,
            "user_id" => $userId
        ]);

        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$conversation) {
            historyResponse(false, "Conversation not found.", 404);
        }

        $stmt = $pdo->prepare("
            SELECT id, role, message, created_at
            FROM ai_messages
            WHERE conversation_id = :conversation_id
            ORDER BY id ASC
        ");

        $stmt->execute([
            "conversation_id" => $conversationId
        ]);

        historyResponse(true, [
            "conversation" => $conversation,
            "messages" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    // =========================================
    // DELETE CONVERSATION
    // =========================================

    if ($method === "POST") {

        $input = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($input)) {
            historyResponse(false, "Invalid request data.", 400);
        }

        $token = $input["csrf_token"] ?? "";

        if (
            !is_string($token) ||
            !hash_equals($_SESSION["bloom_ai_csrf"], $token)
        ) {
            historyResponse(false, "Security verification failed. Refresh the page and try again.", 403);
        }

        if (($input["action"] ?? "") !== "delete") {
            historyResponse(false, "Unknown action.", 400);
        }

        $conversationId = filter_var(
            $input["conversation_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$conversationId || $conversationId < 1) {
            historyResponse(false, "Invalid conversation ID.", 400);
        }

        // Confirm ownership before deleting.
        $stmt = $pdo->prepare("
            DELETE FROM ai_conversations
            WHERE id = :conversation_id
              AND user_id = :user_id
        ");

        $stmt->execute([
            "conversation_id" => $conversationId,
            "user_id" => $userId
        ]);

        if ($stmt->rowCount() === 0) {
            historyResponse(false, "Conversation not found.", 404);
        }

        // Related messages are deleted automatically
        // through ON DELETE CASCADE.

        historyResponse(true, [
            "message" => "Conversation deleted successfully."
        ]);
    }

    // =========================================
    // INVALID REQUEST
    // =========================================

    if ($method !== "GET" && $method !== "POST") {
        historyResponse(false, "Invalid request method.", 405);
    }

    historyResponse(false, "Unknown history action.", 400);

} catch (PDOException $error) {

    error_log("Bloom AI history error: " . $error->getMessage());

    historyResponse(
        false,
        "Unable to process conversation history.",
        500
    );
}
