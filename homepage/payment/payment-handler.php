<?php
session_start();
require '../../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;
    $appointment_id = $_POST['appointment_id'] ?? null;
    $method = $_POST['method'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $reference_number = $_POST['reference_number'] ?? null;
    $paid_at = $_POST['paid_at'] ?? null;
    $screenshot_url = null;

    // Handle file upload if method is GCash
    if ($method === 'gcash' && isset($_FILES['gcash_screenshot']) && $_FILES['gcash_screenshot']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/gcash/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $filename = uniqid() . '_' . basename($_FILES['gcash_screenshot']['name']);
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['gcash_screenshot']['tmp_name'], $target_path)) {
            $screenshot_url = $target_path;
        }
    }

    // Validate required fields
    if (!$appointment_id || !$method || !$amount) {
        $_SESSION['error'] = "Missing required fields.";
        header("Location: payment.php?appointment_id=$appointment_id");
        exit;
    }

    // Set status and paid_at
    $status = ($method === 'cash') ? 'pending' : 'paid';

    if ($method === 'gcash' && empty($paid_at)) {
        $paid_at = date('Y-m-d H:i:s');
    }

    // Insert into payments
    $stmt = $mysqli->prepare("INSERT INTO payments (appointment_id, user_id, method, amount, status, reference_number, paid_at, screenshot_url) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisdssss", $appointment_id, $user_id, $method, $amount, $status, $reference_number, $paid_at, $screenshot_url);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Payment submitted!";
        header("Location: ../../pets/pet-profile.php");
        exit;
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
        header("Location: payment.php?appointment_id=$appointment_id");
        exit;
    }
}
?>
