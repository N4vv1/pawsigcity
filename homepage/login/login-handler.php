<?php
session_start();
require_once '../../db.php'; // $mysqli = DB connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic input validation
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both email and password.";
        header("Location: login.php");
        exit;
    }

    // Fetch user by email
    $stmt = $mysqli->prepare("SELECT user_id, full_name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: ../admin/admin-dashboard.php");
                    break;
                default: // customer
                    header("Location: http://localhost/Purrfect-paws/homepage/main.php");
                    break;
            }
            exit;
        } else {
            $_SESSION['login_error'] = "Invalid password.";
        }
    } else {
        $_SESSION['login_error'] = "No account found with that email.";
    }

    // Return to login with error
    header("Location: login.php");
    exit;
}
?>
