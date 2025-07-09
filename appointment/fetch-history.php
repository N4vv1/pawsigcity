<?php
require '../db.php';

$user_id = $_GET['user_id'] ?? 0;
if (!$user_id) exit('Invalid user ID.');

$result = $mysqli->prepare("
  SELECT a.*, p.name AS pet_name, pk.name AS package_name
  FROM appointments a
  JOIN pets p ON a.pet_id = p.pet_id
  JOIN packages pk ON a.package_id = pk.id
  WHERE a.user_id = ?
  ORDER BY a.appointment_date DESC
");
$result->bind_param("i", $user_id);
$result->execute();
$appointments = $result->get_result();

echo '<table><tr><th>Pet</th><th>Service</th><th>Date</th><th>Status</th></tr>';
while ($row = $appointments->fetch_assoc()) {
  echo "<tr>
          <td>" . htmlspecialchars($row['pet_name']) . "</td>
          <td>" . htmlspecialchars($row['package_name']) . "</td>
          <td>" . htmlspecialchars($row['appointment_date']) . "</td>
          <td>" . ucfirst($row['status']) . "</td>
        </tr>";
}
echo '</table>';
