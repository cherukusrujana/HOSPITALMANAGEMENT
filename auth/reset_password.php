<?php
require_once '../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    
    if (!validate_email($email)) {
        $error = 'Invalid email format';
    } else {
        $token = generate_token();
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?)");
            if ($stmt->execute([$email, $token, $expiry])) {
                // Send email with reset link
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/auth/new_password.php?token=" . $token;
                // TODO: Implement email sending functionality
                $success = 'Password reset instructions have been sent to your email';
            } else {
                $error = 'Failed to process password reset';
            }
        } else {
            $error = 'Email not found';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Reset Password</div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Reset Link</button>
                    <a href="/auth/login.php" class="btn btn-secondary">Back to Login</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>