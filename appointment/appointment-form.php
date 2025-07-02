<?php
session_start();
require '../db.php';

$user_id = 1; // Replace with $_SESSION['user_id'] when login is active

// Fetch user's pets
$pets = $mysqli->query("SELECT * FROM pets WHERE user_id = $user_id");

// Fetch available services
$services = $mysqli->query("SELECT * FROM services");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Book Appointment</title>
</head>
<body>

<?php
if (isset($_SESSION['success'])) {
  echo "<p style='color:green; font-weight:bold'>" . $_SESSION['success'] . "</p>";
  unset($_SESSION['success']);
}
?>

<h2>Book a Grooming Appointment</h2>

<form action="appointment-handler.php" method="POST">
  <label for="pet_id">Choose your pet:</label><br>
  <select name="pet_id" id="pet_id" required onchange="showBreed(this)">
    <option value="" disabled selected>Select a pet</option>
    <?php 
        $petArray = []; // Collect pet data for JS
        while ($pet = $pets->fetch_assoc()): 
        $petArray[$pet['pet_id']] = $pet['breed'];
    ?>
        <option value="<?= $pet['pet_id'] ?>"><?= htmlspecialchars($pet['name']) ?></option>
    <?php endwhile; ?>
  </select><br>
  <p id="breedDisplay" style="font-weight: bold; margin-top: 5px;"></p>


  <label for="service_id">Select Service:</label><br>
  <select name="service_id" id="service_id" required>
    <?php while ($service = $services->fetch_assoc()): ?>
      <option value="<?= $service['service_id'] ?>"><?= htmlspecialchars($service['name']) ?></option>
    <?php endwhile; ?>
  </select><br><br>

  <label for="appointment_date">Appointment Date and Time:</label><br>
  <input type="datetime-local" name="appointment_date" id="appointment_date" required><br><br>

  <label for="groomer_name">Preferred Groomer (optional):</label><br>
  <input type="text" name="groomer_name" id="groomer_name"><br><br>

  <label for="notes">Notes (optional):</label><br>
  <textarea name="notes" id="notes" rows="3" cols="30"></textarea><br><br>

  <button type="submit">Book Appointment</button>
</form>

</body>
</html>
<script>
  const breedData = <?= json_encode($petArray) ?>;

  function showBreed(select) {
    const breed = breedData[select.value] || '';
    document.getElementById('breedDisplay').textContent = breed ? `Breed: ${breed}` : '';
  }
</script>

