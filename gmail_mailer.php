<?php
// Simple Gmail SMTP mailer without PHPMailer dependency
// This uses PHP's built-in socket functions to connect to Gmail SMTP

class SimpleGmailMailer {
    private $smtp_host = 'smtp.gmail.com';
    private $smtp_port = 587;
    private $username;
    private $password;
    private $from_name;
    
    public function __construct($username, $password, $from_name = 'ISURL Admin') {
        $this->username = $username;
        $this->password = $password;
        $this->from_name = $from_name;
    }
    
    public function sendEmail($to, $subject, $body) {
        // Create socket connection
        $socket = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 10);
        
        if (!$socket) {
            return "Connection failed: $errstr ($errno)";
        }
        
        // Read initial response
        $response = fgets($socket, 515);
        
        // Send HELO
        fputs($socket, "HELO localhost\r\n");
        $response = fgets($socket, 515);
        
        // Start TLS
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 515);
        
        // Enable crypto
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        
        // Send HELO again after TLS
        fputs($socket, "HELO localhost\r\n");
        $response = fgets($socket, 515);
        
        // Authenticate
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, base64_encode($this->username) . "\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, base64_encode($this->password) . "\r\n");
        $response = fgets($socket, 515);
        
        if (substr($response, 0, 3) != '235') {
            fclose($socket);
            return "Authentication failed";
        }
        
        // Send mail
        fputs($socket, "MAIL FROM: <{$this->username}>\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, "RCPT TO: <$to>\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 515);
        
        // Email headers and body
        $headers = "From: {$this->from_name} <{$this->username}>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "\r\n";
        
        fputs($socket, $headers . $body . "\r\n.\r\n");
        $response = fgets($socket, 515);
        
        // Quit
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        return substr($response, 0, 3) == '250' ? true : "Send failed: $response";
    }
}

// Easy to use functions
function sendResetEmail($reset_link, $gmail_username, $gmail_password, $to_email = 'roysenjinnery@gmail.com') {
    $mailer = new SimpleGmailMailer($gmail_username, $gmail_password);
    
    $subject = 'Password Reset Request - ISURL Admin';
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #226c2a; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 30px; background: #f9f9f9; border-radius: 0 0 10px 10px; }
            .verify-image {
                background: #226c2a;
                color: white;
                padding: 15px 30px;
                border-radius: 8px;
                display: inline-block;
                margin: 20px 0;
                text-align: center;
                cursor: pointer;
                font-weight: bold;
                font-size: 16px;
                text-decoration: none;
                border: 2px solid #226c2a;
            }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🔐 ISURL Admin Password Reset</h1>
            </div>
            <div class="content">
                <h2>Password Reset Request</h2>
                <p>Hello Administrator,</p>
                <p>A password reset has been requested for your ISURL admin account.</p>
                <p><strong>Click the VERIFY button below to create your new password:</strong></p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $reset_link . '" class="verify-image" style="color: white; text-decoration: none;">
                        🔐 VERIFY
                    </a>
                </div>
                
                <p>This secure link will expire in <strong>1 hour</strong> for your security.</p>
                <p>If you didn\'t request this reset, please ignore this email and your password will remain unchanged.</p>
                
                <div class="footer">
                    <hr style="border: 1px solid #ddd; margin: 20px 0;">
                    <p>ISURL Admin System - Automated Message</p>
                    <p>Time: ' . date('Y-m-d H:i:s') . '</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $mailer->sendEmail($to_email, $subject, $body);
}

function sendConfirmationEmail($gmail_username, $gmail_password, $to_email = 'roysenjinnery@gmail.com') {
    $mailer = new SimpleGmailMailer($gmail_username, $gmail_password);
    
    $subject = 'Password Changed Successfully - ISURL Admin';
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #226c2a; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 20px; background: #f9f9f9; border-radius: 0 0 10px 10px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>✅ ISURL - Password Changed</h1>
            </div>
            <div class="content">
                <h2>Password Successfully Updated</h2>
                <p>Hello Administrator,</p>
                <p>Your ISURL admin password has been successfully changed.</p>
                <p>If you didn\'t make this change, please contact the administrator immediately.</p>
                <p><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
                <hr style="border: 1px solid #ddd; margin: 20px 0;">
                <p><small>ISURL Admin System - Automated Message</small></p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $mailer->sendEmail($to_email, $subject, $body);
}
?>