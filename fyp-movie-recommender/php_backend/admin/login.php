<?php
session_start();
require_once '../includes/config.php';
require_once '../database/connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                if ($admin['status'] === 'blocked') {
                    $error = "Your account has been restricted.";
                } else {
                    // Set Admin Session
                    $_SESSION['admin_id'] = $admin['user_id'];
                    $_SESSION['admin_name'] = $admin['username'];

                    // Sync with User Session for Frontend Nav compatibility
                    $_SESSION['user_id'] = $admin['user_id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['email'] = $admin['email'];

                    header("Location: index.php");
                    exit();
                }
            } else {
                $error = "Invalid admin credentials.";
            }
        } catch (Exception $e) {
            $error = "System error occurred.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | MoodAI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .login-wrapper {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at center, #1a0101 0%, #050505 100%);
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
            background: rgba(20, 20, 20, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid var(--admin-glass-border);
            border-radius: 20px;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card animate-fade-in">
            <div class="text-center mb-4">
                <h2 class="sidebar-logo">Mood<span>AI</span> Admin</h2>
                <p class="text-muted small">Terminal Authentication Required</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small border-0 bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted small">Admin Email</label>
                    <input type="email" name="email" class="form-control bg-dark border-0 text-white p-3" placeholder="admin@moodai.com" required>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted small">Secret Key</label>
                    <input type="password" name="password" class="form-control bg-dark border-0 text-white p-3" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-admin-primary w-100 p-3">
                    Authenticate <i class="bi bi-shield-lock ms-2"></i>
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="../index.php" class="text-muted small text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i> Return to Site
                </a>
            </div>
        </div>
    </div>
</body>
</html>
