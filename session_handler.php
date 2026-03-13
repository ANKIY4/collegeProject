<?php
// Session Handler for Budget Manager
require_once 'config.php';

// Start session with secure settings
function startSecureSession() {
    // Only configure session if it hasn't been started yet
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters BEFORE starting session
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_lifetime', 0); // Session cookie
        
        // For production, enable these (requires HTTPS)
        // ini_set('session.cookie_secure', 1);
        
        session_name('BUDGET_SESSION');
        session_start();
    }
    
    // Regenerate session ID periodically to prevent fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Check if user is logged in
function isLoggedIn() {
    startSecureSession();
    
    if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive > SESSION_TIMEOUT) {
                destroySession();
                return false;
            }
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    // Check remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        return validateRememberToken($_COOKIE['remember_token']);
    }
    
    return false;
}

// Get current user ID
function getCurrentUserId() {
    startSecureSession();
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Get current user data
function getCurrentUser() {
    startSecureSession();
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'] ?? '',
        'email' => $_SESSION['email'] ?? ''
    ];
}

// Create user session
function createUserSession($user) {
    startSecureSession();
    
    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['last_activity'] = time();
    $_SESSION['created'] = time();
    
    // Update last login time
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $stmt->bind_param("i", $user['user_id']);
    $stmt->execute();
    $conn->close();
}

// Destroy session (logout)
function destroySession() {
    startSecureSession();
    
    // Remove remember token if exists
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        // Remove from database
        $conn = getDBConnection();
        $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $conn->close();
        
        // Delete cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    
    // Destroy session
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

// Create remember me token
function createRememberToken($user_id) {
    $token = bin2hex(random_bytes(32));
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE user_id = ?");
    $stmt->bind_param("si", $token, $user_id);
    $stmt->execute();
    $conn->close();
    
    // Set cookie for 30 days
    setcookie('remember_token', $token, time() + REMEMBER_ME_DURATION, '/', '', false, true);
    
    return $token;
}

// Validate remember token
function validateRememberToken($token) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT user_id, full_name, email FROM users WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        createUserSession($row);
        $conn->close();
        return true;
    }
    
    $conn->close();
    return false;
}

// Generate CSRF token
function generateCSRFToken() {
    startSecureSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCSRFToken($token) {
    startSecureSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Require authentication (use in protected pages)
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: auth.html');
        exit();
    }
}

// Return JSON response for API calls
function apiRequireAuth() {
    if (!isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required',
            'redirect' => 'auth.html'
        ]);
        exit();
    }
}

