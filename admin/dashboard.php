<?php
require_once '../includes/header.php';

if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    header('Location: /hospital/auth/login.php');
    exit();
}

// Get counts for dashboard
$stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'");
$pending_appointments = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM doctors");
$total_doctors = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM messages WHERE receiver_id = " . $_SESSION['user_id'] . " AND is_read = 0");
$unread_messages = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
$total_patients = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'accepted'");
$completed_appointments = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome-header {
            color: white;
            margin-bottom: 40px;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .welcome-header h1 {
            font-size: 2.5em;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .welcome-header p {
            font-size: 1.2em;
            opacity: 0.9;
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
            padding: 30px;
            color: white;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, border-color 0.3s;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .stat-card i {
            font-size: 2.5em;
            margin-bottom: 15px;
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
        }

        .card-body {
            padding: 30px;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            text-align: center;
            margin-bottom: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-light {
            background: white;
            color: #003366;
        }

        .btn-primary {
            background: linear-gradient(135deg, #003366, #004080);
            color: white;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .quick-action {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }

        .quick-action:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-5px);
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }

            .welcome-header h1 {
                font-size: 2em;
            }

            .stat-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-header">
            <h1>Welcome to Admin Dashboard</h1>
            <p>Manage your hospital system efficiently</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h5>Total Patients</h5>
                <h2><?php echo $total_patients; ?></h2>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h5>Completed Appointments</h5>
                <h2><?php echo $completed_appointments; ?></h2>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <h5>Pending Appointments</h5>
                <h2><?php echo $pending_appointments; ?></h2>
                <a href="/hospital/admin/view_appointments.php" class="btn btn-light">View All</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-user-md"></i>
                <h5>Total Doctors</h5>
                <h2><?php echo $total_doctors; ?></h2>
                <a href="/hospital/admin/view_doctors.php" class="btn btn-light">Manage Doctors</a>
            </div>
            <div class="stat-card">
                <i class="fas fa-envelope"></i>
                <h5>Unread Messages</h5>
                <h2><?php echo $unread_messages; ?></h2>
                <a href="/hospital/admin/view_messages.php" class="btn btn-light">View Messages</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Quick Actions</div>
            <div class="card-body">
                <div class="actions-grid">
                    <a href="/hospital/admin/add_doctor.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add New Doctor
                    </a>
                    <a href="/hospital/admin/manage_schedule.php" class="btn btn-primary">
                        <i class="fas fa-calendar-alt"></i> Manage Schedules
                    </a>
                    <a href="/hospital/admin/view_appointments.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View All Appointments
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php require_once '../includes/footer.php'; ?>