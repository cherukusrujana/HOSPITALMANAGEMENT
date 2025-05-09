<?php
require_once '../includes/header.php';

if (!is_logged_in() || $_SESSION['role'] !== 'doctor') {
    header('Location: /hospital/auth/login.php');
    exit();
}

$success_message = $error_message = '';
$today_appointments = $upcoming_appointments = [];
$unread_messages = 0;

// Get or create doctor's profile
$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();

if (!$doctor) {
    try {
        // Create new doctor profile
        $stmt = $pdo->prepare("INSERT INTO doctors (user_id, created_at) VALUES (?, NOW())");
        if ($stmt->execute([$_SESSION['user_id']])) {
            // Get the newly created doctor's ID
            $doctor_id = $pdo->lastInsertId();
            $success_message = 'Doctor profile created successfully.';
        } else {
            $error_message = 'Failed to create doctor profile. Please contact administrator.';
        }
    } catch (PDOException $e) {
        error_log('Error creating doctor profile: ' . $e->getMessage());
        $error_message = 'System error occurred. Please contact administrator.';
    }
} else {
    $doctor_id = $doctor['id'];
}

// Only proceed with appointments if we have a valid doctor_id
if (isset($doctor_id)) {
    // Handle appointment status updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id']) && isset($_POST['status'])) {
        $appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_SANITIZE_NUMBER_INT);
        $status = htmlspecialchars(trim($_POST['status']));
        
        if (in_array($status, ['accepted', 'rejected'])) {
            try {
                $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
                if ($stmt->execute([$status, $appointment_id, $doctor_id])) {
                    $success_message = 'Appointment status updated successfully.';
                } else {
                    $error_message = 'Failed to update appointment status.';
                }
            } catch (PDOException $e) {
                error_log('Error updating appointment: ' . $e->getMessage());
                $error_message = 'Failed to update appointment status.';
            }
        }
    }

    // Fetch today's appointments
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT a.*, u.full_name as patient_name, u.email as patient_email
                          FROM appointments a 
                          JOIN users u ON a.user_id = u.id 
                          WHERE a.doctor_id = ? AND a.appointment_date = ? 
                          ORDER BY a.appointment_time");
    $stmt->execute([$doctor_id, $today]);
    $today_appointments = $stmt->fetchAll();

    // Fetch upcoming appointments
    $stmt = $pdo->prepare("SELECT a.*, u.full_name as patient_name, u.email as patient_email
                          FROM appointments a 
                          JOIN users u ON a.user_id = u.id 
                          WHERE a.doctor_id = ? AND a.appointment_date > ? 
                          ORDER BY a.appointment_date, a.appointment_time 
                          LIMIT 5");
    $stmt->execute([$doctor_id, $today]);
    $upcoming_appointments = $stmt->fetchAll();
}

// Fetch unread messages count (this doesn't depend on doctor_id)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_messages = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
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
            padding: 20px;
        }

        .message-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #003366, #004080);
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0, 51, 102, 0.2);
        }

        .message-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 51, 102, 0.3);
        }

        .message-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            font-weight: bold;
        }
        .container {
            max-width: 1200px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 25px;
            color: white;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, border-color 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .stat-card h5 {
            font-size: 18px;
            margin-bottom: 15px;
            opacity: 0.9;
            letter-spacing: 0.5px;
        }

        .stat-card h2 {
            font-size: 38px;
            margin: 0;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
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
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 30px;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s;
        }

        .table th {
            font-weight: 600;
            color: #003366;
            background-color: rgba(0, 0, 0, 0.02);
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        }

        .table tr:hover td {
            background-color: rgba(0, 123, 255, 0.03);
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, #003366, #004080);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-size: 14px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid transparent;
        }

        .alert-success {
            background: linear-gradient(to right, #d4edda, #c3e6cb);
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-danger {
            background: linear-gradient(to right, #f8d7da, #f5c6cb);
            color: #721c24;
            border-color: #f5c6cb;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pending { 
            background: linear-gradient(135deg, #ffc107, #e6a800);
        }
        .badge-accepted { 
            background: linear-gradient(135deg, #28a745, #218838);
        }
        .badge-rejected { 
            background: linear-gradient(135deg, #dc3545, #c82333);
        }

        .text-muted {
            color: #6c757d;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .welcome-header {
                font-size: 2em;
            }

            .card-header {
                padding: 20px;
            }

            .card-body {
                padding: 20px;
            }

            .table th,
            .table td {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="welcome-header">Welcome, Dr. <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h5>Today's Appointments</h5>
                <h2><?php echo count($today_appointments); ?></h2>
            </div>
            <div class="stat-card">
                <h5>Upcoming Appointments</h5>
                <h2><?php echo count($upcoming_appointments); ?></h2>
            </div>
            <div class="stat-card">
                <h5>Unread Messages</h5>
                <h2><?php echo $unread_messages; ?></h2>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Today's Appointments</div>
            <div class="card-body">
                <?php if (empty($today_appointments)): ?>
                    <p class="text-muted">No appointments scheduled for today.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($appointment['patient_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($appointment['patient_email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $appointment['status']; ?>">
                                                <?php echo ucfirst(htmlspecialchars($appointment['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($appointment['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <button type="submit" name="status" value="accepted" class="btn btn-success">Accept</button>
                                                    <button type="submit" name="status" value="rejected" class="btn btn-danger">Reject</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Upcoming Appointments</div>
            <div class="card-body">
                <?php if (empty($upcoming_appointments)): ?>
                    <p class="text-muted">No upcoming appointments.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($appointment['patient_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($appointment['patient_email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $appointment['status']; ?>">
                                                <?php echo ucfirst(htmlspecialchars($appointment['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php require_once '../includes/footer.php'; ?>
    <body>
        <a href="/hospital/doctor/messages.php" class="message-btn">
            View Messages
            <?php if ($unread_messages > 0): ?>
                <span class="message-badge"><?php echo $unread_messages; ?></span>
            <?php endif; ?>
        </a>
        // ... rest of the body content ...