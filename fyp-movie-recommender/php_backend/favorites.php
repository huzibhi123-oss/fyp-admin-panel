<?php
// favorites.php - Premium Neural Favorites
session_start();

require_once 'includes/config.php';
require_once 'database/connection.php';
require_once 'includes/header.php';
set_page_title("MoodAI | Neural Favorites");

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit();
}

$favorite_movies = [];
try {
    $stmt = $pdo->prepare("SELECT id, tmdb_movie_id, movie_title, movie_poster, mood_tag FROM user_favorites WHERE user_id = ? ORDER BY saved_at DESC");
    $stmt->execute([$user_id]);
    $favorite_movies = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database Error in favorites.php: " . $e->getMessage());
}
?>
<link rel="stylesheet" href="assets/css/favorites.css">

<main class="container pb-5 fade-in-section">

    <!-- Interface Header -->
    <header class="hero-section-premium mb-5 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-4">
        <div>
            <span class="interface-tag animate-reveal d-block">Loading your saved movies...</span>
            <h1 class="display-4 fw-800 animate-reveal d-block">Your Favorite Movies</h1>
            <p class="text-muted small animate-reveal d-block mb-0">Movies you saved based on your mood history.</p>
        </div>
        
        <div class="d-flex flex-column flex-md-row align-items-md-center gap-4 animate-reveal">
            <!-- Linguistic Filter Protocols -->
            <div class="dropdown">
                <button class="btn btn-filter dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-funnel me-1 text-bright-red"></i> Filter by Mood
                </button>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg border-danger">
                    <li><a class="dropdown-item dropdown-item-tech active" href="#" data-filter="all">All Protocols</a></li>
                    <li><a class="dropdown-item dropdown-item-tech" href="#" data-filter="happy">Happy</a></li>
                    <li><a class="dropdown-item dropdown-item-tech" href="#" data-filter="sad">Sad</a></li>
                    <li><a class="dropdown-item dropdown-item-tech" href="#" data-filter="angry">Angry</a></li>
                    <li><a class="dropdown-item dropdown-item-tech" href="#" data-filter="surprise">Surprise</a></li>
                    <li><a class="dropdown-item dropdown-item-tech" href="#" data-filter="neutral">Neutral</a></li>
                </ul>
            </div>

            <div class="text-md-end">
                <span id="recordCount" class="fw-bold text-white d-block" style="letter-spacing: 1px;"><?php echo count($favorite_movies); ?> MOVIES</span>
                <div class="small text-muted" style="font-size: 0.6rem; font-family: monospace;">STATUS: READY</div>
            </div>
        </div>
    </header>

    <div class="row justify-content-center">
        <div class="col-lg-11">

            <div id="favoritesContainer">

                <?php if (empty($favorite_movies)): ?>
                    <div class="empty-archive animate-reveal">
                        <i class="bi bi-film text-bright-red mb-3 d-block" style="font-size: 3rem;"></i>
                        <h4 class="fw-bold text-white">No Favorites Yet</h4>
                        <p class="text-white small mb-4">You haven't saved any movies to your favorites list yet.</p>
                        <a href="dashboard.php" class="btn btn-initiate mt-2">Find Movies</a>
                    </div>
                <?php else: ?>

                    <?php foreach ($favorite_movies as $movie): ?>
                        <div class="favorite-item animate-reveal" 
                             data-movie-id="<?php echo htmlspecialchars($movie['tmdb_movie_id']); ?>"
                             data-mood="<?php echo strtolower($movie['mood_tag']); ?>">
                            <div class="favorite-card">
                                <div class="card-body d-flex align-items-center gap-4">
                                    
                                    <!-- Poster -->
                                    <div class="favorite-poster-wrapper flex-shrink-0">
                                        <img src="<?php echo htmlspecialchars($movie['movie_poster']); ?>" 
                                             alt="Poster" 
                                             class="favorite-poster"
                                             onerror="this.src='assets/img/no_poster.jpg';">
                                    </div>

                                    <!-- Details -->
                                    <div class="flex-grow-1">
                                        <span class="archive-id">MOVIE ID: #<?php echo str_pad($movie['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                        <h4 class="fw-bold text-white mb-1"><?php echo htmlspecialchars($movie['movie_title']); ?></h4>
                                        <span class="mood-tag-tech">MOOD: <?php echo strtoupper($movie['mood_tag']); ?></span>
                                        <p class="text-white small mt-2 mb-0 d-none d-md-block fw-bold">
                                            Movie saved based on your mood history.
                                        </p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="favorite-actions ms-auto">
                                        <button class="btn btn-remove-tech remove-btn"
                                                data-movie-id="<?php echo htmlspecialchars($movie['tmdb_movie_id']); ?>">
                                            <i class="bi bi-trash-fill me-1 text-bright-red"></i> Remove
                                        </button>
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
<script src="assets/js/favorites.js"></script>
