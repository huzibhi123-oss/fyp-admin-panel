# mood_to_genre.py
# Maps detected moods to TMDB Genre IDs

MOOD_GENRE_MAP = {
    'Happy': 35,      # Comedy
    'Sad': 18,        # Drama
    'Angry': 28,      # Action
    'Excited': 10751, # Family
    'Anxious': 53,    # Thriller
    'Relaxed': 10749, # Romance
    'Neutral': 10752, # War/Documentary
    'Default': 35
}

def get_genre_for_mood(mood):
    """
    Returns the TMDB genre ID associated with a mood.
    """
    mood_key = mood.capitalize() if mood else 'Default'
    return MOOD_GENRE_MAP.get(mood_key, MOOD_GENRE_MAP['Default'])

def get_all_mappings():
    return MOOD_GENRE_MAP
