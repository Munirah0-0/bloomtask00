
<?php
/* =========================================
   BLOOMTASK - SIDEBAR
========================================= */

$currentPage = basename($_SERVER["PHP_SELF"]);

function navActive($page, $currentPage) {
    return $page === $currentPage ? "active" : "";
}
?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">

    <!-- BRAND AND TOP ACTIONS -->
    <div class="brand">

        <div class="brand-logo">
    <img
        src="../assets/images/bloomtask-logo.png"
        alt="BloomTask Logo"
        class="brand-icon"
    >

    <div class="brand-text">
        <div class="brand-name">BloomTask</div>
        <div class="brand-subtitle">
            PERSONAL WORKSPACE
        </div>
    </div>
</div>

        <div class="brand-actions">

            <!-- LIGHT / DARK MODE -->
            <button
                type="button"
                class="brand-action-btn"
                data-theme-toggle
                title="Toggle Light/Dark Mode"
                aria-label="Toggle Light/Dark Mode"
            >
                <span data-theme-icon>☀</span>
                <span data-theme-label class="visually-hidden">
                    Light Mode
                </span>
            </button>

            <!-- SIGN OUT -->
            <a
                href="../logout.php"
                class="brand-action-btn"
                title="Sign Out"
                aria-label="Sign Out"
            >
                ↪
            </a>

        </div>
    </div>

    <!-- WORKSPACE -->
    <div class="sidebar-label">Workspace</div>

    <nav class="sidebar-nav">

        <a href="../pages/dashboard.php"
           class="nav-link <?= navActive('dashboard.php', $currentPage) ?>">
            <span class="nav-icon">⌂</span>
            Dashboard
        </a>

        <a href="../pages/tasks.php"
           class="nav-link <?= navActive('tasks.php', $currentPage) ?>">
            <span class="nav-icon">▤</span>
            My Tasks
        </a>

        <a href="../pages/focus.php"
            class="nav-link <?= navActive('focus.php', $currentPage) ?>">
            <span class="nav-icon">◷</span>
            Focus Mode
        </a>

        <a href="../pages/smart-planner.php"
            class="nav-link <?= navActive('smart-planner.php', $currentPage) ?>">
            <span class="nav-icon">✦</span>
            Smart Planner
        </a>

        <a href="../pages/bloom-ai.php"
            class="nav-link <?= navActive('bloom-ai.php', $currentPage) ?>">
            <span class="nav-icon">⌘</span>
            Bloom AI
        </a>

        <a href="../pages/categories.php"
           class="nav-link <?= navActive('categories.php', $currentPage) ?>">
            <span class="nav-icon">◈</span>
            Categories
        </a>

        <a href="../pages/calendar.php"
           class="nav-link <?= navActive('calendar.php', $currentPage) ?>">
            <span class="nav-icon">▦</span>
            Calendar
        </a>

        <a href="../pages/analytics.php"
           class="nav-link <?= navActive('analytics.php', $currentPage) ?>">
            <span class="nav-icon">▥</span>
            Analytics
        </a>

        <a href="../pages/achievements.php"
            class="nav-link <?= navActive('achievements.php', $currentPage) ?>">
            <span class="nav-icon">🏆</span>
            Achievements
        </a>

    </nav>

    <!-- ACCOUNT -->
    <div class="sidebar-label">Account</div>

    <nav class="sidebar-nav">

        <a href="../pages/profile.php"
           class="nav-link <?= navActive('profile.php', $currentPage) ?>">
            <span class="nav-icon">♙</span>
            My Profile
        </a>

        <a href="../pages/settings.php"
           class="nav-link <?= navActive('settings.php', $currentPage) ?>">
            <span class="nav-icon">⚙</span>
            Settings
        </a>

    </nav>

</aside>
