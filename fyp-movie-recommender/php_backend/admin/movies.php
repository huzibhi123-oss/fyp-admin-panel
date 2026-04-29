<?php
session_start();
require_once '../includes/config.php';
require_once '../database/connection.php';
require_once 'layout.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = 'success';

// --- HANDLE ACTIONS (Add/Edit/Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'save') {
                $id = $_POST['id'] ?? null;
                $title = $_POST['title'];
                $description = $_POST['description'];
                $genre = $_POST['genre'];
                $language = $_POST['language'];
                $release_year = $_POST['release_year'];
                $rating = $_POST['rating'];
                $poster_url = $_POST['poster_url'];
                $mood_ids = $_POST['mood_ids'] ?? [];

                $pdo->beginTransaction();
                if ($id) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE admin_movies SET title=?, description=?, genre=?, language=?, release_year=?, rating=?, poster_url=? WHERE id=?");
                    $stmt->execute([$title, $description, $genre, $language, $release_year, $rating, $poster_url, $id]);

                    // Update mappings
                    $stmt = $pdo->prepare("DELETE FROM movie_mood_mapping WHERE movie_id = ?");
                    $stmt->execute([$id]);
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO admin_movies (title, description, genre, language, release_year, rating, poster_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $genre, $language, $release_year, $rating, $poster_url]);
                    $id = $pdo->lastInsertId();
                }

                if (!empty($mood_ids)) {
                    $stmt = $pdo->prepare("INSERT INTO movie_mood_mapping (movie_id, mood_id) VALUES (?, ?)");
                    foreach ($mood_ids as $mood_id) {
                        $stmt->execute([$id, $mood_id]);
                    }
                }
                $pdo->commit();
                $message = "Movie and mood tags saved successfully!";
            } elseif ($_POST['action'] === 'delete') {
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM admin_movies WHERE id=?");
                $stmt->execute([$id]);
                $message = "Movie removed successfully.";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// --- FETCH MOVIES WITH MOOD TAGS ---
$movies = $pdo->query("
    SELECT m.*, GROUP_CONCAT(mo.name SEPARATOR ', ') as mood_tags
    FROM admin_movies m
    LEFT JOIN movie_mood_mapping mapping ON m.id = mapping.movie_id
    LEFT JOIN moods mo ON mapping.mood_id = mo.id
    GROUP BY m.id
    ORDER BY m.created_at DESC
")->fetchAll();

$all_moods = $pdo->query("SELECT * FROM moods ORDER BY name ASC")->fetchAll();

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Curated Movies Catalog</h4>
    <button class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#movieModal" onclick="openAddModal()">
        <i class="bi bi-plus-lg me-2"></i> Add New Movie
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="admin-table-container">
    <table class="table admin-table">
        <thead>
            <tr>
                <th>Poster</th>
                <th>Movie Info</th>
                <th>Mood Tags</th>
                <th>Genre/Lang</th>
                <th>Rating</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($movies)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-film display-4 d-block mb-3 opacity-25"></i>
                        No curated movies found. Start by adding one!
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($movies as $movie): ?>
                    <tr>
                        <td style="width: 80px;">
                            <img src="<?php echo $movie['poster_url']; ?>" alt="Poster" class="rounded" style="width: 60px; height: 80px; object-fit: cover;" onerror="this.src='../assets/img/no_poster.jpg'">
                        </td>
                        <td>
                            <div class="fw-bold text-white"><?php echo htmlspecialchars($movie['title']); ?></div>
                            <div class="small text-muted"><?php echo $movie['release_year']; ?></div>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <?php if($movie['mood_tags']): ?>
                                    <?php foreach(explode(', ', $movie['mood_tags']) as $tag): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 0.65rem;"><?php echo htmlspecialchars($tag); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted small">No tags</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-dark border border-secondary"><?php echo htmlspecialchars($movie['genre']); ?></span>
                            <div class="small text-muted mt-1"><?php echo htmlspecialchars($movie['language']); ?></div>
                        </td>
                        <td>
                            <div class="text-warning"><i class="bi bi-star-fill"></i> <?php echo $movie['rating']; ?></div>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-light border-0" onclick='openEditModal(<?php echo json_encode($movie); ?>)'>
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $movie['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Movie Modal (Add/Edit) -->
<div class="modal fade" id="movieModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="modalTitle">Add New Movie</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="movie_id">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small text-muted">Movie Title</label>
                            <input type="text" name="title" id="title" class="form-control bg-black border-secondary text-white" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Release Year</label>
                            <input type="number" name="release_year" id="release_year" class="form-control bg-black border-secondary text-white" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small text-muted">Description</label>
                            <textarea name="description" id="description" rows="3" class="form-control bg-black border-secondary text-white"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Genre</label>
                            <input type="text" name="genre" id="genre" class="form-control bg-black border-secondary text-white" placeholder="e.g. Action, Sci-Fi" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Language</label>
                            <input type="text" name="language" id="language" class="form-control bg-black border-secondary text-white" placeholder="e.g. English" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Rating (0-10)</label>
                            <input type="number" step="0.1" name="rating" id="rating" class="form-control bg-black border-secondary text-white" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small text-muted">Mood Tags (Select all that apply)</label>
                            <div class="d-flex flex-wrap gap-3 p-3 bg-black rounded border border-secondary">
                                <?php foreach($all_moods as $mood): ?>
                                    <div class="form-check">
                                        <input class="form-check-input mood-checkbox" type="checkbox" name="mood_ids[]" value="<?php echo $mood['id']; ?>" id="mood_check_<?php echo $mood['id']; ?>">
                                        <label class="form-check-label small" for="mood_check_<?php echo $mood['id']; ?>">
                                            <?php echo htmlspecialchars($mood['name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small text-muted">Poster URL</label>
                            <input type="url" name="poster_url" id="poster_url" class="form-control bg-black border-secondary text-white" placeholder="https://..." required onchange="previewPoster(this.value)">
                            <div class="mt-2 text-center">
                                <img id="poster_preview" src="" class="rounded" style="max-height: 180px; display: none; border: 1px solid var(--admin-glass-border);">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-admin-primary px-4">Save Movie</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add New Movie';
    document.getElementById('movie_id').value = '';
    document.getElementById('title').value = '';
    document.getElementById('description').value = '';
    document.getElementById('genre').value = '';
    document.getElementById('language').value = '';
    document.getElementById('release_year').value = new Date().getFullYear();
    document.getElementById('rating').value = '7.5';
    document.getElementById('poster_url').value = '';
    document.getElementById('poster_preview').style.display = 'none';

    // Uncheck all moods
    document.querySelectorAll('.mood-checkbox').forEach(cb => cb.checked = false);
}

function openEditModal(movie) {
    document.getElementById('modalTitle').innerText = 'Edit Movie';
    document.getElementById('movie_id').value = movie.id;
    document.getElementById('title').value = movie.title;
    document.getElementById('description').value = movie.description;
    document.getElementById('genre').value = movie.genre;
    document.getElementById('language').value = movie.language;
    document.getElementById('release_year').value = movie.release_year;
    document.getElementById('rating').value = movie.rating;
    document.getElementById('poster_url').value = movie.poster_url;
    previewPoster(movie.poster_url);

    // Handle mood checkboxes
    document.querySelectorAll('.mood-checkbox').forEach(cb => cb.checked = false);
    if (movie.mood_tags) {
        const tags = movie.mood_tags.split(', ');
        document.querySelectorAll('.mood-checkbox').forEach(cb => {
            const moodName = cb.nextElementSibling.innerText.trim();
            if (tags.includes(moodName)) {
                cb.checked = true;
            }
        });
    }

    var modal = new bootstrap.Modal(document.getElementById('movieModal'));
    modal.show();
}

function previewPoster(url) {
    const img = document.getElementById('poster_preview');
    if (url) {
        img.src = url;
        img.style.display = 'inline-block';
    } else {
        img.style.display = 'none';
    }
}
</script>

<?php
$content = ob_get_clean();
render_admin_layout($content, "Movie Management", "movies");
?>
