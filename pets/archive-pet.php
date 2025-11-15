<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../homepage/login/loginform.php');
    exit;
}

if (isset($_POST['pet_id'])) {
    $pet_id = $_POST['pet_id']; // Remove intval if pet_id is UUID/text
    $user_id = $_SESSION['user_id'];
    
    // Verify the pet belongs to the user
    $check_query = "SELECT * FROM pets WHERE pet_id = $1 AND user_id = $2";
    $check_result = pg_query_params($conn, $check_query, [$pet_id, $user_id]);
    
    if ($check_result && pg_num_rows($check_result) > 0) {
        // Archive the pet by setting deleted_at timestamp
        $archive_query = "UPDATE pets SET deleted_at = NOW() WHERE pet_id = $1";
        $archive_result = pg_query_params($conn, $archive_query, [$pet_id]);
        
        if ($archive_result) {
            $_SESSION['success'] = "Pet archived successfully.";
            header('Location: pet-profile.php?pet_archived=1');
        } else {
            $_SESSION['error'] = "Error archiving pet: " . pg_last_error($conn); // Changed to 'error'
            header('Location: pet-profile.php?error=archive_failed');
        }
    } else {
        $_SESSION['error'] = "Pet not found or unauthorized."; // Changed to 'error'
        header('Location: pet-profile.php?error=unauthorized');
    }
} else {
    header('Location: pet-profile.php');
}
exit;
?>