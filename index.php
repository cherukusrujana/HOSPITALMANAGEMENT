<?php require_once 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
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
            display: flex;
            align-items: center;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome-section {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }

        .welcome-section h1 {
            font-size: 42px;
            font-weight: 600;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .welcome-section p {
            font-size: 18px;
            opacity: 0.9;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-body {
            padding: 30px;
            text-align: center;
        }

        .card-title {
            color: #003366;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .card-text {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
            margin: 5px;
        }

        .btn-primary {
            background: #003366;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #002244;
        }

        .btn-outline-primary {
            background: transparent;
            color: #003366;
            border: 2px solid #003366;
        }

        .btn-outline-primary:hover {
            background: #003366;
            color: white;
        }

        @media (max-width: 768px) {
            .welcome-section h1 {
                font-size: 32px;
            }

            .welcome-section p {
                font-size: 16px;
            }

            .cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-section">
            <h1>Welcome to Hospital Management System</h1>
            <p>Efficient healthcare management and appointment booking system</p>
        </div>

        <div class="cards-grid">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Patients</h3>
                    <p class="card-text">Book appointments with our qualified doctors and manage your healthcare needs.</p>
                    <?php if (!is_logged_in()): ?>
                        <a href="/hospital/auth/register.php" class="btn btn-primary">Register Now</a>
                        <a href="/hospital/auth/login.php" class="btn btn-outline-primary">Login</a>
                    <?php else: ?>
                        <a href="/hospital/user/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Doctors</h3>
                    <p class="card-text">Access your schedule, manage appointments, and communicate with patients.</p>
                    <?php if (!is_logged_in()): ?>
                        <a href="/hospital/auth/login.php" class="btn btn-primary">Doctor Login</a>
                    <?php else: ?>
                        <a href="/hospital/doctor/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php require_once 'includes/footer.php'; ?>