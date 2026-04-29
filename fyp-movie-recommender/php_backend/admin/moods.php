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

// --- HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'save_mood') {
                $id = $_POST['id'] ?? null;
                $name = $_POST['name'];
                $desc = $_POST['description'];
                $genres = $_POST['related_genres'];

                if ($id) {
                    $stmt = $pdo->prepare("UPDATE moods SET name=?, description=?, related_genres=? WHERE id=?");
                    $stmt->execute([$name, $desc, $genres, $id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO moods (name, description, related_genres) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $desc, $genres]);
                }
                $message = "Mood configuration updated!";
            } elseif ($_POST['action'] === 'delete_mood') {
                $stmt = $pdo->prepare("DELETE FROM moods WHERE id=?");
                $stmt->execute([$_POST['id']]);
                $message = "Mood removed.";
            }
        } catch (Exception $e) { $message = "Error: " . $e->getMessage(); }
    }
}

$moods = $pdo->query("SELECT * FROM moods ORDER BY name ASC")->fetchAll();

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Mood Management</h4>
    <button class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#moodModal" onclick="clearMoodForm()">
        <i class="bi bi-plus-lg me-2"></i> Define New Mood
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-success border-0 shadow-sm mb-4"><?php echo $message; ?></div>
<?php endif; ?>

<div class="row g-4">
    <?php foreach ($moods as $mood): ?>
        <div class="col-md-6 col-lg-4">
            <div class="admin-card h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="fw-bold text-white mb-0"><?php echo htmlspecialchars($mood['name']); ?></h5>
                    <div class="dropdown">
                        <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="#" onclick='editMood(<?php echo json_encode($mood); ?>)'>Edit Mood</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" onsubmit="return confirm('Delete this mood?')">
                                    <input type="hidden" name="action" value="delete_mood">
                                    <input type="hidden" name="id" value="<?php echo $mood['id']; ?>">
                                    <button type="submit" class="dropdown-item text-danger">Delete</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
                <p class="text-muted small mb-4"><?php echo htmlspecialchars($mood['description'] ?: 'No description provided.'); ?></p>
                <div class="mt-auto">
                    <div class="small text-muted mb-2">Linked Genre IDs:</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php
                        $gs = explode(',', $mood['related_genres']);
                        foreach($gs as $g): if(trim($g)):
                        ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><?php echo trim($g); ?></span>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Mood Modal -->
<div class="modal fade" id="moodModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary text-white">
            <form method="POST">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Mood Configuration</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_mood">
                    <input type="hidden" name="id" id="mood_id">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Mood Name</label>
                        <input type="text" name="name" id="mood_name" class="form-control bg-black border-secondary text-white" placeholder="e.g. Melancholic" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Description</label>
                        <textarea name="description" id="mood_desc" class="form-control bg-black border-secondary text-white" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Related TMDB Genre IDs (Comma separated)</label>
                        <input type="text" name="related_genres" id="mood_genres" class="form-control bg-black border-secondary text-white" placeholder="e.g. 18, 80" required>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="submit" class="btn btn-admin-primary w-100">Save Mood</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function clearMoodForm() {
    document.getElementById('mood_id').value = '';
    document.getElementById('mood_name').value = '';
    document.getElementById('mood_desc').value = '';
    document.getElementById('mood_genres').value = '';
}
function editMood(mood) {
    document.getElementById('mood_id').value = mood.id;
    document.getElementById('mood_name').value = mood.name;
    document.getElementById('mood_desc').value = mood.description;
    document.getElementById('mood_genres').value = mood.related_genres;
    new bootstrap.Modal(document.getElementById('moodModal')).show();
}
</script>

<?php
$content = ob_get_clean();
render_admin_layout($content, "Mood Management", "moods");
?>
