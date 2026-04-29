<?php
session_start();
require_once '../includes/config.php';
require_once '../database/connection.php';
require_once 'layout.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch most liked movies
$most_liked = $pdo->query("
    SELECT movie_title, movie_poster, COUNT(*) as like_count
    FROM user_favorites
    GROUP BY tmdb_movie_id
    ORDER BY like_count DESC
    LIMIT 10
")->fetchAll();

// Fetch least liked (movies that are in favorites but have lowest counts)
$least_liked = $pdo->query("
    SELECT movie_title, movie_poster, COUNT(*) as like_count
    FROM user_favorites
    GROUP BY tmdb_movie_id
    ORDER BY like_count ASC
    LIMIT 10
")->fetchAll();

ob_start();
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">Feedback & Popularity</h4>
    <p class="text-muted small">Analyze which movies are resonating most with your users.</p>
</div>

<div class="row g-4">
    <!-- Most Liked Section -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="d-flex align-items-center mb-4">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3 mb-0">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h5 class="fw-bold text-white mb-0">Most Saved Movies</h5>
            </div>

            <div class="admin-table-container border-0">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Movie</th>
                            <th class="text-end">Saves</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($most_liked)): ?>
                            <tr><td colspan="2" class="text-center py-4 text-muted small">No data available yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($most_liked as $movie): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $movie['movie_poster']; ?>" class="rounded me-2" style="width: 30px; height: 40px; object-fit: cover;" onerror="this.src='../assets/img/no_poster.jpg'">
                                            <span class="small text-white text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($movie['movie_title']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-success bg-opacity-10 text-success"><?php echo $movie['like_count']; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Least Liked Section -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="d-flex align-items-center mb-4">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3 mb-0">
                    <i class="bi bi-graph-down-arrow"></i>
                </div>
                <h5 class="fw-bold text-white mb-0">Lowest Engagement</h5>
            </div>

            <div class="admin-table-container border-0">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Movie</th>
                            <th class="text-end">Saves</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($least_liked)): ?>
                            <tr><td colspan="2" class="text-center py-4 text-muted small">No data available yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($least_liked as $movie): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $movie['movie_poster']; ?>" class="rounded me-2" style="width: 30px; height: 40px; object-fit: cover;" onerror="this.src='../assets/img/no_poster.jpg'">
                                            <span class="small text-white text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($movie['movie_title']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-warning bg-opacity-10 text-warning"><?php echo $movie['like_count']; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
render_admin_layout($content, "Feedback & Ratings", "feedback");
?>
