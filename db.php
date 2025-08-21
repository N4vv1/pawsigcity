<?php
$mysqli = new mysqli("localhost", "root", "", "pet_grooming_system");
if ($mysqli->connect_error) {
  die("Connection failed: " . $mysqli->connect_error);
}
?>