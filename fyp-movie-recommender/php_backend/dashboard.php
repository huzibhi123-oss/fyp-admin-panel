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

} catch (Exception $e) {
    $total_detections = 0;
    $total_favorites = 0;
    $recent_activity = [];
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
    
    <!-- Hero -->
    <section class="dashboard-hero" data-aos="fade-down">
        <span class="section-tag">System Ready</span>
        <h1 class="hero-title">Welcome back,<br><?php echo htmlspecialchars($user_name); ?>.</h1>
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