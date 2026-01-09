<header style="display: flex; align-items: center; justify-content: space-between;">
    <div style="display: flex; align-items: center;">
        <a href="index.php"
            style="border-bottom: none; display: flex; align-items: center; gap: 15px; text-decoration: none;">
            <img src="logo.svg" alt="Pandore Logo" style="height: 40px; display: block;">
            <h1>Pandora's Box</h1>
        </a>
    </div>
    <nav class="nav" style="display: flex; align-items: center;">
        <a href="index.php">📊 Dashboard</a>
        <a href="computers.php">💻 Inventory</a>
        <a href="borrowers.php">👥 Borrowers</a>
        <a href="loans.php">🗓️ Loans</a>
        <button id="theme-toggle" class="theme-toggle" title="Basculer le thème">🌙</button>
    </nav>
</header>

<script>
(function() {
    const toggle = document.getElementById('theme-toggle');
    const html = document.documentElement;
    
    // Check for saved theme preference or system preference
    const savedTheme = localStorage.getItem('theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
        html.setAttribute('data-theme', 'dark');
        toggle.textContent = '☀️';
    } else {
        html.setAttribute('data-theme', 'light');
        toggle.textContent = '🌙';
    }
    
    // Toggle handler
    toggle.addEventListener('click', function() {
        const currentTheme = html.getAttribute('data-theme');
        if (currentTheme === 'dark') {
            html.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
            toggle.textContent = '🌙';
        } else {
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            toggle.textContent = '☀️';
        }
    });
})();
</script>