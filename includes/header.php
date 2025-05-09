<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .nav-profile {
            margin-left: 15px;
        }
        .nav-profile .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        .nav-profile .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
        }

     
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/hospital">Hospital MS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (!is_logged_in()): ?>
                        <li class="nav-item"><a class="nav-link" href="/hospital/auth/login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="/hospital/auth/register.php">Register</a></li>
                    <?php else: ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="/hospital/admin/dashboard.php">Dashboard</a></li>
                        <?php elseif ($_SESSION['role'] === 'doctor'): ?>
                            <li class="nav-item"><a class="nav-link" href="/hospital/doctor/dashboard.php">Dashboard</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="/hospital/user/dashboard.php">Dashboard</a></li>
                        <?php endif; ?>
                        <li class="nav-item nav-profile">
                            <a class="nav-link" href="/hospital/user/profile.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                                    <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                    <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                                </svg>
                                Profile
                            </a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="/hospital/auth/logout.php">Logout</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">