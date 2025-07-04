<!DOCTYPE html>
<html>
<head>
  <title>Add New Pet</title>
</head>

<body>
  <h2>Add a Pet</h2>
  <form method="POST" action="add-pet-handler.php" enctype="multipart/form-data">
    <label>Name: <input type="text" name="name" required></label><br><br>
    <label>Breed: <input type="text" name="breed" required></label><br><br>
    <label>Gender:
      <select name="gender">
        <option value="Male">Male</option>
        <option value="Female">Female</option>
      </select>
    </label><br><br>
    <label>Age: <input type="text" name="age"></label><br><br>
    <label>Birthday: <input type="date" name="birthday"></label><br><br>
    <label>Color: <input type="text" name="color"></label><br><br>
    <label>Photo: <input type="file" name="photo" accept="image/*"></label><br><br>

    <h3>Health Info</h3>
    <label>Allergies: <textarea name="allergies"></textarea></label><br><br>
    <label>Medications: <textarea name="medications"></textarea></label><br><br>
    <label>Medical Conditions: <textarea name="medical_conditions"></textarea></label><br><br>

    <h3>Behavior & Preferences</h3>
    <label>Behavior Notes: <textarea name="behavior_notes"></textarea></label><br><br>
    <label>Nail Trimming:
      <select name="nail_trimming">
        <option value="Yes">Yes</option>
        <option value="No">No</option>
      </select>
    </label><br><br>
    <label>Haircut Style: <input type="text" name="haircut_style"></label><br><br>

    <button type="submit">Add Pet</button>
  </form>
</body>
</html>
