
/* =========================================
   BLOOMTASK - JAVASCRIPT
========================================= */

function initBloomTask() {

    /* =========================================
       THEME SYSTEM
    ========================================= */

    const root = document.documentElement;

    function applyTheme(theme) {

        if (theme !== "light" && theme !== "dark") {
            theme = "dark";
        }

        // Apply theme
        root.setAttribute("data-theme", theme);

        // Save theme preference
        localStorage.setItem("bloomtask-theme", theme);

        // Update Settings buttons
        document.querySelectorAll("[data-theme-choice]").forEach(function (button) {
            button.classList.toggle(
                "active",
                button.dataset.themeChoice === theme
            );
        });

        // Update current theme label
        const themeLabel = document.getElementById("current-theme-label");

        if (themeLabel) {
            themeLabel.textContent =
                theme === "dark" ? "Dark Mode" : "Light Mode";
        }

        // Update sidebar and other theme icons
        document.querySelectorAll("[data-theme-toggle], #themeToggle").forEach(function (button) {

            const icon =
                button.querySelector("[data-theme-icon]") ||
                button.querySelector("#themeIcon");

            const label =
                button.querySelector("[data-theme-label]") ||
                button.querySelector("#themeText");

            if (icon) {
                icon.textContent = theme === "dark" ? "☀" : "☾";
            }

            if (label) {
                label.textContent =
                    theme === "dark" ? "Light Mode" : "Dark Mode";
            }

        });

    }

    // Load saved theme
    const savedTheme = localStorage.getItem("bloomtask-theme") || "dark";
    applyTheme(savedTheme);


    /* =========================================
       THEME BUTTON EVENTS
    ========================================= */

    document.addEventListener("click", function (event) {

        // Sidebar theme toggle
        const toggleButton = event.target.closest(
            "[data-theme-toggle], #themeToggle"
        );

        if (toggleButton) {

            event.preventDefault();

            const currentTheme =
                root.getAttribute("data-theme") || "dark";

            const newTheme =
                currentTheme === "dark" ? "light" : "dark";

            applyTheme(newTheme);
            return;
        }

        // Settings theme selection
        const choiceButton = event.target.closest("[data-theme-choice]");

        if (choiceButton) {
            applyTheme(choiceButton.dataset.themeChoice);
            return;
        }

        // Reset theme
        const resetButton = event.target.closest("#reset-theme");

        if (resetButton) {
            applyTheme("dark");
        }

    });

/* PASSWORD VISIBILITY */

const togglePassword = document.getElementById("togglePassword");

if (togglePassword) {
    togglePassword.addEventListener("click", function () {

        const password = document.getElementById("password");
        const eye = document.getElementById("passwordEye");

        const visible = password.type === "password";

        password.type = visible ? "text" : "password";

        eye.innerHTML = visible
            ? `<path d="M3 3l18 18"/>
               <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
               <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a15.8 15.8 0 0 1-3.1 3.8"/>
               <path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.2 0 2.3-.2 3.3-.6"/>`
            : `<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
               <circle cx="12" cy="12" r="2.5"/>`;

        togglePassword.setAttribute(
            "aria-label",
            visible ? "Hide password" : "Show password"
        );
    });
}

    /* =========================================
       MOBILE SIDEBAR
    ========================================= */

    const sidebar = document.getElementById("sidebar");
    const menuButton = document.getElementById("mobileMenuButton");
    const overlay = document.getElementById("sidebarOverlay");

    function closeSidebar() {

        if (sidebar) {
            sidebar.classList.remove("open");
        }

        if (overlay) {
            overlay.classList.remove("active");
        }

    }

    if (menuButton && sidebar && overlay) {

        menuButton.addEventListener("click", function () {
            sidebar.classList.toggle("open");
            overlay.classList.toggle("active");
        });

        overlay.addEventListener("click", closeSidebar);

        document.querySelectorAll(".sidebar .nav-link").forEach(function (link) {
            link.addEventListener("click", closeSidebar);
        });

    }


    /* =========================================
       WELCOME BUTTON
    ========================================= */

    const welcomeButton = document.getElementById("welcomeBtn");

    if (welcomeButton) {

        welcomeButton.addEventListener("click", function () {

            window.showToast(
                "Welcome to BloomTask. Make today productive.",
                "success"
            );

        });

    }


    /* =========================================
       TOAST NOTIFICATIONS
    ========================================= */

    window.showToast = function (message, type = "success") {

        let container = document.getElementById("toastContainer");

        if (!container) {

            container = document.createElement("div");
            container.id = "toastContainer";
            container.className = "toast-container";

            document.body.appendChild(container);

        }

        const toast = document.createElement("div");

        toast.className = "toast " + type;
        toast.textContent = message;

        container.appendChild(toast);

        setTimeout(function () {

            toast.style.opacity = "0";
            toast.style.transform = "translateY(10px)";

            setTimeout(function () {
                toast.remove();
            }, 300);

        }, 3500);

    };


    /* =========================================
       DELETE CONFIRMATION
    ========================================= */

    document.querySelectorAll("[data-confirm]").forEach(function (element) {

        element.addEventListener("click", function (event) {

            const message =
                element.getAttribute("data-confirm") ||
                "Are you sure you want to continue?";

            if (!confirm(message)) {
                event.preventDefault();
            }

        });

    });


    /* =========================================
       AUTO DISMISS ALERTS
    ========================================= */

    document.querySelectorAll(".alert[data-auto-dismiss]").forEach(function (alert) {

        setTimeout(function () {

            alert.style.transition = "opacity 0.4s";
            alert.style.opacity = "0";

            setTimeout(function () {
                alert.remove();
            }, 400);

        }, 4000);

    });

}


// Initialise JavaScript
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initBloomTask);
} else {
    initBloomTask();
}
