<?php
include('../data/db_connect.php');

if (!isset($_GET['Recipes_id'])) {
    echo json_encode(['error' => 'Missing Recipes_id']);
    exit;
}

$Recipes_id = intval($_GET['Recipes_id']);

// ดึงชื่อสูตร
$sql_recipe = "SELECT Recipes_name FROM recipes WHERE Recipes_id = ?";
$stmt = $conn->prepare($sql_recipe);
$stmt->bind_param('i', $Recipes_id);
$stmt->execute();
$result = $stmt->get_result();
$recipe = $result->fetch_assoc();

if (!$recipe) {
    echo json_encode(['error' => 'ไม่พบสูตรอาหาร']);
    exit;
}

// ดึงส่วนผสมทั้งหมดของสูตรนี้
$sql_ingredients = "SELECT 
                        i.ingredient_id,
                        m.material_name,
                        i.ingr_quantity,
                        u.unit_id,
                        u.unit_name
                    FROM ingredient i
                    JOIN material m ON i.material_id = m.material_id
                    JOIN unit u ON i.Unit_id = u.Unit_id
                    WHERE i.Recipes_id = ?";
$stmt2 = $conn->prepare($sql_ingredients);
$stmt2->bind_param('i', $Recipes_id);
$stmt2->execute();
$result2 = $stmt2->get_result();

$ingredients = [];
while ($row = $result2->fetch_assoc()) {
    $ingredients[] = [
        'ingredient_id' => $row['ingredient_id'],
        'material_name' => $row['material_name'],
        'ingr_quantity' => floatval($row['ingr_quantity']),
        'unit_id' => $row['unit_id'],
        'unit_name' => $row['unit_name']
    ];
}

echo json_encode([
    'Recipes_id' => $Recipes_id,
    'Recipes_name' => $recipe['Recipes_name'],
    'ingredients' => $ingredients
]);
?>