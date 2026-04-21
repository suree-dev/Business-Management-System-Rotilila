<?php
include('../data/db_connect.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error); }
$conn->set_charset("utf8");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_recipe') {
    $recipes_name = $conn->real_escape_string($_POST['recipes_name']);
    $conn->begin_transaction();
    try {
        $sql_recipe = "INSERT INTO Recipes (Recipes_name) VALUES ('$recipes_name')";
        if (!$conn->query($sql_recipe)) throw new Exception($conn->error);
        $recipes_id = $conn->insert_id;
        if (!empty($_POST['material_id'])) {
            foreach ($_POST['material_id'] as $key => $material_id) {
                $ingr_quantity = $_POST['quantity'][$key];
                $unit_id = $_POST['unit_id'][$key];
                $material_info = $conn->query("SELECT material_name FROM Material WHERE material_id = $material_id")->fetch_assoc();
                $unit_info = $conn->query("SELECT Unit_name FROM Unit WHERE Unit_id = $unit_id")->fetch_assoc();
                $material_name = $conn->real_escape_string($material_info['material_name']);
                $unit_name = $conn->real_escape_string($unit_info['Unit_name']);
                $sql_ingredient = "INSERT INTO Ingredient (Recipes_id, Recipes_name, material_id, material_name, ingr_quantity, Unit_id, Unit_name) VALUES ('$recipes_id', '$recipes_name', '$material_id', '$material_name', '$ingr_quantity', '$unit_id', '$unit_name')";
                if (!$conn->query($sql_ingredient)) throw new Exception($conn->error);
            }
        }
        $conn->commit();
        echo "<script>alert('เพิ่มสูตรอาหารสำเร็จ!'); window.location.href='../recipe/own.editrecipe.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('เกิดข้อผิดพลาด: " . $e->getMessage() . "'); window.history.back();</script>";
    }
}

$conn->close();
?>
