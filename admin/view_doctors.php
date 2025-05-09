<?php
require_once '../includes/header.php';

if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    header('Location: /hospital/auth/login.php');
    exit();
}

$success_message = $error_message = '';

// Handle doctor deletion
if (isset($_POST['delete_doctor']) && isset($_POST['doctor_id'])) {
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get user_id first
        $stmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
        $stmt->execute([$doctor_id]);
        $doctor = $stmt->fetch();
        
        if ($doctor) {
            // Delete from doctors table
            $stmt = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
            $stmt->execute([$doctor_id]);
            
            // Delete from users table
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$doctor['user_id']]);
            
            $pdo->commit();
            $success_message = 'Doctor deleted successfully!';
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = 'Failed to delete doctor. Please try again.';
    }
}

// Fetch all doctors with their details
$stmt = $pdo->query("SELECT d.*, u.full_name, u.email 
                     FROM doctors d 
                     JOIN users u ON d.user_id = u.id 
                     ORDER BY u.full_name");
$doctors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Doctors</title>
    <style>
        body {
            background: linear-gradient(to bottom, #007bff, #0056b3);
            min-height: 100vh;
            padding: 40px 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
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
        }

        .btn-primary {
            background: #003366;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: #002244;
        }

        .card-body {
            padding: 30px;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            color: #003366;
            font-weight: 600;
        }

        .table td {
            vertical-align: middle;
        }

        .btn-sm {
            border-radius: 8px;
            font-weight: 500;
            padding: 5px 15px;
            margin: 0 3px;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 15px 20px;
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

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 123, 255, 0.05);
        }

        .table-striped tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Manage Doctors</h5>
                <a href="/hospital/admin/add_doctor.php" class="btn btn-primary">Add New Doctor</a>
            </div>
            <div class="card-body">
                <?php if (empty($doctors)): ?>
                    <p class="text-muted">No doctors found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Specialization</th>
                                    <th>Qualification</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doctors as $doctor): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($doctor['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['qualification']); ?></td>
                                        <td>
                                            <a href="/hospital/admin/edit_doctor.php?id=<?php echo $doctor['id']; ?>" 
                                               class="btn btn-sm btn-primary">Edit</a>
                                            <form method="POST" action="" class="d-inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete this doctor?');">
                                                <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                                <button type="submit" name="delete_doctor" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
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