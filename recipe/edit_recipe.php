<?php
include('../data/db_connect.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error); }
$conn->set_charset("utf8");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_recipe') {
    $recipes_id = intval($_POST['recipe_id_to_edit']);
    $recipes_name = $conn->real_escape_string($_POST['recipes_name']);
    $conn->begin_transaction();
    try {
        $conn->query("UPDATE Recipes SET Recipes_name = '$recipes_name' WHERE Recipes_id = $recipes_id");
        $conn->query("DELETE FROM Ingredient WHERE Recipes_id = $recipes_id");
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
        echo "<script>alert('แก้ไขสูตรอาหารสำเร็จ!'); window.location.href='../recipe/own.editrecipe.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('เกิดข้อผิดพลาดในการแก้ไข: " . $e->getMessage() . "'); window.history.back();</script>";
    }
}

$conn->close();
?>
