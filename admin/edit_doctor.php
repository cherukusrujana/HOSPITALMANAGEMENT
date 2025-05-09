<?php
require_once '../includes/header.php';

if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    header('Location: /hospital/auth/login.php');
    exit();
}

$success_message = $error_message = '';
$doctor_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$doctor_id) {
    header('Location: /hospital/admin/view_doctors.php');
    exit();
}

// Fetch doctor's details
$stmt = $pdo->prepare("SELECT d.*, u.full_name, u.email 
                      FROM doctors d 
                      JOIN users u ON d.user_id = u.id 
                      WHERE d.id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    header('Location: /hospital/admin/view_doctors.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = htmlspecialchars(trim($_POST['full_name'] ?? ''));
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $specialization = htmlspecialchars(trim($_POST['specialization'] ?? ''));
    $qualification = htmlspecialchars(trim($_POST['qualification'] ?? ''));
    $new_password = htmlspecialchars(trim($_POST['new_password'] ?? ''));

    if (empty($full_name) || empty($email) || empty($specialization) || empty($qualification)) {
        $error_message = 'All fields except password are required.';
    } else {
        try {
            $pdo->beginTransaction();

            // Update users table
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $doctor['user_id']]);

            // Update doctors table
            $stmt = $pdo->prepare("UPDATE doctors SET specialization = ?, qualification = ? WHERE id = ?");
            $stmt->execute([$specialization, $qualification, $doctor_id]);

            // Update password if provided
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $doctor['user_id']]);
            }

            $pdo->commit();
            $success_message = 'Doctor information updated successfully!';

            // Refresh doctor data
            $stmt = $pdo->prepare("SELECT d.*, u.full_name, u.email 
                                 FROM doctors d 
                                 JOIN users u ON d.user_id = u.id 
                                 WHERE d.id = ?");
            $stmt->execute([$doctor_id]);
            $doctor = $stmt->fetch();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = 'Failed to update doctor information. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor</title>
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
            border-bottom: none;
        }

        .card-header h5 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .card-body {
            padding: 30px;
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
            transition: border 0.3s;
            margin-bottom: 20px;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-primary {
            background: #003366;
            color: white;
        }

        .btn-primary:hover {
            background: #002244;
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid #003366;
            color: #003366;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: #f0f0f0;
        }

        .alert {
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h5>Edit Doctor</h5>
            </div>
            <div class="card-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($doctor['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="specialization" class="form-label">Specialization</label>
                        <input type="text" class="form-control" id="specialization" name="specialization" 
                               value="<?php echo htmlspecialchars($doctor['specialization']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="qualification" class="form-label">Qualification</label>
                        <input type="text" class="form-control" id="qualification" name="qualification" 
                               value="<?php echo htmlspecialchars($doctor['qualification']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>

                    <button type="submit" class="btn btn-primary">Update Doctor</button>
                    <a href="/hospital/admin/view_doctors.php" class="btn btn-secondary">Back to List</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

<?php require_once '../includes/footer.php'; ?>