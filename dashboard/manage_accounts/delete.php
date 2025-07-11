<?php
require_once '../../db.php';

$id = intval($_GET['id']);
$mysqli->query("DELETE FROM users WHERE user_id = $id");
header("Location: http://localhost/Purrfect-paws/dashboard/manage_accounts/accounts.php");
exit;
?>