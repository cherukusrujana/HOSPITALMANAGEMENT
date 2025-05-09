<?php
require_once '../includes/header.php';

if (!is_logged_in() || $_SESSION['role'] !== 'user') {
    header('Location: /hospital/auth/login.php');
    exit();
}

// Fetch user data first
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Then fetch user's appointments
$stmt = $pdo->prepare("SELECT a.*, d.specialization, u.full_name as doctor_name 
                      FROM appointments a 
                      JOIN doctors d ON a.doctor_id = d.id 
                      JOIN users u ON d.user_id = u.id 
                      WHERE a.user_id = ? 
                      ORDER BY a.appointment_date DESC, a.appointment_time DESC");
$stmt->execute([$_SESSION['user_id']]);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome-header {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-size: 32px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
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
            font-size: 18px;
            font-weight: 600;
        }

        .card-body {
            padding: 25px;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            text-align: center;
            margin-bottom: 15px;
        }

        .btn-primary {
            background: #003366;
            color: white;
        }

        .btn-primary:hover {
            background: #002244;
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            font-weight: 600;
            color: #003366;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .bg-success { background: #28a745; }
        .bg-warning { background: #ffc107; }
        .bg-danger { background: #dc3545; }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="welcome-header">Welcome to Hospital Management System,<br><?php echo htmlspecialchars($user['full_name']); ?>!</h1>
        <div class="dashboard-grid">
            <div class="card">
                <div class="card-header">Quick Actions</div>
                <div class="card-body">
                    <a href="/hospital/user/book_appointment.php" class="btn btn-primary">Book New Appointment</a>
                    <a href="/hospital/user/send_message.php" class="btn btn-secondary">Send Message</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Your Appointments</div>
                <div class="card-body">
                    <?php if (empty($appointments)): ?>
                        <p style="color: #666; text-align: center;">No appointments found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Doctor</th>
                                        <th>Specialization</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['specialization']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $appointment['status'] === 'accepted' ? 'success' : 
                                                        ($appointment['status'] === 'rejected' ? 'danger' : 'warning');
                                                ?>">
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
    </div>
</body>
</html>
<?php require_once '../includes/footer.php'; ?>