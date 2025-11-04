<?php
session_start();
require_once '../../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "⚠️ Please enter both email and password.";
        header("Location: loginform.php");
        exit;
    }

    // ✅ FIRST: Check if user exists in USERS table
    $user_query = "SELECT user_id, first_name, middle_name, last_name, email, password, role 
                   FROM users WHERE email = $1";
    $user_result = pg_query_params($conn, $user_query, [$email]);

    if ($user_result && pg_num_rows($user_result) === 1) {
        $user = pg_fetch_assoc($user_result);

        if (password_verify($password, $user['password'])) {
            $full_name = $user['first_name'] 
                        . (!empty($user['middle_name']) ? " " . $user['middle_name'] : "") 
                        . " " . $user['last_name'];

            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];

            switch ($user['role']) {
                case 'admin':
                    header("Location: https://pawsigcity.onrender.com/dashboard/admin/admin.php");
                    break;

                case 'groomer':
                    header("Location: https://pawsigcity.onrender.com/groomer_dashboard/home_groomer.php");
                    break;

                case 'receptionist':
                    header("Location: https://pawsigcity.onrender.com/receptionist_dashboard/receptionist_home.php");
                    break;

                default: // customer
                    header("Location: https://pawsigcity.onrender.com/homepage/main.php");
                    break;
            }
            exit;
        }
    }

    // ✅ SECOND: Check if user exists in GROOMERS table
    $groomer_query = "SELECT groomer_id, groomer_name, email, password 
                      FROM groomers WHERE email = $1";
    $groomer_result = pg_query_params($conn, $groomer_query, [$email]);

    if ($groomer_result && pg_num_rows($groomer_result) === 1) {
        $groomer = pg_fetch_assoc($groomer_result);

        if (password_verify($password, $groomer['password'])) {
            $_SESSION['groomer_id'] = $groomer['groomer_id'];
            $_SESSION['groomer_name'] = $groomer['groomer_name'];
            $_SESSION['email'] = $groomer['email'];

            header("Location: https://pawsigcity.onrender.com/groomer_dashboard/home_groomer.php");
            exit;
        } else {
            $_SESSION['login_error'] = "❌ Incorrect password for groomer account.";
            header("Location: loginform.php");
            exit;
        }
    }

    // ❌ No account found in either table
    $_SESSION['login_error'] = "❌ No account found with email: " . htmlspecialchars($email);
    header("Location: loginform.php");
    exit;
}
?>
