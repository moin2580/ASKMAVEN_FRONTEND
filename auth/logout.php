<?php
/**
 * Logout script for Hybrid Chatbot System
 */

require_once __DIR__ . '/../includes/database.php';

if (isLoggedIn()) {
    // Log activity before destroying session
    logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    
    // Destroy session
    session_destroy();
    
    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}

// Redirect to login page
redirectTo('/AskMaven/auth/login.php');
?>
