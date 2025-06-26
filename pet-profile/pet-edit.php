<?php
$pet_id = $pet['pet_id'];
?>

<form method="POST" action="pet-edit-handler.php" id="edit-form-<?= $pet['pet_id'] ?>" class="edit-form" style="display:none;">
  <input type="hidden" name="pet_id" value="<?= $pet['pet_id'] ?>">
  <label>Name: <input type="text" name="name" value="<?= htmlspecialchars($pet['name']) ?>"></label><br>
  <label>Breed: <input type="text" name="breed" value="<?= htmlspecialchars($pet['breed']) ?>"></label><br>
  <label>Age: <input type="text" name="age" value="<?= htmlspecialchars($pet['age']) ?>"></label><br>
  <label>Birthday: <input type="date" name="birthday" value="<?= htmlspecialchars($pet['birthday']) ?>"></label><br>
  <label>Color: <input type="text" name="color" value="<?= htmlspecialchars($pet['color']) ?>"></label><br>
  <label>Gender:
    <select name="gender">
      <option value="Male" <?= $pet['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
      <option value="Female" <?= $pet['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
    </select>
  </label><br>
  <button type="submit">💾 Save</button>
</form>


