<?php
require_once '../includes/header.php';

if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id']) && isset($_POST['status'])) {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->execute([$status, $appointment_id]);
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all appointments with user and doctor details
$stmt = $pdo->prepare("SELECT a.*, 
                        u1.full_name as patient_name,
                        u2.full_name as doctor_name,
                        d.specialization
                      FROM appointments a
                      JOIN users u1 ON a.user_id = u1.id
                      JOIN doctors d ON a.doctor_id = d.id
                      JOIN users u2 ON d.user_id = u2.id
                      ORDER BY a.appointment_date DESC, a.appointment_time DESC");
$stmt->execute();
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointments</title>
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
            max-width: 1400px;
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

        .bg-warning {
            background: linear-gradient(135deg, #ffc107, #e6a800);
        }

        .bg-success {
            background: linear-gradient(135deg, #28a745, #218838);
        }

        .bg-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            margin-right: 5px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        .text-muted {
            color: #6c757d;
            font-style: italic;
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
                overflow-x: auto;
            }

            .table th,
            .table td {
                padding: 12px;
                font-size: 14px;
            }

            .btn {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="welcome-header">Manage Appointments</h2>

        <div class="card">
            <div class="card-header">All Appointments</div>
            <div class="card-body">
                <?php if (empty($appointments)): ?>
                    <p class="text-muted">No appointments found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Specialization</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['specialization']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $appointment['status'] === 'accepted' ? 'success' : 
                                                    ($appointment['status'] === 'rejected' ? 'danger' : 'warning');
                                            ?>">
                                                <?php echo ucfirst(htmlspecialchars($appointment['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($appointment['status'] === 'pending'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <input type="hidden" name="status" value="accepted">
                                                    <button type="submit" class="btn btn-success btn-sm">Accept</button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
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
    </div>
</body>
</html>
<?php require_once '../includes/footer.php'; ?>