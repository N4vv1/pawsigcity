<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Include your database connection
include 'db.php'; // FIXED - removed the leading slash

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($email) || empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['login_error'] = "All fields are required!";
        header('Location: auth.php');
        exit();
    }
    
    // Check if passwords match
    if ($new_password !== $confirm_password) {
        $_SESSION['login_error'] = "New passwords do not match!";
        header('Location: auth.php');
        exit();
    }
    
    // Check password length
    if (strlen($new_password) < 8) {
        $_SESSION['login_error'] = "Password must be at least 8 characters long!";
        header('Location: auth.php');
        exit();
    }
    
    // Check if user exists and verify old password
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['login_error'] = "Email not found!";
        header('Location: auth.php');
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Verify old password
    if (!password_verify($old_password, $user['password'])) {
        $_SESSION['login_error'] = "Current password is incorrect!";
        header('Location: auth.php');
        exit();
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update_stmt->bind_param("si", $hashed_password, $user['id']);
    
    if ($update_stmt->execute()) {
        $_SESSION['login_success'] = "Password reset successfully! Please login with your new password.";
    } else {
        $_SESSION['login_error'] = "Failed to reset password. Please try again.";
    }
    
    $stmt->close();
    $update_stmt->close();
    $conn->close();
    
    header('Location: auth.php');
    exit();
}
?>
```

---

## **Now test it:**

1. Save the file
2. Fill in the reset password form
3. Click "Reset Password"
4. Tell me what happens!

**If you see any error messages, copy and paste them here.**

---

## **Quick check - Where is your db.php file located?**

Tell me the folder structure. For example:
```
pawsigcity/
  ├── homepage/
  │   └── login/
  │       ├── auth.php
  │       ├── reset-password-handler.php
  │       └── db.php