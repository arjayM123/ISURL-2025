<?php
session_start();
require_once 'config/database.php';
require_once 'gmail_mailer.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    $error = "Invalid reset token";
} else {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verify token and check expiration
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $error = "Invalid or expired reset token";
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($new_password) || empty($confirm_password)) {
                $error = "Please fill in all fields";
            } elseif (strlen($new_password) < 6) {
                $error = "Password must be at least 6 characters long";
            } elseif ($new_password !== $confirm_password) {
                $error = "Passwords do not match";
            } else {
                // Update password and clear reset token
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admin_users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
                
                if ($stmt->execute([$hashed_password, $user['id']])) {
                    $success = "Password updated successfully! You can now login with your new password.";
                    
                    // Send confirmation email
                    $emailConfig = require_once 'email_config.php';
                    $recipient_email = !empty($user['email']) ? $user['email'] : 'arjaymabini123@gmail.com';
                    sendConfirmationEmail(
                        $emailConfig['gmail_username'], 
                        $emailConfig['gmail_password'], 
                        $recipient_email
                    );
                } else {
                    $error = "Failed to update password. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ISURL</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
        }
        .header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .header h1 {
            color: #226c2a;
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }
        .header p {
            color: #666;
            margin: 0.5rem 0 0 0;
            font-size: 1.1rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        label {
            display: block;
            margin-bottom: 0.8rem;
            color: #333;
            font-weight: 600;
            font-size: 1rem;
        }
        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        input {
            width: 100%;
            padding: 1rem;
            padding-right: 3rem; /* Space for eye icon */
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        input:focus {
            outline: none;
            border-color: #226c2a;
            box-shadow: 0 0 0 3px rgba(34, 108, 42, 0.1);
        }
        .password-toggle {
            position: absolute;
            right: 15px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            color: #666;
            font-size: 1.2rem;
            transition: color 0.3s ease;
            z-index: 10;
            width: auto;
        }
        .password-toggle:hover {
            color: #226c2a;
        }
        .submit-button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #226c2a 0%, #2d8f3a 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(34, 108, 42, 0.3);
        }
        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 108, 42, 0.4);
        }
        .submit-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .error {
            color: #721c24;
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            padding: 1.2rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
            border: 2px solid #dc3545;
            font-weight: 500;
        }
        .success {
            color: #155724;
            background: linear-gradient(135deg, #d1eddb 0%, #c3e6cb 100%);
            padding: 1.2rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
            border: 2px solid #28a745;
            font-weight: 500;
        }
        .login-link {
            text-align: center;
            margin-top: 2rem;
        }
        .login-link a {
            color: white;
            background: linear-gradient(135deg, #226c2a 0%, #2d8f3a 100%);
            text-decoration: none;
            font-weight: bold;
            padding: 12px 25px;
            border-radius: 25px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(34, 108, 42, 0.3);
            display: inline-block;
        }
        .login-link a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 108, 42, 0.4);
        }
        .password-requirements {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.8rem;
            padding: 0.8rem;
            border-radius: 8px;
        }
        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background: #e1e5e9;
            border-radius: 2px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        .strength-weak { background: #dc3545; width: 25%; }
        .strength-fair { background: #ffc107; width: 50%; }
        .strength-good { background: #17a2b8; width: 75%; }
        .strength-strong { background: #28a745; width: 100%; }
        
        .countdown-redirect {
            text-align: center;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            color: #666;
        }
        .countdown-redirect span {
            font-weight: bold;
            color: #226c2a;
        }
        
        /* Eye icon styles */
        .eye-icon {
            width: 20px;
            height: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Reset Password</h1>
            <p>Create your new secure password</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
            <div class="countdown-redirect">
                🔄 Redirecting to login page in <span id="countdown">5</span> seconds...
            </div>
            <div class="login-link">
                <a href="admin_login.php">← Go to Login Now</a>
            </div>
        <?php elseif ($user ?? false): ?>
            <form method="POST" action="" id="passwordResetForm">
                <div class="form-group">
                    <label for="new_password"> New Password</label>
                    <div class="password-input-container">
                        <input type="password" id="new_password" name="new_password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)">
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                    <div class="password-requirements">
                        💡 Password must be at least 6 characters long
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strength-bar"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"> Confirm New Password</label>
                    <div class="password-input-container">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="submit-button" id="submitBtn">
                    Save Password
                </button>
            </form>
        <?php else: ?>
            <div class="error">
                 Invalid or expired reset token
            </div>
            <div class="login-link">
                <a href="forgot_password.php">← Request New Reset Link</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Auto-redirect to login after successful password reset
        <?php if ($success): ?>
        let countdown = 5;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(function() {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = 'admin_login.php';
            }
        }, 1000);
        <?php endif; ?>
        
        // Toggle password visibility function
        function togglePassword(inputId, toggleButton) {
            const input = document.getElementById(inputId);
            const eyeIcon = toggleButton.querySelector('.eye-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.textContent = '🙈'; // Hide icon
                toggleButton.title = 'Hide password';
            } else {
                input.type = 'password';
                eyeIcon.textContent = '👁️'; // Show icon
                toggleButton.title = 'Show password';
            }
        }
        
        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('strength-bar');
            let strength = 0;
            
            // Length check
            if (password.length >= 6) strength += 1;
            if (password.length >= 8) strength += 1;
            
            // Character variety checks
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Remove all strength classes
            strengthBar.className = 'password-strength-bar';
            
            // Add appropriate strength class
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-fair');
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-good');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        }
        
        // Form validation and password checking
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const submitButton = document.getElementById('submitBtn');
            
            function validatePasswords() {
                const newPass = newPassword.value;
                const confirmPass = confirmPassword.value;
                
                // Check password strength
                if (newPass) {
                    checkPasswordStrength(newPass);
                }
                
                // Reset custom validity
                newPassword.setCustomValidity('');
                confirmPassword.setCustomValidity('');
                
                let isValid = true;
                
                // Length validation
                if (newPass && newPass.length < 6) {
                    newPassword.setCustomValidity('Password must be at least 6 characters long');
                    isValid = false;
                }
                
                // Match validation
                if (newPass && confirmPass && newPass !== confirmPass) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                    isValid = false;
                }
                
                // Enable/disable submit button
                submitButton.disabled = !isValid || !newPass || !confirmPass;
                
                // Update button text based on validation
                if (isValid && newPass && confirmPass) {
                    submitButton.textContent = '✅ Update Password';
                    submitButton.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
                } else {
                    submitButton.textContent = '🔄 Update Password';
                    submitButton.style.background = 'linear-gradient(135deg, #226c2a 0%, #2d8f3a 100%)';
                }
            }
            
            // Add event listeners
            newPassword.addEventListener('input', validatePasswords);
            confirmPassword.addEventListener('input', validatePasswords);
            
            // Set initial button titles for password toggles
            document.querySelectorAll('.password-toggle').forEach(btn => {
                btn.title = 'Show password';
            });
            
            // Initial validation
            validatePasswords();
        });
    </script>
</body>
</html>