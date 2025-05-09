<?php
require_once '../includes/header.php';

if (!is_logged_in() || $_SESSION['role'] !== 'user') {
    header('Location: /auth/login.php');
    exit();
}

// Fetch all doctors
$stmt = $pdo->prepare("SELECT d.id, d.specialization, u.full_name 
                      FROM doctors d 
                      JOIN users u ON d.user_id = u.id 
                      ORDER BY u.full_name");
$stmt->execute();
$doctors = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $reason = sanitize_input($_POST['reason']);

    // Validate date (must be future date)
    if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
        $error = 'Please select a future date';
    } else {
        // Check if slot is available
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments 
                              WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?");
        $stmt->execute([$doctor_id, $appointment_date, $appointment_time]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $error = 'This time slot is already booked. Please select another time.';
        } else {
            // Insert appointment
            $stmt = $pdo->prepare("INSERT INTO appointments 
                                  (user_id, doctor_id, appointment_date, appointment_time, reason) 
                                  VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $doctor_id, $appointment_date, $appointment_time, $reason])) {
                $success = 'Appointment booked successfully!';
            } else {
                $error = 'Failed to book appointment. Please try again.';
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
    <title>Book Appointment</title>
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
            max-width: 1000px;
            margin: 0 auto;
        }

        .welcome-header {
            color: white;
            margin-bottom: 30px;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            font-size: 2.5em;
            font-weight: 600;
        }

        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            margin-bottom: 40px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
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

        .form-control,
        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }

        .form-control:focus,
        .form-select:focus {
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
            .welcome-header {
                font-size: 2em;
            }

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
        <h2 class="welcome-header">Book an Appointment</h2>

        <div class="card">
            <div class="card-header">Schedule Your Visit</div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="doctor_id" class="form-label">Select Doctor</label>
                        <select class="form-select" id="doctor_id" name="doctor_id" required>
                            <option value="">Choose a doctor...</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['id']; ?>">
                                    <?php echo htmlspecialchars($doctor['full_name']); ?> 
                                    (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="appointment_date" class="form-label">Appointment Date</label>
                        <input type="date" class="form-control" id="appointment_date" name="appointment_date"
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="appointment_time" class="form-label">Appointment Time</label>
                        <select class="form-select" id="appointment_time" name="appointment_time" required>
                            <?php
                            $start = 9; // 9 AM
                            $end = 17;  // 5 PM
                            for ($hour = $start; $hour < $end; $hour++) {
                                $time = sprintf("%02d:00", $hour);
                                echo "<option value=\"$time\">$time</option>";
                                $time = sprintf("%02d:30", $hour);
                                echo "<option value=\"$time\">$time</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reason" class="form-label">Reason for Visit</label>
                        <textarea class="form-control" id="reason" name="reason" rows="4" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Book Appointment</button>
                    <a href="/hospital/user/dashboard.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php require_once '../includes/footer.php'; ?>