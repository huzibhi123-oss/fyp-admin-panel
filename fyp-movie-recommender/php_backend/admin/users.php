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
            $user_id = $_POST['user_id'];
            if ($_POST['action'] === 'toggle_status') {
                $new_status = $_POST['current_status'] === 'active' ? 'blocked' : 'active';
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
                $stmt->execute([$new_status, $user_id]);
                $message = "User status updated to $new_status.";
            } elseif ($_POST['action'] === 'delete_user') {
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role != 'admin'");
                $stmt->execute([$user_id]);
                $message = "User account permanently removed.";
            }
        } catch (Exception $e) { $message = "Error: " . $e->getMessage(); }
    }
}

// Fetch users with their detection count and last activity
$users = $pdo->query("
    SELECT u.*,
           (SELECT COUNT(*) FROM user_mood_history WHERE user_id = u.user_id) as total_detections,
           (SELECT MAX(detected_at) FROM user_mood_history WHERE user_id = u.user_id) as last_activity
    FROM users u
    WHERE u.role != 'admin'
    ORDER BY u.created_at DESC
")->fetchAll();

ob_start();
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">User Management</h4>
    <p class="text-muted small">Monitor community activity and moderate access.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-info border-0 shadow-sm mb-4"><?php echo $message; ?></div>
<?php endif; ?>

<div class="admin-table-container">
    <table class="table admin-table">
        <thead>
            <tr>
                <th>User Details</th>
                <th>Join Date</th>
                <th>AI Detections</th>
                <th>Last Active</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">No registered users found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-weight: bold;">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-white"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="small"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <span class="badge bg-dark border border-secondary"><?php echo $user['total_detections']; ?></span>
                        </td>
                        <td class="small text-muted">
                            <?php echo $user['last_activity'] ? date('M d, H:i', strtotime($user['last_activity'])) : 'Never'; ?>
                        </td>
                        <td>
                            <?php if ($user['status'] === 'active'): ?>
                                <span class="text-success small"><i class="bi bi-patch-check-fill me-1"></i> Active</span>
                            <?php else: ?>
                                <span class="text-danger small"><i class="bi bi-slash-circle-fill me-1"></i> Blocked</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <input type="hidden" name="current_status" value="<?php echo $user['status']; ?>">
                                <button type="submit" class="btn btn-sm <?php echo $user['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success'; ?> border-0">
                                    <i class="bi <?php echo $user['status'] === 'active' ? 'bi-person-x' : 'bi-person-check'; ?>"></i>
                                </button>
                            </form>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete user forever?')">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
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

<?php
$content = ob_get_clean();
render_admin_layout($content, "User Management", "users");
?>
