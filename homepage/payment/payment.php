<?php
session_start();
require '../../db.php';

$user_id = 1; // Replace with $_SESSION['user_id'] when login is active

$appointments = $mysqli->query("SELECT a.*, pk.name AS package_name, pk.price
  FROM appointments a
  JOIN packages pk ON a.package_id = pk.id
  WHERE a.user_id = $user_id AND a.payment_status = 'unpaid'
");
?>

<h2>🧾 Pay for Appointment</h2>

<?php while ($appt = $appointments->fetch_assoc()): ?>
  <form method="POST" action="payment-handler.php">
    <input type="hidden" name="appointment_id" value="<?= $appt['appointment_id'] ?>">
    <p><strong>Package:</strong> <?= $appt['package_name'] ?> - ₱<?= $appt['price'] ?></p>
    
    <label>Payment Method:
      <select name="payment_method" required>
        <option value="gcash">GCash</option>
        <option value="cash">Cash</option>
      </select>
    </label><br><br>
    
    <button type="submit">Pay Now</button>
  </form>
  <hr>
<?php endwhile; ?>
