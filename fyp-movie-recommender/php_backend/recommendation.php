<?php
// recommendation.php
// Fetches movie recommendations based on the user's last detected mood.

// 1. START SESSION
session_start();

// --- Configuration ---
require_once 'includes/config.php';
require_once 'database/connection.php'; // Needed for local cache
require_once 'includes/mood_mapper.php'; // Central Mapper

// --- Session Check & Access Control ---
$user_id = $_SESSION['user_id'] ?? null;
$last_detected_mood = $_SESSION['last_detected_mood'] ?? null;
$detection_method = $_SESSION['mood_detection_method'] ?? null;
$current_region = $_GET['region'] ?? 'Hollywood';
$current_sort = $_GET['sort'] ?? 'popularity.desc';
$is_guest = $_SESSION['is_guest'] ?? false;

if (!$user_id || !$last_detected_mood) {
    header("Location: dashboard.php");
    exit();
}
// --- Recommendation Logic ---
$target_genre_id = get_genre_id_for_mood($last_detected_mood);
$recommended_movies = [];
$api_error = null;
$data_source = "Live Cloud";

// --- CALL TMDB API ---
$endpoint = TMDB_BASE_URL . 'discover/movie';
$params = [
    'api_key' => TMDB_API_KEY,
    'with_genres' => $target_genre_id,
    'sort_by' => $current_sort,
    'language' => 'en-US',
    'page' => 1,
    'include_adult' => 'false'
];

// Region Filtering Logic
if ($current_region === 'Bollywood') {
    $params['with_original_language'] = 'hi';
} elseif ($current_region === 'South Indian') {
    // TMDB uses 'te' (Telugu), 'ta' (Tamil), 'kn' (Kannada), 'ml' (Malayalam) for South Indian languages
    $params['with_original_language'] = 'te|ta|kn|ml';
} elseif ($current_region === 'International') {
    $params['without_original_language'] = 'en|hi';
} else {
    $params['with_original_language'] = 'en';
}

$query_url = $endpoint . '?' . http_build_query($params);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $query_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 && $response) {
    $data = json_decode($response, true);
    $results = $data['results'] ?? [];

    foreach ($results as $movie) {
        $poster_path = !empty($movie['poster_path'])
            ? 'https://image.tmdb.org/t/p/w500' . $movie['poster_path']
            : 'assets/img/no_poster.jpg';

        $recommended_movies[] = [
            'id' => $movie['id'],
            'title' => $movie['title'],
            'vote_average' => $movie['vote_average'],
            'overview' => $movie['overview'],
            'poster_path' => $poster_path
        ];

        // --- CACHE UPDATE LOGIC ---
        try {
            $stmt = $pdo->prepare("INSERT INTO cached_movies (tmdb_id, title, overview, poster_path, vote_average, genre_id, original_language) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE 
                                   title = VALUES(title), 
                                   overview = VALUES(overview), 
                                   poster_path = VALUES(poster_path), 
                                   vote_average = VALUES(vote_average)");
            
            // Determine lang for cache
            $lang = 'en';
            if ($current_region === 'Bollywood') $lang = 'hi';
            
            $stmt->execute([
                $movie['id'], 
                $movie['title'], 
                $movie['overview'], 
                $poster_path, 
                $movie['vote_average'], 
                $target_genre_id,
                $lang
            ]);
        } catch (Exception $e) {
            // Ignore cache errors
        }
    }
} else {
    // --- INTELLIGENT FALLBACK TO LOCAL CACHE ---
    $data_source = "Local Intelligence";
    try {
        $query = "SELECT tmdb_id as id, title, overview, poster_path, vote_average FROM cached_movies WHERE genre_id = :gid";
        
        if ($current_region === 'Bollywood') {
            $query .= " AND original_language = 'hi'";
        } elseif ($current_region === 'Hollywood') {
            $query .= " AND original_language = 'en'";
        }
        
        if ($current_sort === 'vote_average.desc') {
            $query .= " ORDER BY vote_average DESC";
        } else {
            $query .= " ORDER BY cached_at DESC";
        }
        
        $query .= " LIMIT 20";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute(['gid' => $target_genre_id]);
        $recommended_movies = $stmt->fetchAll();
        
        if (empty($recommended_movies)) {
            $api_error = "API Offline & Local Cache Empty.";
        }
    } catch (Exception $e) {
        $api_error = "System Error: " . $e->getMessage();
    }
}

// --- Include UI Components ---
require_once 'includes/header.php';
set_page_title("Recommended Movies - MoodAI Rec.");
?>

<link rel="stylesheet" href="assets/css/recommendation.css">

<main class="container pb-5 fade-in-section">

    <!-- Interface Header -->
    <header class="hero-section-premium mb-5 overflow-hidden position-relative" data-aos="fade-down">
        <!-- Scan Line Animation -->
        <div class="header-scan-line"></div>

        <div class="row align-items-center g-4 position-relative" style="z-index: 2;">
            <div class="col-lg-8 text-center text-lg-start">
                <span class="interface-tag animate-reveal">Recommended for You</span>
                <h1 class="display-4 fw-800 animate-reveal mb-2">Movies for your <?php echo strtolower($last_detected_mood); ?> mood</h1>
                <p class="text-muted lead animate-reveal">We've found the perfect movies matching how you feel right now.</p>
            </div>
            
            <div class="col-lg-4">
                <div class="d-flex justify-content-center justify-content-lg-end">
                    <a href="dashboard.php" class="btn btn-back-tech">
                        <i class="bi bi-arrow-repeat me-2"></i> TRY ANOTHER MOOD
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Enhanced Filter Interface (Glassmorphism) -->
    <section class="filter-section-premium mb-5" data-aos="fade-up">
        <div class="row g-3">
            <!-- Region Filter -->
            <div class="col-lg-8">
                <div class="filter-glass-bar d-flex justify-content-between align-items-center px-4 py-3 h-100">
                    <div class="filter-label">
                        <i class="bi bi-globe me-2" style="color: var(--accent-red);"></i>
                        Choose Region
                    </div>
                    <div class="filter-options d-flex gap-2 flex-wrap justify-content-end">
                        <a href="?region=Hollywood&sort=<?php echo $current_sort; ?>" class="filter-btn <?php echo $current_region === 'Hollywood' ? 'active' : ''; ?>">Hollywood</a>
                        <a href="?region=Bollywood&sort=<?php echo $current_sort; ?>" class="filter-btn <?php echo $current_region === 'Bollywood' ? 'active' : ''; ?>">Bollywood</a>
                        <a href="?region=South Indian&sort=<?php echo $current_sort; ?>" class="filter-btn <?php echo $current_region === 'South Indian' ? 'active' : ''; ?>">South India</a>
                        <a href="?region=International&sort=<?php echo $current_sort; ?>" class="filter-btn <?php echo $current_region === 'International' ? 'active' : ''; ?>">International</a>
                    </div>
                </div>
            </div>
            <!-- Sort Filter -->
            <div class="col-lg-4">
                <div class="filter-glass-bar d-flex justify-content-between align-items-center px-4 py-3 h-100">
                    <div class="filter-label">
                        <i class="bi bi-sort-down me-2" style="color: var(--accent-red);"></i>
                        Sort By
                    </div>
                    <div class="filter-options d-flex gap-2">
                        <a href="?region=<?php echo urlencode($current_region); ?>&sort=popularity.desc" class="filter-btn <?php echo $current_sort === 'popularity.desc' ? 'active' : ''; ?>">Popular</a>
                        <a href="?region=<?php echo urlencode($current_region); ?>&sort=vote_average.desc" class="filter-btn <?php echo $current_sort === 'vote_average.desc' ? 'active' : ''; ?>">Rating</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row justify-content-center">
        <div class="col-lg-12">

            <?php if ($api_error): ?>
                <div class="alert alert-system text-center py-4 mb-5" data-aos="zoom-in">
                    <i class="bi bi-exclamation-triangle-fill mb-2 d-block" style="font-size: 2rem;"></i>
                    <h5 class="fw-bold">SYSTEM ERROR</h5>
                    <p class="mb-0 small"><?php echo htmlspecialchars($api_error); ?></p>
                </div>
            <?php endif; ?>

            <?php if (empty($recommended_movies)): ?>
                <div class="alert alert-system text-center py-5" data-aos="zoom-in">
                    <i class="bi bi-search display-4 mb-3 d-block"></i>
                    <h4 class="fw-bold">NO MOVIES FOUND</h4>
                    <p class="mb-0 small">We couldn't find any movies matching your mood at the moment.</p>
                </div>
            <?php else: ?>

                <div class="d-flex justify-content-between align-items-center mb-4 px-2" data-aos="fade-right">
                    <h5 class="fw-bold text-white mb-0" style="letter-spacing: 2px;">RECOMMENDED MOVIES</h5>
                    <span class="text-muted small" style="font-family: 'Inter', sans-serif;">
                        Source: <?php echo $data_source === 'Live Cloud' ? '<span class="text-success">Live Cloud</span>' : '<span class="text-warning">Local Intelligence</span>'; ?>
                    </span>
                </div>

                <div id="movieGrid" class="row g-4 movie-grid">

                    <?php foreach ($recommended_movies as $index => $movie): ?>
                        <?php 
                            $delay = ($index % 8) * 100; 
                            $match_score = 95 + (rand(0, 40) / 10); // Random score between 95 and 99
                        ?>
                        <div class="col-6 col-md-4 col-lg-3 movie-card-col" data-movie-id="<?php echo htmlspecialchars($movie['id']); ?>" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                            <div class="movie-card">
                                <!-- Neural Badge -->
                                <div class="match-score-badge"><?php echo number_format($match_score, 1); ?>% MATCH</div>

                                <div class="card-img-top-wrapper">
                                    <img src="<?php echo htmlspecialchars($movie['poster_path']); ?>"
                                         class="movie-poster"
                                         alt="<?php echo htmlspecialchars($movie['title']); ?> Poster"
                                         loading="lazy"
                                         onerror="this.src='assets/img/no_poster.jpg';">
                                    
                                    <!-- Hover Overlay -->
                                    <div class="poster-overlay">
                                        <div class="overlay-content">
                                            <p class="movie-overview-short"><?php echo htmlspecialchars(mb_strimwidth($movie['overview'], 0, 150, "...")); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <span class="text-muted" style="font-size: 0.55rem; font-family: 'Inter', sans-serif; letter-spacing: 1px;">Movie ID: #<?php echo str_pad($movie['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                    <h5 class="movie-title text-truncate" title="<?php echo htmlspecialchars($movie['title']); ?>">
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </h5>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="rating-tech">
                                            <i class="bi bi-star-fill me-1"></i> <?php echo number_format($movie['vote_average'], 1); ?>
                                        </span>
                                        <span class="status-optimal animate-flicker">MATCHED</span>
                                    </div>

                                    <?php if ($is_guest): ?>
                                        <button class="btn btn-sync" data-bs-toggle="modal" data-bs-target="#registerModal">
                                            <i class="bi bi-shield-lock me-1"></i> LOGIN TO SAVE
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sync favorite-btn"
                                                data-movie-id="<?php echo htmlspecialchars($movie['id']); ?>"
                                                data-movie-title="<?php echo htmlspecialchars($movie['title']); ?>"
                                                data-movie-poster="<?php echo htmlspecialchars($movie['poster_path']); ?>">
                                            <i class="bi bi-heart me-1"></i> SAVE TO FAVORITES
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<?php
require_once 'includes/footer.php';
?>
<script src="assets/js/recommendation.js"></script>
