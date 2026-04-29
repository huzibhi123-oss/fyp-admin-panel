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

// --- HANDLE MAPPING UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_mapping') {
    try {
        $mood_id = $_POST['mood_id'];
        $movie_ids = $_POST['movie_ids'] ?? [];

        $pdo->beginTransaction();

        // Clear existing mappings for this mood
        $stmt = $pdo->prepare("DELETE FROM movie_mood_mapping WHERE mood_id = ?");
        $stmt->execute([$mood_id]);

        // Add new mappings
        if (!empty($movie_ids)) {
            $stmt = $pdo->prepare("INSERT INTO movie_mood_mapping (mood_id, movie_id) VALUES (?, ?)");
            foreach ($movie_ids as $movie_id) {
                $stmt->execute([$mood_id, $movie_id]);
            }
        }

        $pdo->commit();
        $message = "Mappings updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

$moods = $pdo->query("SELECT * FROM moods ORDER BY name ASC")->fetchAll();
$all_movies = $pdo->query("SELECT id, title, release_year FROM admin_movies ORDER BY title ASC")->fetchAll();

// Get current mappings
$current_mappings = [];
$maps = $pdo->query("SELECT mood_id, movie_id FROM movie_mood_mapping")->fetchAll();
foreach ($maps as $m) {
    $current_mappings[$m['mood_id']][] = $m['movie_id'];
}

ob_start();
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">Mood ↔ Movie Mapping</h4>
    <p class="text-muted small">Curate exactly which movies appear for each detected mood.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-success border-0 shadow-sm mb-4"><?php echo $message; ?></div>
<?php endif; ?>

<div class="row g-4">
    <?php foreach ($moods as $mood): ?>
        <div class="col-lg-6">
            <div class="admin-card">
                <div class="d-flex align-items-center mb-4">
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3 mb-0" style="width: 40px; height: 40px;">
                        <i class="bi bi-diagram-3" style="font-size: 1.1rem;"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-white mb-0"><?php echo htmlspecialchars($mood['name']); ?></h5>
                        <span class="text-muted small"><?php echo count($current_mappings[$mood['id']] ?? []); ?> Movies Linked</span>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="update_mapping">
                    <input type="hidden" name="mood_id" value="<?php echo $mood['id']; ?>">

                    <div class="mapping-scroll-area mb-4" style="max-height: 300px; overflow-y: auto; padding-right: 10px;">
                        <?php if (empty($all_movies)): ?>
                            <div class="text-center py-4">
                                <p class="text-muted small">No movies in catalog. <a href="movies.php" class="text-danger">Add movies first</a>.</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-2">
                                <?php foreach ($all_movies as $movie): ?>
                                    <?php $is_checked = in_array($movie['id'], $current_mappings[$mood['id']] ?? []); ?>
                                    <div class="col-12">
                                        <label class="mapping-item d-flex align-items-center p-2 rounded <?php echo $is_checked ? 'bg-danger bg-opacity-10' : ''; ?>" style="cursor: pointer; border: 1px solid var(--admin-glass-border);">
                                            <input type="checkbox" name="movie_ids[]" value="<?php echo $movie['id']; ?>" class="form-check-input me-3 ms-2" <?php echo $is_checked ? 'checked' : ''; ?> onchange="this.parentElement.classList.toggle('bg-danger', this.checked); this.parentElement.classList.toggle('bg-opacity-10', this.checked);">
                                            <div class="small">
                                                <div class="fw-bold <?php echo $is_checked ? 'text-white' : 'text-muted'; ?>"><?php echo htmlspecialchars($movie['title']); ?></div>
                                                <div class="text-muted" style="font-size: 0.7rem;"><?php echo $movie['release_year']; ?></div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-admin-primary btn-sm w-100">
                        Update Mappings for <?php echo $mood['name']; ?>
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.mapping-scroll-area::-webkit-scrollbar { width: 5px; }
.mapping-scroll-area::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
.mapping-scroll-area::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
.mapping-scroll-area::-webkit-scrollbar-thumb:hover { background: #444; }
.mapping-item:hover { background: rgba(255,255,255,0.05); }
</style>

<?php
$content = ob_get_clean();
render_admin_layout($content, "Core Mapping", "mapping");
?>
