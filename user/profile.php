<?php
require_once '../includes/header.php';

if (!is_logged_in()) {
    header('Location: /auth/login.php');
    exit();
}

$error = '';
$success = '';

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($current_password)) {
        // Password change requested
        if (!password_verify($current_password, $user['password'])) {
            $error = 'Current password is incorrect';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            $success = 'Password updated successfully';
        }
    }

    if (empty($error)) {
        // Update profile information
        if ($email !== $user['email']) {
            // Check if email is already taken
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->rowCount() > 0) {
                $error = 'Email is already in use';
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            if ($stmt->execute([$full_name, $email, $_SESSION['user_id']])) {
                $success = 'Profile updated successfully';
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            } else {
                $error = 'Failed to update profile';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings</title>
    <style>
        body {
            background: linear-gradient(to bottom, #007bff, #0056b3);
            min-height: 100vh;
            padding: 40px 0;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 880px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #0056b3, #007bff);
            color: white;
            padding: 20px;
            font-size: 24px;
            font-weight: 600;
            border-bottom: none;
        }

        .card-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            color: #003366;
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 14px;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .section-title {
            color: #003366;
            margin: 30px 0 15px;
            font-size: 18px;
            font-weight: 600;
        }

        .divider {
            height: 1px;
            background: #e0e0e0;
            margin: 15px 0;
        }

        <!-- Add this to your existing style section -->
        <style>
            .button-group {
                display: flex;
                gap: 15px;
                margin-top: 20px;
            }

            .btn {
                flex: 1;
                width: auto;
                padding: 12px;
                border: none;
                border-radius: 10px;
                font-weight: bold;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.3s ease;
                text-align: center;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .btn-primary {
                background: #003366;
                color: white;
            }

            .btn-primary:hover {
                background: #002244;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            .btn-secondary {
                background: transparent;
                border: 2px solid #003366;
                color: #003366;
            }

            .btn-secondary:hover {
                background: #f0f0f0;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            .btn-outline {
                background: transparent;
                border: 2px solid #007bff;
                color: #007bff;
            }

            .btn-outline:hover {
                background: #007bff;
                color: white;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }
        </style>

        <!-- Replace the submit button section with this new button group -->
        <div class="button-group">
            <button type="submit" class="btn btn-primary">Update Profile</button>
            <a href="/hospital/user/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <?php if ($_SESSION['role'] === 'doctor'): ?>
                <a href="/hospital/doctor/messages.php" class="btn btn-outline">Messages</a>
            <?php elseif ($_SESSION['role'] === 'user'): ?>
                <a href="/hospital/user/book_appointment.php" class="btn btn-outline">Book Appointment</a>
            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                <a href="/hospital/admin/view_doctors.php" class="btn btn-outline">Manage Doctors</a>
            <?php endif; ?>
        </div>
        <!-- Add this inside the existing style section -->
        <style>
            .alert {
                border-radius: 10px;
                padding: 15px 20px;
                margin-bottom: 20px;
                border: 1px solid transparent;
                font-weight: 500;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .alert-success {
                background-color: #d4edda;
                border-color: #c3e6cb;
                color: #155724;
            }

            .alert-danger {
                background-color: #f8d7da;
                border-color: #f5c6cb;
                color: #721c24;
                animation: shake 0.5s ease-in-out;
            }

            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        </style>
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">Profile Settings</div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <h5 class="section-title">Change Password</h5>
                    <div class="divider"></div>

                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>

                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php require_once '../includes/footer.php'; ?>