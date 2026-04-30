<?php
// 1. START SESSION
session_start();

// --- Session Check & User Data Retrieval ---
$user_name = $_SESSION['username'] ?? 'Operative';
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: login.php");
    exit();
}

require_once 'includes/config.php';
require_once 'database/connection.php';

// Fetch stats for the Overview bar
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_mood_history WHERE user_id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $total_detections = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_favorites WHERE user_id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $total_favorites = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT mood as detected_mood, input_type as type, detected_at as created_at FROM user_mood_history WHERE user_id = :uid ORDER BY detected_at DESC LIMIT 3");
    $stmt->execute(['uid' => $user_id]);
    $recent_activity = $stmt->fetchAll();

    // Fetch a random spotlight movie
    $stmt = $pdo->prepare("SELECT title, overview, poster_path FROM catched_movies ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    $spotlight_movie = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $total_detections = 0;
    $total_favorites = 0;
    $recent_activity = [];
    $spotlight_movie = null;
}

// Fallback for spotlight movie
if (!$spotlight_movie) {
    $spotlight_movie = [
        'title' => 'Top Gun: Maverick',
        'overview' => 'After more than thirty years of service as one of the Navy\'s top aviators, Pete Mitchell is where he belongs, pushing the envelope as a courageous test pilot.',
        'poster_path' => '/62HCnUTziyWcpDaBO2i1DX17ljH.jpg'
    ];
}

require_once 'includes/header.php';
set_page_title("MoodAI | Your Dashboard");
?>
<link rel="stylesheet" href="assets/css/dashboard.css">

<!-- Stats Bar -->
<div class="stats-bar" data-aos="fade-down">
    <div class="container d-flex align-items-center overflow-auto">
        <div class="stat-item">
            <div class="stat-label">System Status</div>
            <div class="stat-value"><span>ACTIVE</span></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Mood Scans</div>
            <div class="stat-value"><?php echo number_format($total_detections); ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Favorites</div>
            <div class="stat-value"><?php echo number_format($total_favorites); ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Access</div>
            <div class="stat-value">AUTHORIZED</div>
        </div>
    </div>
</div>

<main class="container pb-5 mb-5 fade-in-section">

    <!-- Hero Section -->
    <section class="hero-section dashboard-hero-section mb-5">
        <div class="container px-0">
            <div class="hero-card" data-aos="zoom-in">
                <div class="row align-items-center p-5">
                    <div class="col-lg-5 text-center position-relative mb-5 mb-lg-0" data-aos="fade-right">
                        <!-- Decorative small icons -->
                        <i class="bi bi-star-fill decorative-icon icon-1"></i>
                        <i class="bi bi-film decorative-icon icon-2"></i>
                        <i class="bi bi-cpu-fill decorative-icon icon-3"></i>
                        <i class="bi bi-play-circle-fill decorative-icon icon-4"></i>

                        <!-- Large Main Icon / Movie Poster -->
                        <div class="large-hero-icon">
                            <?php
                            $poster_path = $spotlight_movie['poster_path'] ?? '';
                            if (!empty($poster_path)):
                                if (strpos($poster_path, 'http') === false) {
                                    if ($poster_path[0] !== '/') $poster_path = '/' . $poster_path;
                                    $poster_url = "https://image.tmdb.org/t/p/w500" . $poster_path;
                                } else {
                                    $poster_url = $poster_path;
                                }
                            ?>
                                <img src="<?php echo htmlspecialchars($poster_url); ?>"
                                     alt="Spotlight"
                                     style="width: 250px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.5); border: 2px solid rgba(255,255,255,0.1);">
                            <?php else: ?>
                                <i class="bi bi-camera-reels"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-7 hero-content ps-lg-5" data-aos="fade-left">
                        <span class="section-tag hero-tag">Movie Spotlight</span>
                        <h1 class="hero-title-text" style="font-size: 3.5rem;"><?php echo htmlspecialchars($spotlight_movie['title']); ?></h1>
                        <p class="lead hero-lead mb-5" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                            <?php echo htmlspecialchars($spotlight_movie['overview']); ?>
                        </p>
                        <div class="d-flex align-items-center">
                            <a href="recommendation.php" class="btn btn-hero-primary btn-lg me-3">Discover More</a>
                            <span class="text-white opacity-75 small">Welcome back, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-5 align-items-stretch">
        <!-- Detection Methods -->
        <div class="col-lg-8 d-flex flex-column">
            <h5 class="text-white mb-4 fw-bold">Choose a method to find your mood</h5>
            <div class="row g-4">

                <div class="col-md-6" data-aos="zoom-in" data-aos-delay="100">
                    <div class="premium-card">
                        <i class="bi bi-webcam premium-icon"></i>
                        <h4 class="card-title-premium">Mood by Camera</h4>
                        <p class="text-muted small animate-reveal">Our AI will find your mood by looking at your face through the camera.</p>
                        <a href="mood_face.php" class="btn btn-outline-premium mt-3">Try Now</a>
                    </div>
                </div>

                <div class="col-md-6" data-aos="zoom-in" data-aos-delay="200">
                    <div class="premium-card">
                        <i class="bi bi-pencil-square premium-icon"></i>
                        <h4 class="card-title-premium">Mood by Text</h4>
                        <p class="text-muted small animate-reveal">Type how you are feeling, and our AI will analyze your words to find your mood.</p>
                        <a href="mood_text.php" class="btn btn-outline-premium mt-3">Try Now</a>
                    </div>
                </div>

                <div class="col-md-12" data-aos="zoom-in" data-aos-delay="300">
                    <div class="premium-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <i class="bi bi-mic premium-icon"></i>
                                <h4 class="card-title-premium">Mood by Voice</h4>
                                <p class="text-muted small animate-reveal">Speak into your microphone, and our AI will find your mood from your voice.</p>
                                <a href="mood_voice.php" class="btn btn-outline-premium mt-3">Try Now</a>
                            </div>
                            <div class="col-md-4 d-none d-md-block text-end">
                                <i class="bi bi-soundwave text-bright-red" style="font-size: 5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-4 d-flex flex-column">
            <h5 class="text-white mb-4 fw-bold">Recent Activity</h5>

            <div class="premium-card recent-card" data-aos="fade-left">
                <div class="recent-logs-container">

                <?php if (empty($recent_activity)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-hdd-network mb-3" style="font-size: 2rem; color: var(--accent-red);"></i>
                        <p class="text-muted small animate-reveal" style="color: var(--text-white) !important;">No recent activity found.</p>
                    </div>
                <?php else: ?>

                    <?php foreach ($recent_activity as $log): ?>
                        <div class="recent-item">
                            <div class="recent-info">
                                <h6><?php echo htmlspecialchars(strtoupper($log['detected_mood'])); ?></h6>
                                <p>Method: <?php echo htmlspecialchars(strtoupper($log['type'])); ?> | Time: <?php echo date('H:i', strtotime($log['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="text-center mt-4">
                        <a href="history.php" class="text-muted small text-decoration-none">View Full History <i class="bi bi-arrow-right"></i></a>
                    </div>

                <?php endif; ?>

                </div>
            </div>

        </div>
    </div>

</main>

<?php
require_once 'includes/footer.php';
?>