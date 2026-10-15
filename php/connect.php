<?php

date_default_timezone_set('Asia/Bangkok');

$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "project";


$conn = mysqli_connect($servername, $username, $password, $database);


if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>
