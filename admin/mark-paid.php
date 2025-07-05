<?php
session_start();
require '../db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login/admin-login.php");
    exit;
}

$payment_id = $_GET['payment_id'] ?? null;

if ($payment_id) {
    $stmt = $mysqli->prepare("UPDATE payments SET status = 'paid', paid_at = NOW() WHERE payment_id = ?");
    $stmt->bind_param("i", $payment_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Payment marked as paid.";
    } else {
        $_SESSION['error'] = "Failed to update payment.";
    }
}

header("Location: pending-payments.php");
exit;
?>
