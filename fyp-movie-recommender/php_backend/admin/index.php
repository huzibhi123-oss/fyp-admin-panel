<?php
session_start();
require_once '../includes/config.php';
require_once '../database/connection.php';
require_once 'layout.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- FETCH REAL ANALYTICS DATA ---

// 1. Total Users
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// 2. Movies Catalog
$total_movies = $pdo->query("SELECT COUNT(*) FROM cached_movies")->fetchColumn();
$admin_movies_count = 0;
try {
    $admin_movies_count = $pdo->query("SELECT COUNT(*) FROM admin_movies")->fetchColumn();
} catch (Exception $e) {}
$grand_total_movies = $total_movies + $admin_movies_count;

// 3. AI Queries (History)
$total_queries = $pdo->query("SELECT COUNT(*) FROM user_mood_history")->fetchColumn();

// 4. Favorites Saved
$total_favorites = $pdo->query("SELECT COUNT(*) FROM user_favorites")->fetchColumn();

// 5. Mood Trends Data (Last 7 Days)
$mood_trends = $pdo->query("
    SELECT DATE(detected_at) as date, COUNT(*) as count
    FROM user_mood_history
    GROUP BY DATE(detected_at)
    ORDER BY date DESC LIMIT 7
")->fetchAll(PDO::FETCH_ASSOC);
$mood_trends = array_reverse($mood_trends);

$chart_labels = json_encode(array_column($mood_trends, 'date'));
$chart_data = json_encode(array_column($mood_trends, 'count'));

// 6. Mood Distribution (Pie Chart)
$mood_dist = $pdo->query("
    SELECT mood, COUNT(*) as count
    FROM user_mood_history
    GROUP BY mood
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pie_labels = json_encode(array_column($mood_dist, 'mood'));
$pie_data = json_encode(array_column($mood_dist, 'count'));

ob_start();
?>

<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="admin-card">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-people"></i>
            </div>
            <h6 class="text-muted small">Total Users</h6>
            <h3 class="fw-bold mb-0"><?php echo number_format($total_users); ?></h3>
            <div class="text-success small mt-2">
                <i class="bi bi-shield-check"></i> System Active
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card">
            <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                <i class="bi bi-film"></i>
            </div>
            <h6 class="text-muted small">Movies Catalog</h6>
            <h3 class="fw-bold mb-0"><?php echo number_format($grand_total_movies); ?></h3>
            <div class="text-muted small mt-2">
                Across All Genres
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card">
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-cpu"></i>
            </div>
            <h6 class="text-muted small">AI Queries</h6>
            <h3 class="fw-bold mb-0"><?php echo number_format($total_queries); ?></h3>
            <div class="text-success small mt-2">
                <i class="bi bi-lightning-charge"></i> Live Processing
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card">
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-heart"></i>
            </div>
            <h6 class="text-muted small">Favorites Saved</h6>
            <h3 class="fw-bold mb-0"><?php echo number_format($total_favorites); ?></h3>
            <div class="text-muted small mt-2">
                User engagement high
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="admin-card">
            <h5 class="fw-bold mb-4">AI Detection Activity (Last 7 Days)</h5>
            <div style="height: 300px;">
                <canvas id="moodChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-card">
            <h5 class="fw-bold mb-4">Mood Distribution</h5>
            <div style="height: 300px;">
                <canvas id="genreChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mood Activity Chart
    const moodCtx = document.getElementById('moodChart').getContext('2d');
    new Chart(moodCtx, {
        type: 'line',
        data: {
            labels: <?php echo $chart_labels; ?>,
            datasets: [{
                label: 'AI Mood Detections',
                data: <?php echo $chart_data; ?>,
                borderColor: '#950101',
                backgroundColor: 'rgba(149, 1, 1, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#888' } },
                x: { grid: { display: false }, ticks: { color: '#888' } }
            }
        }
    });

    // Mood Distribution Chart
    const genreCtx = document.getElementById('genreChart').getContext('2d');
    new Chart(genreCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo $pie_labels; ?>,
            datasets: [{
                data: <?php echo $pie_data; ?>,
                backgroundColor: ['#950101', '#3D0000', '#6F0000', '#B30101', '#FF0000', '#222'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#888', padding: 20 }
                }
            }
        }
    });
});
</script>

<?php
$content = ob_get_clean();
render_admin_layout($content, "Dashboard Overview", "dashboard");
?>
