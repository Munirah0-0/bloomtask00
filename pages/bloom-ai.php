
<?php
require_once "../includes/auth.php";
require_once "../includes/functions.php";
require_once "../config/db.php";

$userId = (int) $_SESSION["user_id"];

// Get the user's task overview

// Get the user's task overview

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_pending,
        COUNT(
            CASE
                WHEN `priority` = 'High' THEN 1
            END
        ) AS high_count,
        COUNT(
            CASE
                WHEN `due_date` IS NOT NULL
                 AND `due_date` < CURDATE()
                THEN 1
            END
        ) AS overdue_count
    FROM tasks
    WHERE `user_id` = :user_id
      AND `status` = 'Pending'
");

$stmt->execute([
    "user_id" => $userId
]);

$overview = $stmt->fetch(PDO::FETCH_ASSOC);

$pending = (int) ($overview["total_pending"] ?? 0);
$highPriority = (int) ($overview["high_count"] ?? 0);
$overdue = (int) ($overview["overdue_count"] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Bloom AI | BloomTask</title>

    <link rel="icon" type="image/svg+xml"
          href="../assets/images/favicon.svg">

    <link rel="stylesheet"
          href="../assets/css/style.css">

    
<style>
/* =========================================
   BLOOM AI — OBSIDIAN LUXURY
========================================= */

.ai-intro {
    margin-bottom: 25px;
}

.ai-intro p {
    margin-top: 6px;
}

.ai-eyebrow {
    color: var(--gold);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
}

.ai-status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--success);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
}

.ai-status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--success);
    box-shadow: 0 0 9px var(--success);
}

/* =========================================
   MAIN LAYOUT
========================================= */

.ai-console {
    display: grid;
    grid-template-columns: 220px minmax(420px, 1fr) 260px;
    gap: 18px;
    align-items: stretch;
    width: 100%;
    min-width: 0;
}

/* =========================================
   CONVERSATION HISTORY
========================================= */

.ai-history-panel {
    min-width: 0;
    height: 760px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.ai-history-header {
    padding: 20px 16px;
    border-bottom: 1px solid var(--border);
}

.ai-history-header h3 {
    margin: 0 0 16px;
    font-size: 16px;
}

.ai-new-chat {
    width: 100%;
    padding: 12px 8px;
    background: var(--surface-3);
    color: var(--gold-light);
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 12px;
    cursor: pointer;
    transition: .2s;
}

.ai-new-chat:hover {
    border-color: var(--gold);
    background: var(--surface-2);
}

.ai-history-label {
    padding: 16px;
    color: var(--text-muted);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.8px;
}

.ai-history-list {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 5px 10px 12px;
}

.ai-history-item {
    display: block;
    width: 100%;
    padding: 12px;
    margin-bottom: 6px;
    text-align: left;
    color: var(--text-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: 7px;
    font-size: 11px;
    line-height: 1.6;
    cursor: pointer;
    overflow-wrap: anywhere;
    transition: .2s;
}

.ai-history-row {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 6px;
}

.ai-history-row .ai-history-item {
    flex: 1;
    min-width: 0;
    margin-bottom: 0;
}

.ai-delete-chat {
    flex-shrink: 0;
    padding: 8px;
    color: var(--text-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: 6px;
    cursor: pointer;
}

.ai-delete-chat:hover {
    color: var(--danger);
    background: var(--surface-2);
    border-color: var(--border);
}

.ai-history-item:hover,
.ai-history-item.active {
    color: var(--gold-light);
    background: var(--surface-2);
    border-color: var(--border);
}

.ai-history-empty,
.ai-history-loading {
    padding: 12px;
    color: var(--text-muted);
    font-size: 11px;
    line-height: 1.7;
}

/* =========================================
   MAIN CHAT
========================================= */

.ai-chat-panel {
    min-width: 0;
    height: 760px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.ai-chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding: 20px 23px;
    border-bottom: 1px solid var(--border);
}

.ai-identity {
    display: flex;
    align-items: center;
    gap: 13px;
    min-width: 0;
}

.ai-symbol {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    color: var(--gold-light);
    background: var(--surface-3);
    border: 1px solid var(--border);
    border-radius: 11px;
    font-size: 20px;
}

.ai-identity h3 {
    margin: 0;
    font-size: 15px;
}

.ai-identity p {
    margin: 4px 0 0;
    color: var(--text-muted);
    font-size: 10px;
    letter-spacing: 1px;
}

.ai-chat-body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 28px;
    display: flex;
    flex-direction: column;
    gap: 19px;
}

.ai-welcome {
    width: 100%;
    max-width: 680px;
    margin: auto;
    text-align: center;
}

.ai-welcome-symbol {
    width: 65px;
    height: 65px;
    display: grid;
    place-items: center;
    margin: 0 auto 20px;
    color: var(--gold-light);
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 18px;
    font-size: 27px;
}

.ai-welcome h2 {
    margin-bottom: 12px;
    font-family: Georgia, serif;
    font-size: clamp(25px, 2vw, 34px);
    font-weight: 500;
    line-height: 1.35;
}

.ai-welcome p {
    max-width: 500px;
    margin: auto;
    color: var(--text-muted);
    font-size: 13px;
    line-height: 1.8;
}

.ai-suggestions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-top: 28px;
}

.ai-suggestion {
    min-width: 0;
    padding: 14px;
    text-align: left;
    background: var(--surface-2);
    color: var(--text);
    border: 1px solid var(--border);
    border-radius: 9px;
    font-size: 11px;
    line-height: 1.6;
    cursor: pointer;
    transition: .2s;
}

.ai-suggestion:hover {
    border-color: var(--gold);
    color: var(--gold);
    transform: translateY(-2px);
}

/* =========================================
   CHAT MESSAGES
========================================= */

.ai-message {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    max-width: 88%;
}

.ai-message.user {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.ai-message-icon {
    width: 30px;
    height: 30px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    color: var(--gold);
    background: var(--surface-3);
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 12px;
}

.ai-message.user .ai-message-icon {
    color: var(--text);
}

.ai-message-content {
    padding: 12px 15px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 10px;
    color: var(--text);
    font-size: 13px;
    line-height: 1.8;
    overflow-wrap: anywhere;
}

.ai-message.user .ai-message-content {
    background: var(--surface-3);
}

.ai-message-content p {
    margin: 0 0 9px;
}

.ai-message-content p:last-child {
    margin-bottom: 0;
}

.ai-message-content ul,
.ai-message-content ol {
    padding-left: 20px;
    margin: 8px 0;
}

.ai-message-content strong {
    color: var(--gold-light);
}

.ai-message-time {
    margin-top: 5px;
    color: var(--text-muted);
    font-size: 9px;
}

.ai-typing {
    color: var(--text-muted);
    font-size: 12px;
    padding: 8px 0;
}

/* =========================================
   CHAT INPUT
========================================= */

.ai-input-area {
    padding: 17px 20px;
    border-top: 1px solid var(--border);
}

.ai-input-form {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    padding: 8px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 11px;
    transition: .2s;
}

.ai-input-form:focus-within {
    border-color: var(--gold);
}

.ai-input {
    flex: 1;
    min-width: 0;
    max-height: 110px;
    resize: none;
    padding: 10px;
    background: transparent;
    color: var(--text);
    border: none;
    outline: none;
    font: inherit;
    font-size: 13px;
}

.ai-input::placeholder {
    color: var(--text-muted);
}

.ai-send {
    width: 39px;
    height: 39px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    background: var(--gold);
    color: #17120a;
    border: none;
    border-radius: 8px;
    font-size: 17px;
    cursor: pointer;
    transition: .2s;
}

.ai-send:hover {
    background: var(--gold-light);
}

.ai-send:disabled {
    opacity: .5;
    cursor: not-allowed;
}

.ai-disclaimer {
    margin: 9px 0 0;
    color: var(--text-muted);
    text-align: center;
    font-size: 10px;
}

/* =========================================
   RIGHT INFORMATION PANEL
========================================= */

.ai-console > .ai-side {
    display: flex !important;
    flex-direction: column !important;
    flex-wrap: nowrap !important;
    align-items: stretch;
    gap: 16px;
    width: 100%;
    min-width: 0;
}

.ai-console > .ai-side > .ai-side-card {
    display: block;
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
}

.ai-side-card {
    padding: 21px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.ai-side-card > .ai-eyebrow {
    display: block;
    min-height: 30px;
    margin: 0 0 12px;
    color: var(--gold);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2px;
    line-height: 1.5;
    text-transform: uppercase;
    white-space: normal;
    overflow-wrap: break-word;
}

.ai-side-card h3 {
    margin: 0 0 17px;
    font-family: Georgia, serif;
    font-size: 16px;
    font-weight: 500;
    line-height: 1.5;
}

.ai-stat {
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
}

.ai-stat:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.ai-stat-label {
    color: var(--text-muted);
    font-size: 11px;
}

.ai-stat-value {
    display: block;
    margin-top: 5px;
    color: var(--gold);
    font-family: Georgia, serif;
    font-size: 26px;
}

.ai-insight {
    padding: 14px;
    background: var(--surface-2);
    border-left: 2px solid var(--gold);
    border-radius: 7px;
}

.ai-insight strong {
    color: var(--gold);
    font-size: 11px;
}

.ai-insight p {
    margin: 7px 0 0;
    color: var(--text-muted);
    font-size: 11px;
    line-height: 1.7;
}

.ai-clear {
    width: 100%;
    margin-top: 15px;
}

/* =========================================
   RESPONSIVE LAYOUT
========================================= */

@media (max-width: 1250px) {
    .ai-console {
        grid-template-columns: 200px minmax(350px, 1fr);
    }

    .ai-console > .ai-side {
        grid-column: 1 / -1;
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: stretch;
    }
}

@media (max-width: 850px) {
    .ai-console {
        grid-template-columns: 1fr;
    }

    .ai-history-panel {
        height: auto;
        max-height: 260px;
    }

    .ai-history-list {
        max-height: 150px;
    }

    .ai-chat-panel {
        height: 700px;
    }

    .ai-console > .ai-side {
        grid-column: auto;
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 600px) {
    .ai-chat-panel {
        height: 650px;
    }

    .ai-chat-body {
        padding: 16px;
    }

    .ai-chat-header {
        padding: 15px;
    }

    .ai-suggestions {
        grid-template-columns: 1fr;
    }

    .ai-message {
        max-width: 100%;
    }

    .ai-console > .ai-side {
        grid-template-columns: 1fr;
    }

    .ai-input-area {
        padding: 12px;
    }

    .ai-history-panel {
        max-height: 220px;
    }
}

.ai-usage {
    margin-top: 8px;
    color: var(--text-muted);
    font-size: 11px;
    text-align: right;
}

.ai-usage strong {
    color: var(--gold);
    font-weight: 700;
}

.ai-usage-bar {
    width: 100%;
    height: 4px;
    margin-top: 6px;
    overflow: hidden;
    background: var(--surface-3);
    border-radius: 10px;
}

.ai-usage-progress {
    width: 0%;
    height: 100%;
    background: var(--gold);
    border-radius: 10px;
    transition: width .3s ease;
}

</style>
</head>

<body>

<div class="app-layout">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">

        <div class="page-container">

            <div class="page-header">
                <div class="page-header-top">

                    <button type="button"
                            class="mobile-menu-button"
                            id="mobileMenuButton">
                        ☰
                    </button>

                    <div class="ai-intro">
                        <p class="page-subtitle">PERSONAL INTELLIGENCE</p>
                        <h1>Bloom AI</h1>
                        <p>
                            A smarter way to understand your workload
                            and make your next move.
                        </p>
                    </div>

                </div>
            </div>

            <div class="ai-console">

            <!-- CONVERSATION HISTORY -->

<aside class="ai-history-panel">

    <div class="ai-history-header">

        <p class="ai-eyebrow">YOUR WORKSPACE</p>

        <h3>Conversations</h3>

        <button type="button"
                class="ai-new-chat"
                id="newChatButton">
            + New Conversation
        </button>

    </div>

    <div class="ai-history-label">
        RECENT CHATS
    </div>

    <div class="ai-history-list" id="historyList">

        <div class="ai-history-loading">
            Loading conversations...
        </div>

    </div>

</aside>

                <!-- CHAT PANEL -->

                <section class="ai-chat-panel">

                    <div class="ai-chat-header">

                        <div class="ai-identity">

                            <div class="ai-symbol">
                                ◈
                            </div>

                            <div>
                                <h3>Bloom AI</h3>
                                <p>PERSONAL INTELLIGENCE CONSOLE</p>
                            </div>

                        </div>

                        <div class="ai-status">
                            <span class="ai-status-dot"></span>
                            READY
                        </div>

                    </div>

                    <div class="ai-chat-body" id="chatBody">

                        <div class="ai-welcome" id="welcomeScreen">

                            <div class="ai-welcome-symbol">
                                ◈
                            </div>

                            <p class="ai-eyebrow">
                                YOUR INTELLIGENCE ASSISTANT
                            </p>

                            <h2>Think clearly. Work intelligently.</h2>

                            <p>
                                I'm Bloom AI. I can help you understand
                                your workload, organise your priorities
                                and make more informed productivity decisions.
                            </p>

                            <div class="ai-suggestions">

                                <button type="button"
                                        class="ai-suggestion"
                                        data-prompt="Analyse my current workload and tell me what I should focus on first.">
                                    ◈ Analyse my workload
                                </button>

                                <button type="button"
                                        class="ai-suggestion"
                                        data-prompt="Which of my tasks should I prioritise and why?">
                                    ↗ Identify my priorities
                                </button>

                                <button type="button"
                                        class="ai-suggestion"
                                        data-prompt="Help me create a realistic schedule for my pending tasks.">
                                    ◷ Plan my schedule
                                </button>

                                <button type="button"
                                        class="ai-suggestion"
                                        data-prompt="Give me advice to improve my productivity based on my tasks.">
                                    ⌁ Improve my productivity
                                </button>

                            </div>

                        </div>

                    </div>

                    <div class="ai-input-area">

                        <form class="ai-input-form" id="chatForm">

                            <textarea
                                class="ai-input"
                                id="messageInput"
                                rows="1"
                                maxlength="2000"
                                placeholder="Ask Bloom AI about your productivity..."
                                required
                            ></textarea>

                            <button type="submit"
                                    class="ai-send"
                                    id="sendButton"
                                    aria-label="Send message">
                                ↑
                            </button>

                        </form>

                        <p class="ai-disclaimer">
                            Bloom AI can make mistakes. Review important information.
                        </p>

                    <div class="ai-usage">
                        <span id="usageText">Loading daily usage...</span>
                        <div class="ai-usage-bar">
                            <div class="ai-usage-progress" id="usageProgress"></div>
                        </div>
                    </div>

                    </div>

                </section>

                <!-- INTELLIGENCE PANEL -->

                <aside class="ai-side">

                    <section class="ai-side-card">

                        <p class="ai-eyebrow">WORKLOAD OVERVIEW</p>

                        <h3>Your Task Intelligence</h3>

                        <div class="ai-stat">

                            <span class="ai-stat-label">
                                Pending Tasks
                            </span>

                            <strong class="ai-stat-value">
                                <?= $pending ?>
                            </strong>

                        </div>

                        <div class="ai-stat">

                            <span class="ai-stat-label">
                                High Priority
                            </span>

                            <strong class="ai-stat-value">
                                <?= $highPriority ?>
                            </strong>

                        </div>

                        <div class="ai-stat">

                            <span class="ai-stat-label">
                                Overdue Tasks
                            </span>

                            <strong class="ai-stat-value">
                                <?= $overdue ?>
                            </strong>

                        </div>

                    </section>

                    <section class="ai-side-card">

                        <p class="ai-eyebrow">SYSTEM INSIGHT</p>

                        <h3>Current Overview</h3>

                        <div class="ai-insight">

                            <strong>WORKLOAD STATUS</strong>

                            <p>
                                <?php if ($pending === 0): ?>

                                    You have no pending tasks.
                                    This is a good opportunity to plan ahead.

                                <?php elseif ($overdue > 0): ?>

                                    You have <?= $overdue ?>
                                    overdue task(s). Consider reviewing
                                    these first.

                                <?php elseif ($highPriority > 0): ?>

                                    You have <?= $highPriority ?>
                                    high-priority task(s) that may need
                                    your attention.

                                <?php else: ?>

                                    Your tasks are ready for prioritisation.
                                    Ask Bloom AI to help plan your next steps.

                                <?php endif; ?>
                            </p>

                        </div>

                        <button type="button"
                                class="btn btn-secondary btn-sm ai-clear"
                                id="clearChatButton">
                            Clear Conversation
                        </button>

                    </section>

                    <section class="ai-side-card">

                        <p class="ai-eyebrow">AI CAPABILITIES</p>

                        <h3>What I Can Help With</h3>

                        <div class="ai-stat">
                            <span class="ai-stat-label">
                                Task prioritisation
                            </span>
                        </div>

                        <div class="ai-stat">
                            <span class="ai-stat-label">
                                Deadline planning
                            </span>
                        </div>

                        <div class="ai-stat">
                            <span class="ai-stat-label">
                                Productivity guidance
                            </span>
                        </div>

                    </section>

                </aside>

            </div>

        </div>

    </main>

</div>



<script>

// =============================================
// BLOOM AI - DAILY USAGE DISPLAY
// =============================================

async function updateAIUsage() {

    const usageText = document.getElementById("usageText");
    const usageProgress = document.getElementById("usageProgress");

    try {

        const response = await fetch("bloom-ai-usage.php", {
            credentials: "same-origin"
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error("Unable to load usage.");
        }

        const used = result.data.used;
        const limit = result.data.limit;
        const remaining = result.data.remaining;

        const percentage = limit > 0
            ? (used / limit) * 100
            : 0;

        usageText.innerHTML =
            `<strong>${remaining}</strong> of ${limit} messages remaining today`;

        usageProgress.style.width =
            `${Math.min(percentage, 100)}%`;

    } catch (error) {

        usageText.textContent = "Usage information unavailable.";

    }
}

(function () {

    const chatBody = document.getElementById("chatBody");
    const chatForm = document.getElementById("chatForm");
    const messageInput = document.getElementById("messageInput");
    const sendButton = document.getElementById("sendButton");
    const clearButton = document.getElementById("clearChatButton");
    const newChatButton = document.getElementById("newChatButton");
    const historyList = document.getElementById("historyList");

    let conversationId = null;
    let isSending = false;
    let csrfToken = "";

    function scrollToBottom() {
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    async function loadCsrfToken() {

    const response = await fetch(
        "bloom-ai-history.php?action=token",
        { credentials: "same-origin" }
    );

    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error("Unable to verify security token.");
    }

    csrfToken = result.data.csrf_token;
}

    function formatMessage(text) {

        const escaped = text.replace(/[&<>"']/g, function (character) {
            return {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;"
            }[character];
        });

        return escaped
            .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
            .replace(/\*(.*?)\*/g, "<em>$1</em>")
            .replace(/^### (.*)$/gm, "<strong>$1</strong>")
            .replace(/^## (.*)$/gm, "<strong>$1</strong>")
            .replace(/^- (.*)$/gm, "• $1")
            .replace(/\n/g, "<br>");
    }

    function addMessage(text, sender) {

        const welcome = document.getElementById("welcomeScreen");

        if (welcome) welcome.remove();

        const message = document.createElement("div");
        message.className = "ai-message " + sender;

        const symbol = document.createElement("div");
        symbol.className = "ai-message-icon";
        symbol.textContent = sender === "user" ? "U" : "◈";

        const wrapper = document.createElement("div");

        const content = document.createElement("div");
        content.className = "ai-message-content";

        if (sender === "assistant") {
            content.innerHTML = formatMessage(text);
        } else {
            content.textContent = text;
        }

        const time = document.createElement("div");
        time.className = "ai-message-time";
        time.textContent = new Date().toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit"
        });

        wrapper.appendChild(content);
        wrapper.appendChild(time);

        message.appendChild(symbol);
        message.appendChild(wrapper);

        chatBody.appendChild(message);

        scrollToBottom();
    }

    function showWelcome() {

        chatBody.innerHTML = `
            <div class="ai-welcome" id="welcomeScreen">

                <div class="ai-welcome-symbol">◈</div>

                <p class="ai-eyebrow">
                    YOUR INTELLIGENCE ASSISTANT
                </p>

                <h2>Think clearly. Work intelligently.</h2>

                <p>
                    I'm Bloom AI. I can help you understand
                    your workload, organise your priorities
                    and make more informed productivity decisions.
                </p>

                <div class="ai-suggestions">

                    <button type="button"
                            class="ai-suggestion"
                            data-prompt="Analyse my current workload and tell me what I should focus on first.">
                        ◈ Analyse my workload
                    </button>

                    <button type="button"
                            class="ai-suggestion"
                            data-prompt="Which of my tasks should I prioritise and why?">
                        ↗ Identify my priorities
                    </button>

                    <button type="button"
                            class="ai-suggestion"
                            data-prompt="Help me create a realistic schedule for my pending tasks.">
                        ◷ Plan my schedule
                    </button>

                    <button type="button"
                            class="ai-suggestion"
                            data-prompt="Give me advice to improve my productivity based on my tasks.">
                        ⌁ Improve my productivity
                    </button>

                </div>

            </div>
        `;
    }

    // =========================================
    // LOAD CONVERSATION LIST
    // =========================================

    
async function loadConversations() {

    historyList.innerHTML = `
        <div class="ai-history-loading">
            Loading conversations...
        </div>
    `;

    try {

        const response = await fetch(
            "bloom-ai-history.php?action=list",
            { credentials: "same-origin" }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error("Unable to load conversations.");
        }

        const conversations = result.data.conversations;

        historyList.innerHTML = "";

        if (conversations.length === 0) {

            historyList.innerHTML = `
                <div class="ai-history-empty">
                    No conversations yet.
                </div>
            `;

            return;
        }

        conversations.forEach(function (conversation) {

            const row = document.createElement("div");
            row.className = "ai-history-row";

            const button = document.createElement("button");

            button.type = "button";
            button.className = "ai-history-item";
            button.dataset.id = conversation.id;
            button.textContent = conversation.title;

            if (Number(conversation.id) === Number(conversationId)) {
                button.classList.add("active");
            }

            const deleteButton = document.createElement("button");

            deleteButton.type = "button";
            deleteButton.className = "ai-delete-chat";
            deleteButton.dataset.deleteId = conversation.id;
            deleteButton.textContent = "×";
            deleteButton.title = "Delete conversation";
            deleteButton.setAttribute(
                "aria-label",
                "Delete conversation"
            );

            row.appendChild(button);
            row.appendChild(deleteButton);

            historyList.appendChild(row);

        });

    } catch (error) {

        historyList.innerHTML = `
            <div class="ai-history-empty">
                Unable to load conversation history.
            </div>
        `;

    }
}


async function deleteConversation(id) {

    if (isSending) return;

    const confirmed = confirm(
        "Are you sure you want to delete this conversation? This action cannot be undone."
    );

    if (!confirmed) return;

    try {

        if (!csrfToken) {
            await loadCsrfToken();
        }

        const response = await fetch("bloom-ai-history.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "delete",
                conversation_id: Number(id),
                csrf_token: csrfToken
            })
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.data || "Unable to delete conversation."
            );
        }

        if (Number(conversationId) === Number(id)) {
            conversationId = null;
            showWelcome();
        }

        await loadConversations();

        updateAIUsage();

    } catch (error) {

        alert(error.message);

    }
}

    // =========================================
    // LOAD A SAVED CONVERSATION
    // =========================================

    async function loadConversation(id) {

        if (isSending) return;

        try {

            const response = await fetch(
                "bloom-ai-history.php?action=load&conversation_id=" +
                encodeURIComponent(id),
                { credentials: "same-origin" }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.data || "Unable to open this conversation."
                );
            }

            conversationId = Number(id);

            chatBody.innerHTML = "";

            result.data.messages.forEach(function (message) {

                const sender = message.role === "user"
                    ? "user"
                    : "assistant";

                addMessage(message.message, sender);

            });

            document.querySelectorAll(".ai-history-item").forEach(function (item) {
                item.classList.toggle(
                    "active",
                    Number(item.dataset.id) === conversationId
                );
            });

        } catch (error) {

            alert(error.message);

        }
    }

    // Click on saved conversations.
    historyList.addEventListener("click", function (event) {

    const deleteButton = event.target.closest(".ai-delete-chat");

    if (deleteButton) {
        deleteConversation(deleteButton.dataset.deleteId);
        return;
    }

    const button = event.target.closest(".ai-history-item");

    if (button) {
        loadConversation(button.dataset.id);
    }

});

    // =========================================
    // SEND MESSAGE
    // =========================================

    async function sendMessage(text) {

        if (isSending || !text.trim()) return;

        isSending = true;
        sendButton.disabled = true;

        addMessage(text, "user");

        const typing = document.createElement("div");
        typing.className = "ai-typing";
        typing.textContent = "Bloom AI is analysing your request...";

        chatBody.appendChild(typing);
        scrollToBottom();

        try {

            const response = await fetch("bloom-ai-api.php", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    message: text,
                    conversation_id: conversationId
                })
            });

            const result = await response.json();

            typing.remove();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.data || "Unable to get a response from Bloom AI."
                );
            }

        conversationId = Number(result.data.conversation_id);

            addMessage(result.data.reply, "assistant");

            await loadConversations();

            updateAIUsage();

        } catch (error) {

            typing.remove();

            addMessage(
                error.message || "Something went wrong. Please try again.",
                "assistant"
            );

        } finally {

            isSending = false;
            sendButton.disabled = false;
            messageInput.focus();

        }
    }

    // =========================================
    // CHAT FORM
    // =========================================

    chatForm.addEventListener("submit", function (event) {

        event.preventDefault();

        const text = messageInput.value.trim();

        if (!text || isSending) return;

        messageInput.value = "";
        messageInput.style.height = "auto";

        sendMessage(text);

    });

    messageInput.addEventListener("input", function () {

        this.style.height = "auto";
        this.style.height = Math.min(this.scrollHeight, 110) + "px";

    });

    messageInput.addEventListener("keydown", function (event) {

        if (event.key === "Enter" && !event.shiftKey) {
            event.preventDefault();
            chatForm.requestSubmit();
        }

    });

    // =========================================
    // SUGGESTED PROMPTS
    // =========================================

    document.addEventListener("click", function (event) {

        const button = event.target.closest("[data-prompt]");

        if (!button || isSending) return;

        sendMessage(button.dataset.prompt);

    });

    // =========================================
    // NEW CONVERSATION
    // =========================================

    function startNewConversation() {

        if (isSending) return;

        conversationId = null;

        showWelcome();

        document.querySelectorAll(".ai-history-item").forEach(function (item) {
            item.classList.remove("active");
        });

        messageInput.focus();
    }

    newChatButton.addEventListener("click", startNewConversation);

    clearButton.addEventListener("click", startNewConversation);

// =========================================
// INITIALISE
// =========================================

updateAIUsage();

showWelcome();

loadCsrfToken()
    .then(loadConversations)
    .catch(function () {
        historyList.textContent = "Unable to initialise conversation history.";
    });

})();
</script>

<script src="../assets/js/script.js"></script>

</body>
</html>
