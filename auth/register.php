<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = sanitize_input($_POST['email']);
    $full_name = sanitize_input($_POST['full_name']);

    if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!validate_email($email)) {
        $error = 'Invalid email format';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $error = 'Username or email already exists';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'user')");
            if ($stmt->execute([$username, $hashed_password, $email, $full_name])) {
                header('Location: /auth/login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up Page</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background: linear-gradient(to bottom, #007bff, #0056b3);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .container {
      display: flex;
      width: 880px;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
      background: #fff;
    }

    .left {
      background: linear-gradient(135deg, #0056b3, #007bff);
      color: white;
      padding: 40px;
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
    }

    .left h1 {
      font-size: 32px;
      margin-bottom: 10px;
    }

    .left p:first-of-type {
      font-size: 15px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .left p:last-of-type {
      margin-top: 20px;
      font-size: 13px;
      line-height: 1.6;
      max-width: 300px;
    }

    .circle {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
    }

    .circle1 {
      width: 160px;
      height: 160px;
      bottom: -50px;
      left: -40px;
    }

    .circle2 {
      width: 130px;
      height: 130px;
      bottom: 40px;
      left: 90px;
    }

    .circle3 {
      width: 100px;
      height: 100px;
      top: 20px;
      right: -50px;
    }

    .right {
      flex: 1;
      padding: 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .right h2 {
      margin-bottom: 10px;
      font-size: 26px;
      color: #003366;
    }

    .right p {
      font-size: 13px;
      color: #666;
      margin-bottom: 25px;
    }

    .form-group {
      margin-bottom: 18px;
    }

    .form-group input {
      width: 100%;
      padding: 12px 16px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 14px;
      background-color: #f9f9f9;
      transition: border 0.3s;
    }

    .form-group input:focus {
      outline: none;
      border-color: #007bff;
    }

    .btn {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 10px;
      background: #003366;
      color: white;
      font-weight: bold;
      font-size: 14px;
      cursor: pointer;
      transition: background 0.3s;
      margin-bottom: 12px;
    }

    .btn:hover {
      background: #002244;
    }

    .login-text {
      margin-top: 25px;
      text-align: center;
      font-size: 13px;
    }

    .login-text a {
      color: #007bff;
      text-decoration: none;
      font-weight: 600;
    }

    .alert {
      color: red;
      font-size: 14px;
      margin-bottom: 15px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="left">
      <h1>WELCOME</h1>
      <p>CREATE YOUR ACCOUNT</p>
      <p>Join our healthcare community and get access to personalized medical services.</p>
      <div class="circle circle1"></div>
      <div class="circle circle2"></div>
      <div class="circle circle3"></div>
    </div>
    <div class="right">
      <h2>Sign up</h2>
      <p>Please fill in your information to create an account</p>
      <?php if ($error): ?>
        <div class="alert"><?php echo $error; ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <input type="text" name="username" placeholder="👤  Username" required />
        </div>
        <div class="form-group">
          <input type="text" name="full_name" placeholder="📝  Full Name" required />
        </div>
        <div class="form-group">
          <input type="email" name="email" placeholder="📧  Email Address" required />
        </div>
        <div class="form-group">
          <input type="password" name="password" placeholder="🔐  Password" required />
        </div>
        <div class="form-group">
          <input type="password" name="confirm_password" placeholder="🔐  Confirm Password" required />
        </div>
        <button type="submit" class="btn">Create Account</button>
      </form>
      <div class="login-text">
        Already have an account? <a href="/hospital/auth/login.php">Sign In</a>
      </div>
    </div>
  </div>
</body>
</html>
<?php require_once '../includes/footer.php'; ?>