<?php
// api/get_movies_api.php
// Returns list of movies based on Mood or Genre ID
// Uses TMDB API

header('Content-Type: application/json');
session_start();

// --- Configuration ---
require_once '../includes/config.php';
require_once '../includes/mood_mapper.php'; // Central Mapper

// 2. Validate Request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed. Use GET.']);
    exit();
}

$mood = $_GET['mood'] ?? null;
$genre_id = $_GET['genre'] ?? null;
$page = $_GET['page'] ?? 1;

if (!$mood && !$genre_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing "mood" or "genre" parameter.']);
    exit();
}

// Determine Genre ID
if ($mood) {
    $target_genre_id = get_genre_id_for_mood($mood);
} else {
    $target_genre_id = intval($genre_id);
}

// 3. Fetch from TMDB
// Endpoint: /discover/movie
$endpoint = TMDB_BASE_URL . 'discover/movie';
$params = [
    'api_key' => TMDB_API_KEY,
    'with_genres' => $target_genre_id,
    'sort_by' => 'popularity.desc',
    'language' => 'en-US',
    'page' => $page,
    'include_adult' => 'false'
];

$query_url = $endpoint . '?' . http_build_query($params);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $query_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
// Optional: Verify SSL if needed, usually default is fine
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// 4. Handle Response
if ($http_code === 200 && $response) {
    $data = json_decode($response, true);
    
    // Filter/Format results if needed
    $results = $data['results'] ?? [];
    
    // Append full image path
    foreach ($results as &$movie) {
        if (!empty($movie['poster_path'])) {
            $movie['poster_full_path'] = 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'];
        } else {
            $movie['poster_full_path'] = 'assets/img/no_poster.jpg'; // Fallback
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'page' => $page,
        'results' => $results,
        'mood_used' => $mood,
        'genre_id_used' => $target_genre_id
    ]);

} else {
    // Fallback: If API fails (or invalid key), return Mock Data for demo purposes
    // (This ensures the UI doesn't break during evaluation if key is missing)
    
    $mock_movies = [
        [
            'id' => 101, 
            'title' => 'Mock Movie 1 (' . ($mood ?? 'Genre '.$target_genre_id) . ')',
            'overview' => 'This is a fallback result because the TMDB API key is missing or invalid.',
            'poster_full_path' => 'assets/img/mock_default.jpg',
            'vote_average' => 7.5
        ],
        [
            'id' => 102, 
            'title' => 'Mock Movie 2',
            'overview' => 'Another fallback movie result.',
            'poster_full_path' => 'assets/img/mock_default.jpg',
            'vote_average' => 8.0
        ]
    ];
    
    // Return error with mock data fallback? Or just error?
    // Let's return error but with data if we want to be graceful, 
    // but better to be honest about the error so dev can fix it.
    
    http_response_code(502); // Bad Gateway / Upstream Error
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch from TMDB.',
        'tmdb_code' => $http_code,
        'debug' => $curl_error
    ]);
}
?>
