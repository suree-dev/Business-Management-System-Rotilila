<?php
date_default_timezone_set('Asia/Bangkok');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rotilila2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>