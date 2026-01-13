<div class="topbar">
    <!-- Search Bar -->
    <div class="search-container">
        <form action="index.php" method="GET" style="margin:0;">
            <input type="hidden" name="view" value="search_results">
            <span class="search-icon">🔍</span>
            <input type="text" name="q" class="search-input" placeholder="Buscar negocios o usuarios...">
            <button type="submit" class="search-btn">➡️</button>
        </form>
    </div>

    <!-- Right Side: Dark Mode & User Info -->
    <div class="topbar-right">
        <button id="dark-mode-toggle" class="theme-toggle" title="Cambiar Tema">🌙</button>
        
        <div class="user-profile">
            <div class="user-info">
                <div class="user-name">Super Admin</div>
                <div class="user-role"><?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?></div>
            </div>
            <div class="user-avatar">
                SA
            </div>
        </div>
    </div>
</div>

<script>
    // Dark Mode Logic
    (function() {
        const toggleBtn = document.getElementById('dark-mode-toggle');
        const currentTheme = localStorage.getItem("theme");
        
        if (currentTheme === "dark") {
            document.documentElement.setAttribute("data-theme", "dark");
            toggleBtn.textContent = "☀️";
        } else {
            toggleBtn.textContent = "🌙";
        }

        toggleBtn.addEventListener("click", function() {
            let theme = "light";
            if (!document.documentElement.getAttribute("data-theme") || document.documentElement.getAttribute("data-theme") === "light") {
                theme = "dark";
                document.documentElement.setAttribute("data-theme", "dark");
                toggleBtn.textContent = "☀️";
            } else {
                document.documentElement.setAttribute("data-theme", "light");
                toggleBtn.textContent = "🌙";
            }
            localStorage.setItem("theme", theme);
        });
    })();
</script>
