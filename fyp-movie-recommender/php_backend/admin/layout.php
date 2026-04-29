<?php
// admin/layout.php - Main Wrapper for Admin Panel
function render_admin_layout($content, $title = "Dashboard", $active_page = "dashboard") {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> | MoodAI Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">

    <?php
    // Inject Dynamic Theme Color
    global $pdo;
    $theme_color = $pdo->query("SELECT setting_value FROM admin_settings WHERE setting_key = 'theme_color'")->fetchColumn() ?: '#950101';
    ?>
    <style>
        :root { --admin-primary: <?php echo $theme_color; ?>; }
        @media (max-width: 991.98px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.show { transform: translateX(0); }
            .admin-main { margin-left: 0; }
            .admin-navbar { padding: 0 1rem; }
        }
    </style>

    <!-- Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-logo">Mood<span>AI</span>.</a>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php" class="nav-link-admin <?php echo $active_page == 'dashboard' ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            <div class="text-muted small fw-bold mt-4 mb-2 px-3">CONTENT</div>
            <a href="movies.php" class="nav-link-admin <?php echo $active_page == 'movies' ? 'active' : ''; ?>">
                <i class="bi bi-film"></i> Movies
            </a>
            <a href="moods.php" class="nav-link-admin <?php echo $active_page == 'moods' ? 'active' : ''; ?>">
                <i class="bi bi-emoji-smile"></i> Moods
            </a>
            <a href="mapping.php" class="nav-link-admin <?php echo $active_page == 'mapping' ? 'active' : ''; ?>">
                <i class="bi bi-diagram-3"></i> Mapping
            </a>

            <div class="text-muted small fw-bold mt-4 mb-2 px-3">MANAGEMENT</div>
            <a href="users.php" class="nav-link-admin <?php echo $active_page == 'users' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i> Users
            </a>
            <a href="feedback.php" class="nav-link-admin <?php echo $active_page == 'feedback' ? 'active' : ''; ?>">
                <i class="bi bi-chat-heart"></i> Feedback
            </a>
            <a href="settings.php" class="nav-link-admin <?php echo $active_page == 'settings' ? 'active' : ''; ?>">
                <i class="bi bi-sliders"></i> Settings
            </a>

            <div class="mt-5 px-3">
                <a href="logout.php" class="btn btn-outline-danger btn-sm w-100">
                    <i class="bi bi-box-arrow-left me-2"></i> Logout
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="admin-main">
        <header class="admin-navbar">
            <div class="admin-nav-left d-flex align-items-center">
                <button class="btn btn-link text-white d-lg-none me-2 p-0" onclick="document.querySelector('.admin-sidebar').classList.toggle('show')">
                    <i class="bi bi-list fs-3"></i>
                </button>
                <h5 class="mb-0 fw-bold"><?php echo $title; ?></h5>
            </div>
            <div class="admin-nav-right d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-dark dropdown-toggle border-0" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-2"></i> <?php echo $_SESSION['admin_name']; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                        <li><a class="dropdown-item" href="settings.php">Profile Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <section class="admin-content animate-fade-in">
            <?php echo $content; ?>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            easing: 'ease-out-cubic'
        });
    </script>
</body>
</html>
<?php
}
?>
