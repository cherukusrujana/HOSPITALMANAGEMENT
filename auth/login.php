<?php
session_start();
require_once '../config/db.php';

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'All fields are required';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                switch ($user['role']) {
                    case 'admin':
                        header('Location: /hospital/admin/dashboard.php');
                        break;
                    case 'doctor':
                        header('Location: /hospital/doctor/dashboard.php');
                        break;
                    default:
                        header('Location: /hospital/user/dashboard.php');
                }
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'System error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="google-signin-client_id" content="824405434124-gktpnelj6p3mlkmr147ng76v309bkuq7.apps.googleusercontent.com">
    <title>Sign In Page</title>
    <script src="https://accounts.google.com/gsi/client" async></script>
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

        .form-actions {
          display: flex;
          justify-content: space-between;
          font-size: 12px;
          margin-bottom: 20px;
        }

        .form-actions a {
          text-decoration: none;
          color: #007bff;
        }

        .form-actions input[type="checkbox"] {
          margin-right: 6px;
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

        .btn-secondary {
          background: transparent;
          border: 1px solid #003366;
          color: #003366;
        }

        .btn-secondary:hover {
          background: #f0f0f0;
        }

        .signup-text {
          margin-top: 25px;
          text-align: center;
          font-size: 13px;
        }

        .signup-text a {
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
      <p>YOUR HEADLINE NAME</p>
      <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
      <div class="circle circle1"></div>
      <div class="circle circle2"></div>
      <div class="circle circle3"></div>
    </div>
    <div class="right">
      <h2>Sign in</h2>
      <p>Please enter your credentials to continue</p>
      <?php if ($error): ?>
        <div class="alert"><?php echo $error; ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <input type="text" name="username" placeholder="👤  User Name" required />
        </div>
        <div class="form-group">
          <input type="password" name="password" placeholder="🔐  Password" required />
        </div>
        <div class="form-actions">
          <label><input type="checkbox" /> Remember me</label>
          <a href="#">Forgot Password?</a>
        </div>
        <style>
            .or-divider {
                text-align: center;
                margin: 20px 0;
                position: relative;
                height: 1px;
                background: #e0e0e0;
            }

            .or-divider span {
                background: #fff;
                padding: 0 15px;
                color: #666;
                font-size: 14px;
                position: absolute;
                left: 50%;
                transform: translate(-50%, -50%);
                top: 50%;
            }

            .g_id_signin {
                margin-top: 15px;
                display: flex;
                justify-content: center;
            }

            .g_id_signin > * {
                width: 100% !important;
            }

            #g_id_onload {
                margin: 0 auto;
            }
        </style>

        <!-- Update the divider and button section in your form: -->
        <button type="submit" class="btn">Sign in</button>
        <div class="or-divider">
            <span>OR</span>
        </div>
        <div id="g_id_onload"
             data-client_id="824405434124-gktpnelj6p3mlkmr147ng76v309bkuq7.apps.googleusercontent.com"
             data-context="signin"
             data-ux_mode="popup"
             data-callback="handleCredentialResponse"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin"
             data-type="standard"
             data-shape="rectangular"
             data-theme="outline"
             data-text="signin_with"
             data-size="large"
             data-logo_alignment="center"
             data-width="100%">
        </div>
        
        <!-- Add this script at the end of body -->
        <script>
        function handleCredentialResponse(response) {
            // Get the ID token from the credential response
            const token = response.credential;
            
            // Decode the JWT token to get user info
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
        
            const { name, email } = JSON.parse(jsonPayload);
        
            // Send to backend
            fetch('/hospital/auth/google_auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                    email: email,
                    name: name
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert('Login failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Login failed. Please try again.');
            });
        }
        </script>
       
      </form>
      <div class="signup-text">
        Don't have an account? <a href="/hospital/auth/register.php">Sign Up</a>
      </div>
    </div>
  </div>
</body>
</html>
<?php require_once '../includes/footer.php'; ?>
