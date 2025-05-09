<?php
require_once '../includes/header.php';

if (!is_logged_in() || $_SESSION['role'] !== 'user') {
    header('Location: /hospital/auth/login.php');
    exit();
}

$success_message = $error_message = '';

// Enable error reporting during development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch all doctors for the dropdown
try {
    $stmt = $pdo->query("SELECT d.id, u.full_name FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY u.full_name");
    $doctors = $stmt->fetchAll();
    if (empty($doctors)) {
        error_log('No doctors found in the database');
        $error_message = 'No doctors available for messaging.';
    }
} catch (PDOException $e) {
    error_log('Error fetching doctors: ' . $e->getMessage());
    $error_message = 'System error: Unable to load doctors list. Error: ' . $e->getMessage();
    $doctors = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_SANITIZE_NUMBER_INT);
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (empty($doctor_id) || empty($subject) || empty($message)) {
        $error_message = 'All fields are required.';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Get doctor's user_id
            $stmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
            if (!$stmt->execute([$doctor_id])) {
                throw new PDOException('Failed to execute doctor query: ' . implode(', ', $stmt->errorInfo()));
            }
            $doctor = $stmt->fetch();

            if ($doctor) {
                // Verify message table structure
                $check_table = $pdo->query("DESCRIBE messages");
                if (!$check_table) {
                    throw new PDOException('Messages table might not exist');
                }

                $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
                if (!$stmt->execute([$_SESSION['user_id'], $doctor['user_id'], $subject, $message])) {
                    throw new PDOException('Failed to insert message: ' . implode(', ', $stmt->errorInfo()));
                }
                $pdo->commit();
                $success_message = 'Message sent successfully!';
            } else {
                throw new PDOException('Invalid doctor ID: ' . $doctor_id);
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Message sending error: ' . $e->getMessage() . 
                      ' | User ID: ' . $_SESSION['user_id'] . 
                      ' | Doctor ID: ' . $doctor_id);
            // During development, show the actual error
            $error_message = 'Failed to send message. Error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(to bottom, #007bff, #0056b3);
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
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #0056b3, #007bff);
            color: white;
            padding: 20px;
            font-size: 20px;
            font-weight: 600;
        }

        .card-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
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
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 14px;
            background-color: #f9f9f9;
            transition: border 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M8 11l4-4H4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 40px;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: #003366;
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            background: #002244;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">Send Message to Doctor</div>
            <div class="card-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="doctor_id" class="form-label">Select Doctor</label>
                        <select class="form-control" id="doctor_id" name="doctor_id" required>
                            <option value="">Choose a doctor...</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['id']; ?>">
                                    <?php echo htmlspecialchars($doctor['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>

                    <div class="form-group">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
        <a href="/hospital/user/dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>
<?php require_once '../includes/footer.php'; ?>