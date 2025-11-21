<?php
$conn = mysqli_connect("localhost","root","","barkffy_pet_db");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>