<?php
$mysqli = new mysqli("localhost", "root", "", "purrfect_paws_system");
if ($mysqli->connect_error) {
  die("Connection failed: " . $mysqli->connect_error);
}
?>