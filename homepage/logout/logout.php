<?php
session_start();
session_unset(); // Clear all session variables
session_destroy(); // Destroy the session

// Redirect to login page
header("Location: http://localhost/Purrfect-paws/homepage/index.php"); 
exit;
