<?php
// DATABASE CONFIGURATION
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'secureshops');
// CRITICAL: If you change this key, all previously encrypted emails will be unreadable!
define('ENCRYPTION_KEY', 'SecureShops_AES_Key_2024!@#$%^&*()');

function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($db->connect_error) {
            die(json_encode(['error' => 'Connection failed: ' . $db->connect_error]));
        }
        $db->set_charset('utf8mb4');
    }
    return $db;
}
// ENCRYPTION HELPERS (AES-256-CBC)
function encryptData($data) {
    if ($data === null || $data === '') return '';
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
    // Format: IV::EncryptedData encoded in base64
    return base64_encode($iv . '::' . $encrypted);
}

function decryptData($data) {
    if ($data === null || $data === '') return '';
    $decoded = base64_decode($data, true);
    if ($decoded === false) return $data; 
    
    $parts = explode('::', $decoded, 2);
    if (count($parts) !== 2) return $data;
    
    [$iv, $encrypted] = $parts;
    return openssl_decrypt($encrypted, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
}
// INPUT SANITIZATION
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}
// PASSWORD VALIDATION
function validatePassword($password) {
    if (strlen($password) < 8) return false;
    if (!preg_match('/[A-Za-z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    return true;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
// SESSION HELPERS
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => false, // Set to TRUE if using HTTPS
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

function requireAdminLogin() {
    startSecureSession();
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ../admin/login.php');
        exit;
    }
}

function requireUserLogin() {
    startSecureSession();
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../user/login.php');
        exit;
    }
}
// CSRF TOKEN
function generateCSRFToken() {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    startSecureSession();
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}


// ============================================================
// FLASH MESSAGES
// ============================================================
function setFlash($type, $message) {
    startSecureSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    startSecureSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================================
// ENCRYPTED EMAIL LOOKUP HELPERS
// ============================================================

/**
 * Since emails are encrypted with a random IV, we cannot search them via SQL.
 * This function fetches all admins and decrypts them locally to find a match.
 */
function findAdminByEmail($targetEmail) {
    $db = getDB();
    $result = $db->query("SELECT id, username, email, password_hash FROM admins");
    while ($row = $result->fetch_assoc()) {
        if (decryptData($row['email']) === $targetEmail) {
            return $row;
        }
    }
    return null;
}

/**
 * Finds a user by decrypting and comparing emails.
 */
function findUserByEmail($targetEmail) {
    $db = getDB();
    $result = $db->query("SELECT id, username, email, password_hash FROM users");
    while ($row = $result->fetch_assoc()) {
        if (decryptData($row['email']) === $targetEmail) {
            return $row;
        }
    }
    return null;
}