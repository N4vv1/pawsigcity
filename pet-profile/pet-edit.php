<?php
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pet_id = $_POST['pet_id'];
  $name = $_POST['name'];
  $breed = $_POST['breed'];
  $age = $_POST['age'];
  $birthday = $_POST['birthday'];
  $color = $_POST['color'];
  $gender = $_POST['gender'];

  $mysqli->query("UPDATE pets SET 
    name = '$name',
    breed = '$breed',
    age = '$age',
    birthday = '$birthday',
    color = '$color',
    gender = '$gender'
    WHERE pet_id = $pet_id
  ");

  header("Location: pet-profile.php?updated=1");
  exit;
}
?>
