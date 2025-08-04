<?php
session_start();
require_once 'config/database.php';
require_once 'gmail_mailer.php';

// Load email configuration
$emailConfig = require_once 'email_config.php';

// Automatically send reset email when page is accessed
$db = new Database();
$conn = $db->getConnection();

// Get the admin user (assuming there's only one admin or get the first one)
$stmt = $conn->prepare("SELECT * FROM admin_users LIMIT 1");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Check if user has an email address
    $recipient_email = !empty($user['email']) ? $user['email'] : 'roysenjinnery@gmail.com';
    
    // Generate reset token
    $reset_token = bin2hex(random_bytes(32));
    $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Update user with reset token
    $stmt = $conn->prepare("UPDATE admin_users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
    $stmt->execute([$reset_token, $reset_expires, $user['id']]);
    
    // Create reset link
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $reset_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/isurl/reset_password.php?token=" . $reset_token;
    
    // Send email using Gmail SMTP
    $result = sendResetEmail(
        $reset_link, 
        $emailConfig['gmail_username'], 
        $emailConfig['gmail_password'], 
        $recipient_email,
        $emailConfig // Pass full config for additional settings
    );
    
    if ($result === true) {
        $success = true;
        $sent_to_email = $recipient_email;
    } else {
        $error = "Failed to send email: " . $result;
    }
} else {
    $error = "No admin user found in the system.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Sent - ISURL</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            max-width: 500px;
            text-align: center;
        }
        .header {
            margin-bottom: 2rem;
        }
        .header h1 {
            color: #226c2a;
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }
        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        .success {
            color: #155724;
            background: linear-gradient(135deg, #d1eddb 0%, #c3e6cb 100%);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 2px solid #28a745;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
        }
        .success h3 {
            margin-top: 0;
            font-size: 1.5rem;
        }
        .email-address {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            font-weight: bold;
            color: #226c2a;
            margin: 1rem 0;
            border: 2px solid #e9ecef;
        }
        .error {
            color: #721c24;
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 2px solid #dc3545;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
        }
        .instructions {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        .instructions h3 {
            color: #226c2a;
            margin-top: 0;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .instructions ol {
            margin: 1rem 0;
            padding-left: 25px;
        }
        .instructions li {
            margin-bottom: 1rem;
            font-size: 1rem;
            line-height: 1.5;
        }
        .instructions strong {
            color: #226c2a;
        }
        .back-link {
            margin-top: 2rem;
        }
        .back-link a {
            color: white;
            background: linear-gradient(135deg, #226c2a 0%, #2d8f3a 100%);
            text-decoration: none;
            font-weight: bold;
            padding: 15px 30px;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(34, 108, 42, 0.3);
            display: inline-block;
        }
        .back-link a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 108, 42, 0.4);
        }
        .countdown {
            font-size: 1rem;
            color: #666;
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        .countdown span {
            font-weight: bold;
            color: #226c2a;
        }
        .setup-note {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: left;
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.2);
        }
        .setup-note h4 {
            color: #856404;
            margin-top: 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .setup-note code {
            background: #f5f5f5;
            padding: 5px 8px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            border: 1px solid #ddd;
        }
        .setup-note ol {
            margin: 1rem 0;
            padding-left: 25px;
        }
        .setup-note li {
            margin-bottom: 0.8rem;
        }
        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
        }
        .warning-box {
            background: #ffeaa7;
            border: 2px solid #fdcb6e;
            padding: 1rem;
            border-radius: 10px;
            margin: 1rem 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Password Reset</h1>
        </div>
        
        <?php if (isset($success) && $success): ?>

            <div class="success">
                <h3>Reset Email Sent Successfully!</h3>
                <p>A password reset link has been automatically sent to:</p>
                <div class="email-address">
                    📧 <?php echo htmlspecialchars($sent_to_email); ?>
                </div>
            </div>
            
            
            <div class="countdown">
                <p>🔄 Redirecting to login page in <span id="countdown">15</span> seconds...</p>
                <p><small>You can click the button below to go back immediately.</small></p>
            </div>
            
        <?php else: ?>
            <div class="error">
                <h3>❌ Email Sending Failed</h3>
                <p><?php echo htmlspecialchars($error ?? 'Unknown error occurred'); ?></p>
            </div>
            
            <div class="setup-note">
                <h4>⚙️ Gmail App Password Setup Required</h4>
                <p>To fix this error, you need to set up Gmail App Password authentication:</p>
                <ol>
                    <li>Go to <strong>Google Account Settings</strong>: <code>https://myaccount.google.com/</code></li>
                    <li>Click <strong>"Security"</strong> in the left sidebar</li>
                    <li>Enable <strong>"2-Step Verification"</strong> if not already enabled</li>
                    <li>After 2-Step is enabled, go back to <strong>Security</strong></li>
                    <li>Click <strong>"App passwords"</strong> (you'll need to sign in again)</li>
                    <li>Select <strong>"Mail"</strong> as the app and <strong>"Other"</strong> as the device</li>
                    <li>Enter <strong>"ISURL Admin System"</strong> as the device name</li>
                    <li>Google will generate a 16-character password</li>
                    <li>Update <code>email_config.php</code> with this App Password</li>
                </ol>
                
                <div class="code-block">
'gmail_password' => 'abcd efgh ijkl mnop'  // Your 16-char App Password
                </div>
                
                <div class="warning-box">
                    <strong>🔒 Security Note:</strong> Never share your App Password and use the generated 16-character code, not your regular Gmail password.
                </div>
            </div>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="admin_login.php">← Back to Login</a>
        </div>
    </div>
    
    <script>
        <?php if (isset($success) && $success): ?>
        // Auto-redirect countdown
        let countdown = 15;
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
    </script>
</body>
</html>