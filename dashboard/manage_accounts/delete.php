<?php
require_once '../../db.php';

$id = intval($_GET['id']);
$mysqli->query("DELETE FROM users WHERE user_id = $id");
header("Location: user-management.php");
exit;
?>