<?php
session_start();
require_once '../../db.php'; // $conn from pg_connect

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
    $query = "SELECT user_id, first_name, middle_name, last_name, email, password, role 
              FROM users WHERE email = $1";
    $result = pg_query_params($conn, $query, [$email]);

    if (pg_num_rows($result) === 1) {
        $user = pg_fetch_assoc($result);

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Combine full name
            $full_name = $user['first_name'] 
                        . (!empty($user['middle_name']) ? " " . $user['middle_name'] : "") 
                        . " " . $user['last_name'];

            // Set session variables
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];

            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: http://localhost/pawsigcity/dashboard/admin/admin.php");
                    break;
                default: // customer
                    header("Location: http://localhost/pawsigcity/homepage/main.php");
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
    header("Location: http://localhost/pawsigcity/homepage/login/loginform.php");
    exit;
}
?>
