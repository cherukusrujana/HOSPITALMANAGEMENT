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
    $specialization = sanitize_input($_POST['specialization']);
    $qualification = sanitize_input($_POST['qualification']);

    if (!validate_email($email)) {
        $error = 'Invalid email format';
    } else {
        $pdo->beginTransaction();
        try {
            // Check if username or email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                throw new Exception('Username or email already exists');
            }

            // Insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'doctor')");
            $stmt->execute([$username, $hashed_password, $email, $full_name]);
            $user_id = $pdo->lastInsertId();

            // Insert doctor
            $stmt = $pdo->prepare("INSERT INTO doctors (user_id, specialization, qualification) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $specialization, $qualification]);

            $pdo->commit();
            $success = 'Doctor added successfully!';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Doctor</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #007bff, #0056b3);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            margin-bottom: 40px;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: linear-gradient(135deg, #0056b3, #007bff);
            color: white;
            padding: 25px;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .card-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #003366;
            font-weight: 600;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            background-color: white;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .alert-success {
            background: linear-gradient(to right, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: linear-gradient(to right, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            margin-right: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #003366, #004080);
            color: white;
            box-shadow: 0 4px 6px rgba(0, 51, 102, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 51, 102, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 4px 6px rgba(108, 117, 125, 0.2);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(108, 117, 125, 0.3);
        }

        @media (max-width: 768px) {
            .card-header {
                font-size: 20px;
                padding: 20px;
            }

            .card-body {
                padding: 20px;
            }

            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">Add New Doctor</div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>

                    <div class="form-group">
                        <label for="specialization" class="form-label">Specialization</label>
                        <input type="text" class="form-control" id="specialization" name="specialization" required>
                    </div>

                    <div class="form-group">
                        <label for="qualification" class="form-label">Qualification</label>
                        <textarea class="form-control" id="qualification" name="qualification" rows="3" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Add Doctor</button>
                    <a href="/hospital/admin/dashboard.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php require_once '../includes/footer.php'; ?>