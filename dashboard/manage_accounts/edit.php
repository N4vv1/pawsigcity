<?php
require_once '../../db.php';

$id = intval($_GET['id']);

// Fetch user by ID
$result = pg_query_params($conn, "SELECT * FROM users WHERE user_id = $1", [$id]);
$user = pg_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name  = $_POST['full_name'];
  $email = $_POST['email'];
  $phone = $_POST['phone'];
  $role  = $_POST['role'];

  pg_query_params(
    $conn,
    "UPDATE users SET full_name = $1, email = $2, phone = $3, role = $4 WHERE user_id = $5",
    [$name, $email, $phone, $role, $id]
  );

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