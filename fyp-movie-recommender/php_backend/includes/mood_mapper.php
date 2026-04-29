<?php
/**
 * mood_mapper.php - Centralized Mood-to-Genre Mapping Protocol
 * 
 * Provides a unified way to map detected moods to TMDB Genre IDs.
 * Always attempts to query the Python AI service first to ensure the
 * AI's logic is the source of truth, with a local fallback.
 */

if (!function_exists('get_genre_id_for_mood')) {
    function get_genre_id_for_mood($mood) {
        $python_api_url = 'http://127.0.0.1:5000/genre?mood=' . urlencode($mood);

        // --- 1. ATTEMPT AI SYNC (PRIMARY) ---
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $python_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Quick timeout for seamless fallback

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['genre_id'])) {
                return (int)$data['genre_id'];
            }
        }

        // --- 2. LOCAL FALLBACK PROTOCOL (SECONDARY) ---
        // Synchronized with python_ai_backend/services/mood_to_genre.py
        $fallback_map = [
            'Happy'    => 35,      // Comedy
            'Sad'      => 18,      // Drama
            'Angry'    => 28,      // Action
            'Excited'  => 10751,   // Family
            'Anxious'  => 53,      // Thriller
            'Relaxed'  => 10749,   // Romance
            'Neutral'  => 10752,   // War
            'Default'  => 35       // Comedy
        ];

        $mood_key = ucfirst(strtolower($mood));
        return $fallback_map[$mood_key] ?? $fallback_map['Default'];
    }
}
?>
