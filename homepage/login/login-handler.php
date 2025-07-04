<?php
session_start();
require_once '../../db.php'; // use $mysqli as your DB connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both email and password.";
        header("Location: login.php");
        exit;
    }

    // Check user in DB
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

            // Redirect to homepage or admin/staff dashboard
            if ($user['role'] == 'admin') {
                header("Location: ../admin/dashboard.php");
            } elseif ($user['role'] == 'staff') {
                header("Location: ../staff/dashboard.php");
            } else {
                header("Location: http://localhost/purrfect-paws/homepage/main.php");
            }
            exit;
        } else {
            $_SESSION['login_error'] = "Invalid password.";
        }
    } else {
        $_SESSION['login_error'] = "No account found with that email.";
    }

    header("Location: login.php");
    exit;
}
?>
