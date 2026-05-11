<?php
require_once 'config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit();
}

$error = '';
$redirect = $_GET['redirect'] ?? '/dashboard.php';

if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT id, username, password_hash, is_admin FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            header('Location: ' . $redirect);
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>

<?php
// Start output buffering to prevent header issues
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - China Orange Inc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="order-card">
                    <div class="text-center mb-4">
                        <i class="fas fa-orange text-warning" style="font-size: 3rem;"></i>
                        <h2 class="mt-3 mb-1">Welcome Back</h2>
                        <p class="text-muted">Sign in to your account</p>
                    </div>

                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username or Email</label>
                            <input type="text" class="form-control form-control-lg" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            <div class="invalid-feedback">Please enter your username or email</div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                            <div class="invalid-feedback">Please enter your password</div>
                        </div>

                        <button type="submit" class="btn btn-warning btn-lg w-100 mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="mb-0">Don't have an account? 
                            <a href="/register.php?redirect=<?php echo urlencode($redirect); ?>" class="text-warning text-decoration-none fw-bold">
                                Register here
                            </a>
                        </p>
                    </div>

                    <hr class="my-4">
                    
                    <div class="text-center">
                        <small class="text-muted">
                            <strong>Demo Accounts:</strong><br>
                            Admin: admin / admin123<br>
                            User: testuser / user123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html>
