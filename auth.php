<?php
header('Content-Type: application/json');

require_once 'config.php';
require_once 'session_handler.php';

$conn = getDBConnection();
$action = $_GET['action'] ?? '';

// Route requests
switch ($action) {
    case 'signup':
        handleSignup($conn);
        break;
    case 'login':
        handleLogin($conn);
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check_email':
        checkEmailAvailability($conn);
        break;
    case 'check_session':
        checkSession();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();

// SIGNUP FUNCTION
function handleSignup($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate inputs
    $full_name = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $confirm_password = $data['confirm_password'] ?? '';
    
    // Validation
    $errors = [];
    
    // Full name validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    } elseif (strlen($full_name) < 3) {
        $errors[] = 'Full name must be at least 3 characters';
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $full_name)) {
        $errors[] = 'Full name can only contain letters and spaces';
    }
    
    // Email validation
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Email already exists';
        }
    }
    
    // Password validation
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match("/[a-z]/", $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    } elseif (!preg_match("/[0-9]/", $password)) {
        $errors[] = 'Password must contain at least one number';
    } elseif (!preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    // Confirm password
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        return;
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $full_name, $email, $password_hash);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Create session
        createUserSession([
            'user_id' => $user_id,
            'full_name' => $full_name,
            'email' => $email
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully!',
            'redirect' => 'dashboard.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create account']);
    }
}

// ============ LOGIN FUNCTION ============
function handleLogin($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $remember_me = $data['remember_me'] ?? false;
    
    // Validation
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        return;
    }
    
    // Get user
    $stmt = $conn->prepare("SELECT user_id, full_name, email, password_hash, failed_login_attempts, account_locked_until FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        return;
    }
    
    $user = $result->fetch_assoc();
    
    // Check if account is locked
    if ($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
        $remaining = strtotime($user['account_locked_until']) - time();
        $minutes = ceil($remaining / 60);
        echo json_encode([
            'success' => false,
            'message' => "Account locked. Try again in $minutes minutes.",
            'locked' => true,
            'remaining_seconds' => $remaining
        ]);
        return;
    }
    
    // Verify password
    if (password_verify($password, $user['password_hash'])) {
        // Reset failed attempts
        $stmt = $conn->prepare("UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        
        // Create session
        createUserSession($user);
        
        // Handle remember me
        if ($remember_me) {
            createRememberToken($user['user_id']);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful!',
            'redirect' => 'dashboard.php',
            'user' => [
                'full_name' => $user['full_name'],
                'email' => $user['email']
            ]
        ]);
    } else {
        // Increment failed attempts
        $failed_attempts = $user['failed_login_attempts'] + 1;
        
        if ($failed_attempts >= MAX_LOGIN_ATTEMPTS) {
            // Lock account
            $lock_until = date('Y-m-d H:i:s', time() + ACCOUNT_LOCK_DURATION);
            $stmt = $conn->prepare("UPDATE users SET failed_login_attempts = ?, account_locked_until = ? WHERE user_id = ?");
            $stmt->bind_param("isi", $failed_attempts, $lock_until, $user['user_id']);
            $stmt->execute();
            
            echo json_encode([
                'success' => false,
                'message' => 'Too many failed attempts. Account locked for 15 minutes.',
                'locked' => true
            ]);
        } else {
            $stmt = $conn->prepare("UPDATE users SET failed_login_attempts = ? WHERE user_id = ?");
            $stmt->bind_param("ii", $failed_attempts, $user['user_id']);
            $stmt->execute();
            
            $remaining = MAX_LOGIN_ATTEMPTS - $failed_attempts;
            echo json_encode([
                'success' => false,
                'message' => "Invalid email or password. $remaining attempts remaining.",
                'attempts_remaining' => $remaining
            ]);
        }
    }
}

// ============ LOGOUT FUNCTION ============
function handleLogout() {
    destroySession();
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully',
        'redirect' => 'auth.html'
    ]);
}

// ============ CHECK EMAIL AVAILABILITY ============
function checkEmailAvailability($conn) {
    $email = $_GET['email'] ?? '';
    
    if (empty($email)) {
        echo json_encode(['available' => false]);
        return;
    }
    
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode(['available' => $result->num_rows === 0]);
}

// ============ CHECK SESSION ============
function checkSession() {
    if (isLoggedIn()) {
        $user = getCurrentUser();
        echo json_encode([
            'authenticated' => true,
            'user' => $user
        ]);
    } else {
        echo json_encode(['authenticated' => false]);
    }
}
?>
