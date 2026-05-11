<?php
require_once 'config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit();
}

$error = '';
$success = '';
$redirect = $_GET['redirect'] ?? '/dashboard.php';

if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Username or email already exists';
        } else {
            // Create new user
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, balance) VALUES (?, ?, ?, 1000.00)");
            
            if ($stmt->execute([$username, $email, $passwordHash])) {
                $userId = $conn->lastInsertId();
                
                // Auto-login the user
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['is_admin'] = false;
                
                header('Location: ' . $redirect);
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
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
    <title>Register - China Orange Inc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="order-card">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus text-warning" style="font-size: 3rem;"></i>
                        <h2 class="mt-3 mb-1">Create Account</h2>
                        <p class="text-muted">Join us and start ordering premium oranges</p>
                    </div>

                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control form-control-lg" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required minlength="3">
                            <div class="invalid-feedback">Username must be at least 3 characters long</div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" required minlength="6">
                            <div class="invalid-feedback">Password must be at least 6 characters long</div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" required>
                            <div class="invalid-feedback">Please confirm your password</div>
                        </div>

                        <div class="mb-4">
                            <div class="alert alert-info">
                                <i class="fas fa-gift me-2"></i>
                                <strong>Welcome Bonus:</strong> Get $1000 credit upon registration!
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning btn-lg w-100 mb-3">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="mb-0">Already have an account? 
                            <a href="/login.php?redirect=<?php echo urlencode($redirect); ?>" class="text-warning text-decoration-none fw-bold">
                                Sign in here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
