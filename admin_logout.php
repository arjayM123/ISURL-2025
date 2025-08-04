<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['id']) && !isset($_SESSION['username'])) {
    // User is already logged out, redirect to login
    header('Location: admin_login.php');
    exit;
}

// Store username for potential logging (optional)
$logged_out_user = $_SESSION['username'] ?? 'Unknown';

// Unset all session variables
$_SESSION = array();

// Delete the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Optional: Log the logout action (uncomment if you have a logging system)
/*
try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("INSERT INTO admin_logs (username, action, timestamp) VALUES (?, 'logout', NOW())");
    $stmt->execute([$logged_out_user]);
} catch (Exception $e) {
    // Silently handle logging errors - don't prevent logout
    error_log("Logout logging failed: " . $e->getMessage());
}
*/

// Set a success message in a temporary session (will be destroyed after display)
session_start();
$_SESSION['logout_success'] = 'You have been successfully logged out.';

// Redirect to login page
header('Location: admin_login.php');
exit;
?>