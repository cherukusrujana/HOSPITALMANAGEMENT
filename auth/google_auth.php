<?php
session_start();
require_once '../config/db.php';

// Your Google OAuth 2.0 Client ID
$CLIENT_ID = "824405434124-gktpnelj6p3mlkmr147ng76v309bkuq7.apps.googleusercontent.com";

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$email = $input['email'] ?? '';
$name = $input['name'] ?? '';

if (empty($token) || empty($email) || empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Create new user
        $pdo->beginTransaction();
        try {
            $username = explode('@', $email)[0] . rand(100, 999);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $username,
                $email,
                $name,
                password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
                'user'
            ]);
            
            $user_id = $pdo->lastInsertId();
            $pdo->commit();
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';
            
            echo json_encode(['success' => true, 'redirect' => '/hospital/user/dashboard.php']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'Registration failed']);
        }
    } else {
        // Log in existing user
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        $redirect = match($user['role']) {
            'admin' => '/hospital/admin/dashboard.php',
            'doctor' => '/hospital/doctor/dashboard.php',
            default => '/hospital/user/dashboard.php'
        };
        
        echo json_encode(['success' => true, 'redirect' => $redirect]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error']);
}