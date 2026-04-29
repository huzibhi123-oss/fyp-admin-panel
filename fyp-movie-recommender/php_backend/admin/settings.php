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

// --- HANDLE SETTINGS UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    try {
        $settings = [
            'app_name' => $_POST['app_name'],
            'recommendation_count' => $_POST['recommendation_count'],
            'recommendation_logic' => $_POST['recommendation_logic'],
            'theme_color' => $_POST['theme_color']
        ];

        $stmt = $pdo->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value, $value]);
        }
        $message = "System settings updated successfully!";
    } catch (Exception $e) { $message = "Error: " . $e->getMessage(); }
}

// Fetch current settings
$current_settings = $pdo->query("SELECT * FROM admin_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

ob_start();
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">System Settings</h4>
    <p class="text-muted small">Configure global application behavior and appearance.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-success border-0 shadow-sm mb-4"><?php echo $message; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-card">
            <form method="POST">
                <input type="hidden" name="action" value="update_settings">

                <h5 class="fw-bold text-white mb-4">General Configuration</h5>

                <div class="mb-4">
                    <label class="form-label small text-muted">Application Name</label>
                    <input type="text" name="app_name" class="form-control bg-black border-secondary text-white" value="<?php echo htmlspecialchars($current_settings['app_name'] ?? 'MoodAI'); ?>">
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Recommendation Count</label>
                        <select name="recommendation_count" class="form-select bg-black border-secondary text-white">
                            <option value="5" <?php echo ($current_settings['recommendation_count'] ?? '') == '5' ? 'selected' : ''; ?>>Top 5 Movies</option>
                            <option value="10" <?php echo ($current_settings['recommendation_count'] ?? '') == '10' ? 'selected' : ''; ?>>Top 10 Movies</option>
                            <option value="20" <?php echo ($current_settings['recommendation_count'] ?? '') == '20' ? 'selected' : ''; ?>>Top 20 Movies</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Prioritization Logic</label>
                        <select name="recommendation_logic" class="form-select bg-black border-secondary text-white">
                            <option value="top-rated" <?php echo ($current_settings['recommendation_logic'] ?? '') == 'top-rated' ? 'selected' : ''; ?>>Top Rated First</option>
                            <option value="recent" <?php echo ($current_settings['recommendation_logic'] ?? '') == 'recent' ? 'selected' : ''; ?>>Recently Added</option>
                            <option value="random" <?php echo ($current_settings['recommendation_logic'] ?? '') == 'random' ? 'selected' : ''; ?>>Randomize</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small text-muted">Theme Accent Color</label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="color" name="theme_color" class="form-control form-control-color bg-black border-secondary" value="<?php echo $current_settings['theme_color'] ?? '#950101'; ?>">
                        <span class="text-muted small">This color is used for buttons, icons, and highlights across the app.</span>
                    </div>
                </div>

                <hr class="border-secondary my-4">

                <h5 class="fw-bold text-white mb-4">External Integrations</h5>

                <div class="mb-4">
                    <label class="form-label small text-muted">TMDB API Key (Global)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-black border-secondary text-muted"><i class="bi bi-key"></i></span>
                        <input type="text" class="form-control bg-black border-secondary text-white" value="<?php echo TMDB_API_KEY; ?>" disabled>
                        <button class="btn btn-outline-secondary" type="button" onclick="alert('For security, update the API key directly in includes/config.php')">Learn More</button>
                    </div>
                    <div class="form-text text-muted mt-2">The API key is currently managed via the core configuration file for maximum security.</div>
                </div>

                <div class="mt-5">
                    <button type="submit" class="btn btn-admin-primary px-5">
                        <i class="bi bi-save me-2"></i> Save System Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="admin-card bg-primary bg-opacity-10 border-primary border-opacity-25">
            <h5 class="fw-bold text-white mb-3">System Information</h5>
            <div class="mb-2 d-flex justify-content-between">
                <span class="text-muted small">PHP Version:</span>
                <span class="text-white small"><?php echo phpversion(); ?></span>
            </div>
            <div class="mb-2 d-flex justify-content-between">
                <span class="text-muted small">Database:</span>
                <span class="text-white small">MySQL (PDO)</span>
            </div>
            <div class="mb-4 d-flex justify-content-between">
                <span class="text-muted small">AI Backend:</span>
                <span class="text-success small">Online</span>
            </div>

            <div class="p-3 bg-black rounded border border-secondary">
                <h6 class="fw-bold text-white small mb-2"><i class="bi bi-info-circle me-2"></i> Note</h6>
                <p class="text-muted mb-0" style="font-size: 0.75rem;">
                    Changes to the "Recommendation Logic" will immediately affect the <strong>recommendation.php</strong> results for all users.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
render_admin_layout($content, "Settings", "settings");
?>
