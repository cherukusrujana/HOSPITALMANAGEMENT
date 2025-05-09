<?php
require_once '../includes/header.php';

if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $email = sanitize_input($_POST['email']);
    $full_name = sanitize_input($_POST['full_name']);

    if (!validate_email($email)) {
        $error = 'Invalid email format';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $error = 'Username or email already exists';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'admin')");
            if ($stmt->execute([$username, $hashed_password, $email, $full_name])) {
                $success = 'Admin added successfully!';
            } else {
                $error = 'Failed to add admin';
            }
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-gradient py-4">
                    <div class="text-center">
                        <i class="fas fa-user-shield fa-3x mb-3"></i>
                        <h3 class="mb-0 fw-bold">Add New Admin</h3>
                    </div>
                </div>
                <div class="card-body p-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger bg-danger bg-opacity-10 border-0 text-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success bg-success bg-opacity-10 border-0 text-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control form-control-lg bg-opacity-50" id="username" name="username" placeholder="Username" required>
                            <label for="username">
                                <i class="fas fa-user me-2"></i>Username
                            </label>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="password" class="form-control form-control-lg bg-opacity-50" id="password" name="password" placeholder="Password" required>
                            <label for="password">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="email" class="form-control form-control-lg bg-opacity-50" id="email" name="email" placeholder="Email" required>
                            <label for="email">
                                <i class="fas fa-envelope me-2"></i>Email
                            </label>
                        </div>

                        <div class="form-floating mb-5">
                            <input type="text" class="form-control form-control-lg bg-opacity-50" id="full_name" name="full_name" placeholder="Full Name" required>
                            <label for="full_name">
                                <i class="fas fa-id-card me-2"></i>Full Name
                            </label>
                        </div>

                        <div class="d-grid gap-3">
                            <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold text-uppercase">
                                <i class="fas fa-plus-circle me-2"></i>Add Admin
                            </button>
                            <a href="/hospital/admin/dashboard.php" class="btn btn-outline-secondary btn-lg py-3 fw-bold text-uppercase">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.body.classList.add('dark-theme');
</script>

<?php require_once '../includes/footer.php';