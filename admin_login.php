<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['id']) && isset($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config/database.php';

// Handle logout success message
$logout_message = '';
if (isset($_SESSION['logout_success'])) {
    $logout_message = $_SESSION['logout_success'];
    unset($_SESSION['logout_success']); // Remove the message after displaying it
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Invalid username or password";
            }
        } catch (Exception $e) {
            $error = "Database connection error. Please try again.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ISU Roxas Library</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .header h1 {
            color: #226c2a;
            margin: 0;
            font-size: 2rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }
        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background: #226c2a;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #1a5520;
        }
        .error {
            color: #dc3545;
            background: #ffe6e6;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .success {
            color: #28a745;
            background: #e6ffe6;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .lb {
            text-align: center;
            margin-top: 1rem;
        }
        .forgot-password a {
            color: #226c2a;
            text-decoration: none;
        }
        .forgot-password a:hover {
            text-decoration: underline;
        }
        .logo img{
            width: 100px;
            height: 100px;
            
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <div class="logo">
                <img src="assets/img/images-removebg-preview.png" alt="">
            </div>
            <h1>Librarian Login</h1>
        </div>
        
        <?php if (!empty($logout_message)): ?>
            <div class="success">
                <?php echo htmlspecialchars($logout_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group lb">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
                
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>