
<?php
require_once '../includes/header.php';

if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    header('Location: /hospital/auth/login.php');
    exit();
}

// Handle schedule updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_SANITIZE_NUMBER_INT);
    $working_hours = htmlspecialchars(trim($_POST['working_hours'] ?? ''));
    $available_days = $_POST['available_days'] ?? [];
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE doctors SET working_hours = ?, available_days = ? WHERE id = ?");
        $stmt->execute([$working_hours, implode(',', $available_days), $doctor_id]);
        $pdo->commit();
        $success_message = 'Schedule updated successfully!';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = 'Failed to update schedule.';
    }
}

// Fetch all doctors
// Fetch all doctors
$stmt = $pdo->prepare("SELECT d.id, d.specialization, IFNULL(d.working_hours, '') as working_hours, 
                             IFNULL(d.available_days, '') as available_days, u.full_name 
                      FROM doctors d 
                      JOIN users u ON d.user_id = u.id 
                      ORDER BY u.full_name");
$stmt->execute();
$doctors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctor Schedules</title>
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

        .doctor-schedule {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .doctor-name {
            color: #003366;
            font-size: 20px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .doctor-specialization {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
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

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .checkbox-label:hover {
            background: #e9ecef;
        }

        .checkbox-label input[type="checkbox"] {
            margin-right: 8px;
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

            .doctor-schedule {
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
        <h2 class="welcome-header">Manage Doctor Schedules</h2>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">Doctor Schedules</div>
            <div class="card-body">
                <?php foreach ($doctors as $doctor): ?>
                    <div class="doctor-schedule">
                        <h3 class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                        <p class="doctor-specialization"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                        
                        <form method="POST">
                            <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                            
                            <div class="form-group">
                                <label class="form-label">Working Hours</label>
                                <input type="text" name="working_hours" class="form-control" 
                                       value="<?php echo htmlspecialchars($doctor['working_hours'] ?? ''); ?>" 
                                       placeholder="e.g., 9:00 AM - 5:00 PM" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Available Days</label>
                                <div class="checkbox-group">
                                    <?php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    $available_days = explode(',', $doctor['available_days'] ?? '');
                                    foreach ($days as $day): ?>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="available_days[]" value="<?php echo $day; ?>" 
                                                   <?php echo in_array($day, $available_days) ? 'checked' : ''; ?>>
                                            <?php echo $day; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Schedule</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php require_once '../includes/footer.php'; ?>