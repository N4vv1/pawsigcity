<?php
require_once '../../db.php';

$id = intval($_GET['id']);
$user = $mysqli->query("SELECT * FROM users WHERE user_id = $id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $mysqli->real_escape_string($_POST['full_name']);
  $email = $mysqli->real_escape_string($_POST['email']);
  $phone = $mysqli->real_escape_string($_POST['phone']);
  $role = $mysqli->real_escape_string($_POST['role']);

  $mysqli->query("UPDATE users SET full_name='$name', email='$email', phone='$phone', role='$role' WHERE user_id=$id");
  header("Location: user-management.php");
  exit;
}
?>

<!DOCTYPE html>
<html>
<head><title>Edit User</title></head>
<body>
  <h2>Edit User</h2>
  <form method="POST">
    <input name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required><br>
    <input name="email" value="<?= htmlspecialchars($user['email']) ?>" required><br>
    <input name="phone" value="<?= htmlspecialchars($user['phone']) ?>"><br>
    <select name="role">
      <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
      <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
    </select><br><br>
    <button type="submit">Update</button>
  </form>
</body>
</html>