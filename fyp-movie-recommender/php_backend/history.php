<?php
// history.php - Premium Neural Archives
session_start();

require_once 'includes/config.php';
require_once 'database/connection.php';
require_once 'includes/header.php';
set_page_title("MoodAI | Neural Archives");

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit();
}

// Fetch history
$mood_history = [];
try {
    $stmt = $pdo->prepare("SELECT id, mood, input_type as type, detected_at as timestamp FROM user_mood_history WHERE user_id = ? ORDER BY detected_at DESC");
    $stmt->execute([$user_id]);
    $mood_history = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database Error in history.php: " . $e->getMessage());
}

// Tech UI Helpers
function get_mood_icon_tech($mood) {
    $mood = strtolower($mood);
    $icons = [
        'happy' => 'bi-emoji-smile',
        'sad' => 'bi-emoji-frown',
        'angry' => 'bi-emoji-angry',
        'surprise' => 'bi-emoji-surprise',
        'neutral' => 'bi-emoji-neutral',
    ];
    $icon = $icons[$mood] ?? 'bi-emoji-expressionless';
    return '<i class="bi ' . $icon . ' mood-icon-premium"></i>';
}

function get_type_badge_tech($type) {
    return '<span class="badge-tech">' . htmlspecialchars(strtoupper($type)) . '</span>';
}
?>
<link rel="stylesheet" href="assets/css/history.css">

<main class="container pb-5 fade-in-section">

    <!-- Interface Header -->
    <header class="hero-section-premium mb-5 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
        <div>
            <span class="archive-tag animate-reveal d-block">Loading your history...</span>
            <h1 class="display-4 fw-800 animate-reveal d-block">Your Mood History</h1>
            <p class="text-muted small animate-reveal d-block mb-0">A record of your detected moods over time.</p>
        </div>
        
        <div class="dropdown animate-reveal">
            <button class="btn btn-filter dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-funnel me-1 text-bright-red"></i> Filter by Method
            </button>
            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg border-danger">
                <li><a class="dropdown-item dropdown-item-tech active" href="#" data-filter="all">All Methods</a></li>
                <li><a class="dropdown-item dropdown-item-tech" href="#" data-filter="face">Camera</a></li>
                <li><a class="dropdown-item dropdown-item-tech" href="#" data-filter="text">Text</a></li>
                <li><a class="dropdown-item dropdown-item-tech" href="#" data-filter="voice">Voice</a></li>
            </ul>
        </div>
    </header>

    <div class="row justify-content-center">
        <div class="col-lg-11">

            <div id="historyContainer">
                <?php if (empty($mood_history)): ?>
                    <div class="empty-archive animate-reveal">
                        <i class="bi bi-hdd-network text-bright-red mb-3 d-block" style="font-size: 3rem;"></i>
                        <h4 class="fw-bold">No Records Found</h4>
                        <p class="text-muted small">Use our detection tools to see your history here.</p>
                        <a href="dashboard.php" class="btn btn-filter mt-3">Try it now</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($mood_history as $record): ?>
                        <div class="history-item animate-reveal" data-type="<?php echo strtolower($record['type']); ?>">
                            <div class="history-card">
                                <div class="card-body d-flex align-items-center flex-wrap flex-md-nowrap gap-3">
                                    
                                    <div class="d-flex align-items-center flex-grow-1">
                                        <?php echo get_mood_icon_tech($record['mood']); ?>
                                        <div>
                                            <span class="archive-id">Log ID: #<?php echo str_pad($record['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                            <h4 class="fw-bold text-white mb-0"><?php echo strtoupper($record['mood']); ?></h4>
                                            <p class="small text-muted mb-0">Emotional state detected and verified.</p>
                                        </div>
                                    </div>

                                    <div class="text-start text-md-center px-md-4 border-start border-dark border-end d-none d-md-block">
                                        <?php echo get_type_badge_tech($record['type']); ?>
                                        <small class="d-block text-muted mt-2" style="font-size: 0.6rem;">Method</small>
                                    </div>

                                    <div class="text-md-end flex-shrink-0 ms-md-4">
                                        <div class="fw-bold text-white" style="font-size: 0.9rem; letter-spacing: 1px;">
                                            <?php echo date('d_M_Y', strtotime($record['timestamp'])); ?>
                                        </div>
                                        <div class="small text-muted" style="font-family: monospace;">
                                            Time: <?php echo date('H:i:s', strtotime($record['timestamp'])); ?>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>
<script src="assets/js/history.js"></script>
