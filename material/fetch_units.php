<?php
// material/fetch_units.php

header('Content-Type: application/json; charset=utf-8');
include('../data/db_connect.php'); 

$units = [];
$sql = "SELECT Unit_id, Unit_name FROM unit ORDER BY Unit_name ASC";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $units[] = $row;
    }
}

mysqli_close($conn);
echo json_encode($units);
?>