<?php
require_once '../../db.php';

$id = intval($_GET['id']);
pg_query($conn, "DELETE FROM users WHERE user_id = $id");
header("Location: https://pawsigcity.onrender.com/dashboard/manage_accounts/accounts.php");
exit;
?>