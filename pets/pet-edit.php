<?php
$pet_id = $pet['pet_id'];
?>

<form method="POST" action="pet-edit-handler.php" id="edit-form-<?= $pet['pet_id'] ?>" class="edit-form" style="display:none;" enctype="multipart/form-data">
  <input type="hidden" name="pet_id" value="<?= $pet['pet_id'] ?>">
  
  <label>Name: <input type="text" name="name" value="<?= htmlspecialchars($pet['name']) ?>" required></label><br>
  
  <label>Breed: <input type="text" name="breed" value="<?= htmlspecialchars($pet['breed']) ?>" required></label><br>
  
  <label>Age: <input type="number" step="0.1" name="age" value="<?= htmlspecialchars($pet['age']) ?>"></label><br>
  
  <label>Birthday: <input type="date" name="birthday" value="<?= htmlspecialchars($pet['birthday']) ?>"></label><br>
  
  <label>Color: <input type="text" name="color" value="<?= htmlspecialchars($pet['color']) ?>"></label><br>
  
  <label>Gender:
    <select name="gender" required>
      <option value="Male" <?= $pet['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
      <option value="Female" <?= $pet['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
    </select>
  </label><br>
  
  <label>Update Photo (optional):
    <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp">
  </label><br>
  <small style="color: #666;">Leave empty to keep current photo. Accepted: JPEG, PNG, GIF, WebP</small><br>
  
  <?php if (!empty($pet['photo_url'])): ?>
    <div style="margin: 10px 0;">
      <small>Current photo:</small><br>
      <img src="<?= htmlspecialchars($pet['photo_url']) ?>" alt="<?= htmlspecialchars($pet['name']) ?>" style="max-width: 150px; max-height: 150px; border-radius: 8px; margin-top: 5px;">
    </div>
  <?php endif; ?>
  
  <button type="submit">💾 Save</button>
  <button type="button" onclick="toggleEdit(<?= $pet['pet_id'] ?>)">❌ Cancel</button>
</form>