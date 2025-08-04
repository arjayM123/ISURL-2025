<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email)) {
        $error = 'Username and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Check if username or email already exists (excluding current user)
        $check_query = "SELECT id FROM admin_users WHERE (username = ? OR email = ?) AND id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([$username, $email, $user_id]);
        $check_result = $check_stmt->fetchAll();
        
        if (count($check_result) > 0) {
            $error = 'Username or email already exists.';
        } else {
            // Get current user data to verify password
            $user_query = "SELECT username, email, password FROM admin_users WHERE id = ?";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->execute([$user_id]);
            $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
            
            // If password fields are filled, validate and update password
            if (!empty($new_password) || !empty($current_password)) {
                if (empty($current_password)) {
                    $error = 'Current password is required to change password.';
                } elseif (!password_verify($current_password, $user_data['password'])) {
                    $error = 'Current password is incorrect.';
                } elseif (strlen($new_password) < 6) {
                    $error = 'New password must be at least 6 characters long.';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match.';
                } else {
                    // Update with new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE admin_users SET username = ?, email = ?, password = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    
                    if ($update_stmt->execute([$username, $email, $hashed_password, $user_id])) {
                        $message = 'Profile updated successfully!';
                        $_SESSION['username'] = $username; // Update session
                    } else {
                        $error = 'Error updating profile.';
                    }
                }
            } else {
                // Update without password change
                $update_query = "UPDATE admin_users SET username = ?, email = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                
                if ($update_stmt->execute([$username, $email, $user_id])) {
                    $message = 'Profile updated successfully!';
                    $_SESSION['username'] = $username; // Update session
                } else {
                    $error = 'Error updating profile.';
                }
            }
        }
    }
}

// Fetch current user data
$query = "SELECT username, email, created_at FROM admin_users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include 'admin_layout.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>

        
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .profile-content {
            padding: 40px;
        }
        
        .profile-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .info-item:last-child {
            margin-bottom: 0;
        }
        
        .info-icon {
            font-size: 1.5em;
            color: #216c2a;
            width: 40px;
            text-align: center;
        }
        
        .info-content {
            flex: 1;
            margin-left: 15px;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1.1em;
            color: #333;
        }
        
        .edit-btn {
            background: #216c2a;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .edit-btn:hover {
            background: #1c5a23ff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .edit-form {
            display: none;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .password-section {
            border-top: 2px solid #e1e5e9;
            padding-top: 25px;
            margin-top: 25px;
        }
        
        .password-section h3 {
            color: #555;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #216c2a;
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #155724;
            text-decoration: none;
            margin: 20px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #155724;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .profile-content {
                padding: 20px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .info-item {
                flex-direction: column;
                text-align: center;
            }
            
            .info-content {
                margin-left: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
                        <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        <div class="header">
            <h1><i class="fas fa-user-cog"></i> Profile Settings</h1>
            <p>Manage your account information and preferences</p>
        </div>
        
        <div class="profile-content">

            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="profile-info">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Username</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Member Since</div>
                        <div class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                    </div>
                </div>
                
                <button class="edit-btn" onclick="toggleEditForm()">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
            </div>
            
            <div class="edit-form" id="editForm">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="password-section">
                        <h3>
                            <i class="fas fa-lock"></i> Change Password
                        </h3>
                        <p style="color: #666; margin-bottom: 20px; font-size: 0.9em;">
                            Leave password fields empty if you don't want to change your password.
                        </p>
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" placeholder="Enter your current password">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password (min 6 characters)">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="toggleEditForm()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function toggleEditForm() {
            const form = document.getElementById('editForm');
            const isVisible = form.style.display !== 'none';
            
            if (isVisible) {
                form.style.display = 'none';
                // Reset form if canceling
                const formElement = form.querySelector('form');
                const passwordFields = ['current_password', 'new_password', 'confirm_password'];
                passwordFields.forEach(field => {
                    document.getElementById(field).value = '';
                });
            } else {
                form.style.display = 'block';
                form.scrollIntoView({ behavior: 'smooth' });
            }
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
        
        // Password validation
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const currentField = document.getElementById('current_password');
            
            if (password.length > 0) {
                currentField.setAttribute('required', 'required');
            } else {
                currentField.removeAttribute('required');
            }
        });
        
        // Confirm password validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword.length > 0 && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>