<?php
// material/delete_unit.php

include('../data/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unit_id'])) {
    $unit_id = intval($_POST['unit_id']);

    // หมายเหตุ: การลบนี้อาจไม่สำเร็จหากมีวัตถุดิบกำลังใช้ Unit_id นี้อยู่ (Foreign Key Constraint)
    $stmt = $conn->prepare("DELETE FROM unit WHERE Unit_id = ?");
    $stmt->bind_param("i", $unit_id);

    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'failed: ' . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
?>