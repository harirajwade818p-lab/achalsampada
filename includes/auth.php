<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Register user
function registerUser($data) {
    global $conn;
    
    try {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Generate OTP
        $otp = generateOTP();
        $otpExpiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, phone, role, email_otp, otp_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['email'],
            $hashedPassword,
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            $data['role'] ?? 'buyer',
            $otp,
            $otpExpiry
        ]);
        
        $userId = $conn->lastInsertId();
        
        // Send verification email
        $subject = 'Verify Your Email - ' . SITE_NAME;
        $message = "
            <html>
            <head>
                <title>Email Verification</title>
            </head>
            <body>
                <h2>Welcome to " . SITE_NAME . "</h2>
                <p>Your OTP is: <strong>$otp</strong></p>
                <p>This OTP will expire in 15 minutes.</p>
                <p>If you did not create an account, please ignore this email.</p>
            </body>
            </html>
        ";
        
        sendEmail($data['email'], $subject, $message);
        
        // Log activity
        logActivity($userId, 'register', 'user', 'User registered');
        
        return ['success' => true, 'message' => 'Registration successful. Please verify your email.', 'user_id' => $userId];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

// Login user
function loginUser($email, $password) {
    global $conn;
    
    try {
        // Check if account is locked
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        if ($user['account_status'] === 'suspended') {
            return ['success' => false, 'message' => 'Your account has been suspended. Please contact support.'];
        }
        
        if ($user['account_status'] === 'deleted') {
            return ['success' => false, 'message' => 'This account has been deleted.'];
        }
        
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return ['success' => false, 'message' => 'Account locked. Try again after ' . date('H:i', strtotime($user['locked_until']))];
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Reset login attempts
            $stmt = $conn->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Log activity
            logActivity($user['id'], 'login', 'auth', 'User logged in');
            
            return ['success' => true, 'message' => 'Login successful', 'user' => $user];
        } else {
            // Increment login attempts
            $loginAttempts = $user['login_attempts'] + 1;
            $lockedUntil = null;
            
            if ($loginAttempts >= 5) {
                $lockedUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            }
            
            $stmt = $conn->prepare("UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?");
            $stmt->execute([$loginAttempts, $lockedUntil, $user['id']]);
            
            $message = $loginAttempts >= 5 ? 'Too many failed attempts. Account locked for 30 minutes.' : 'Invalid email or password';
            
            return ['success' => false, 'message' => $message];
        }
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
    }
}

// Logout user
function logoutUser() {
    if (isLoggedIn()) {
        $userId = getCurrentUserId();
        logActivity($userId, 'logout', 'auth', 'User logged out');
    }
    
    session_unset();
    session_destroy();
    
    return ['success' => true, 'message' => 'Logged out successfully'];
}

// Update user profile
function updateProfile($userId, $data) {
    global $conn;
    
    try {
        $fields = [];
        $values = [];
        
        $allowedFields = ['first_name', 'last_name', 'phone', 'date_of_birth', 'gender', 'address', 'city', 'state', 'country', 'pincode'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }
        
        $values[] = $userId;
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($values);
        
        logActivity($userId, 'update', 'profile', 'Profile updated');
        
        return ['success' => true, 'message' => 'Profile updated successfully'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
    }
}

// Update profile image
function updateProfileImage($userId, $file) {
    global $conn;
    
    try {
        // Upload file
        $upload = uploadFile($file, 'profile_images');
        
        if (!$upload['success']) {
            return ['success' => false, 'message' => $upload['message']];
        }
        
        // Get old image
        $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // Delete old image
        if ($user && $user['profile_image']) {
            deleteFile($user['profile_image']);
        }
        
        // Update database
        $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $stmt->execute([$upload['filePath'], $userId]);
        
        logActivity($userId, 'update', 'profile', 'Profile image updated');
        
        return ['success' => true, 'message' => 'Profile image updated successfully', 'imagePath' => $upload['filePath']];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
    }
}

// Change password
function changePassword($userId, $currentPassword, $newPassword) {
    global $conn;
    
    try {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        
        logActivity($userId, 'update', 'security', 'Password changed');
        
        return ['success' => true, 'message' => 'Password changed successfully'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Password change failed: ' . $e->getMessage()];
    }
}

// Submit KYC documents
function submitKYC($userId, $data, $files) {
    global $conn;
    
    try {
        $updates = [];
        $values = [];
        
        // Upload Aadhaar document
        if (isset($files['aadhaar_document']) && $files['aadhaar_document']['error'] === 0) {
            $upload = uploadFile($files['aadhaar_document'], 'kyc_documents', ['jpg', 'jpeg', 'png', 'pdf']);
            if ($upload['success']) {
                $updates[] = "aadhaar_document = ?";
                $values[] = $upload['filePath'];
            }
        }
        
        // Upload PAN document
        if (isset($files['pan_document']) && $files['pan_document']['error'] === 0) {
            $upload = uploadFile($files['pan_document'], 'kyc_documents', ['jpg', 'jpeg', 'png', 'pdf']);
            if ($upload['success']) {
                $updates[] = "pan_document = ?";
                $values[] = $upload['filePath'];
            }
        }
        
        if (isset($data['aadhaar_number'])) {
            $updates[] = "aadhaar_number = ?";
            $values[] = $data['aadhaar_number'];
        }
        
        if (isset($data['pan_number'])) {
            $updates[] = "pan_number = ?";
            $values[] = $data['pan_number'];
        }
        
        $updates[] = "kyc_verified = ?";
        $values[] = 'pending';
        
        $values[] = $userId;
        
        $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute($values);
        
        logActivity($userId, 'submit', 'kyc', 'KYC documents submitted');
        
        return ['success' => true, 'message' => 'KYC documents submitted successfully. Pending verification.'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'KYC submission failed: ' . $e->getMessage()];
    }
}

// Forgot password
function forgotPassword($email) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Email not found'];
        }
        
        // Generate reset token
        $token = generateToken(64);
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token (you might want a separate table for this)
        $_SESSION['reset_token'] = $token;
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_expiry'] = $expiry;
        
        // Send reset email
        $resetLink = BASE_URL . 'reset-password.php?token=' . $token;
        
        $subject = 'Password Reset - ' . SITE_NAME;
        $message = "
            <html>
            <head>
                <title>Password Reset</title>
            </head>
            <body>
                <h2>Password Reset Request</h2>
                <p>Hello {$user['first_name']},</p>
                <p>Click the link below to reset your password:</p>
                <p><a href='$resetLink'>$resetLink</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you did not request this, please ignore this email.</p>
            </body>
            </html>
        ";
        
        sendEmail($email, $subject, $message);
        
        logActivity($user['id'], 'request', 'password_reset', 'Password reset requested');
        
        return ['success' => true, 'message' => 'Password reset link sent to your email'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Request failed: ' . $e->getMessage()];
    }
}

// Reset password
function resetPassword($token, $newPassword) {
    global $conn;
    
    try {
        // Verify token
        if (!isset($_SESSION['reset_token']) || $_SESSION['reset_token'] !== $token) {
            return ['success' => false, 'message' => 'Invalid token'];
        }
        
        if (strtotime($_SESSION['reset_expiry']) < time()) {
            unset($_SESSION['reset_token'], $_SESSION['reset_email'], $_SESSION['reset_expiry']);
            return ['success' => false, 'message' => 'Token expired'];
        }
        
        $email = $_SESSION['reset_email'];
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        
        // Clear session
        unset($_SESSION['reset_token'], $_SESSION['reset_email'], $_SESSION['reset_expiry']);
        
        return ['success' => true, 'message' => 'Password reset successfully'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Reset failed: ' . $e->getMessage()];
    }
}
?>
