<?php 
// own.editrecipe.php (ฉบับสมบูรณ์)

error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../data/db_connect.php'); 
if (session_status() === PHP_SESSION_NONE) session_start();

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error); }
$conn->set_charset("utf8");

// ===================================================================
// START: โค้ด PHP สำหรับจัดการ AJAX REQUESTS ทั้งหมด
// ===================================================================

// --- A. ดึงข้อมูลสูตรและส่วนผสม (สำหรับแก้ไข หรือ คัดลอก) ---
if (isset($_GET['get_recipe_details'])) {
    header('Content-Type: application/json');
    $recipe_id = intval($_GET['get_recipe_details']);
    $recipe_query = $conn->query("SELECT Recipes_id, Recipes_name FROM recipes WHERE Recipes_id = $recipe_id");
    $recipe_data = $recipe_query->fetch_assoc();
    $ingredients_query = $conn->query("SELECT material_id, ingr_quantity, Unit_id FROM ingredient WHERE recipes_id = $recipe_id");
    $ingredients_data = [];
    while($row = $ingredients_query->fetch_assoc()) { $ingredients_data[] = $row; }
    echo json_encode(['recipe' => $recipe_data, 'ingredients' => $ingredients_data]);
    exit();
}

// --- B. จัดการการส่งข้อมูลฟอร์ม (เพิ่ม, แก้ไข, ลบ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // B.1 -- จัดการการลบ --
    if ($action === 'delete_recipe') {
        $delete_id = intval($_POST['recipe_id'] ?? 0);
        if ($delete_id > 0) {
            $conn->begin_transaction();
            try {
                $stmt1 = $conn->prepare("DELETE FROM ingredient WHERE Recipes_id = ?");
                $stmt1->bind_param("i", $delete_id);
                $stmt1->execute();
                $stmt1->close();

                $stmt2 = $conn->prepare("DELETE FROM recipes WHERE Recipes_id = ?");
                $stmt2->bind_param("i", $delete_id);
                $stmt2->execute();
                $stmt2->close();
                
                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'ลบสูตรอาหารเรียบร้อยแล้ว']);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID ไม่ถูกต้อง']);
        }
        exit();
    }

    // B.2 -- จัดการการเพิ่มและแก้ไข --
    $recipe_name = trim($_POST['recipes_name'] ?? '');
    $material_ids = $_POST['material_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unit_ids = $_POST['unit_id'] ?? [];
    
    // --- Server-side validation ---
    if (empty($recipe_name)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อสูตรอาหาร']);
        exit();
    }
    if (empty($material_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาเพิ่มส่วนผสมอย่างน้อย 1 รายการ']);
        exit();
    }

    $conn->begin_transaction();
    try {
        if ($action === 'add_recipe') {
            $stmt = $conn->prepare("INSERT INTO recipes (Recipes_name) VALUES (?)");
            $stmt->bind_param("s", $recipe_name);
            $stmt->execute();
            $recipe_id = $stmt->insert_id;
            $stmt->close();
            $success_message = 'เพิ่มสูตรอาหารสำเร็จ!';

        } elseif ($action === 'update_recipe') {
            $recipe_id = intval($_POST['recipe_id_to_edit'] ?? 0);
            if ($recipe_id <= 0) throw new Exception("ID สำหรับแก้ไขไม่ถูกต้อง");

            $stmt = $conn->prepare("UPDATE recipes SET Recipes_name = ? WHERE Recipes_id = ?");
            $stmt->bind_param("si", $recipe_name, $recipe_id);
            $stmt->execute();
            $stmt->close();

            $stmt_del = $conn->prepare("DELETE FROM ingredient WHERE Recipes_id = ?");
            $stmt_del->bind_param("i", $recipe_id);
            $stmt_del->execute();
            $stmt_del->close();
            $success_message = 'แก้ไขสูตรอาหารสำเร็จ!';
        } else {
            throw new Exception("Action ไม่ถูกต้อง");
        }

        // --- บันทึกส่วนผสม (ใช้ร่วมกันทั้งเพิ่มและแก้ไข) ---
        $stmt_ingr = $conn->prepare("INSERT INTO ingredient (Recipes_id, material_id, ingr_quantity, Unit_id) VALUES (?, ?, ?, ?)");
        for ($i = 0; $i < count($material_ids); $i++) {
            $mat_id = intval($material_ids[$i]);
            $qty = floatval($quantities[$i]);
            $unit_id = intval($unit_ids[$i]);
            if ($mat_id > 0 && $unit_id > 0) {
                $stmt_ingr->bind_param("iidi", $recipe_id, $mat_id, $qty, $unit_id);
                $stmt_ingr->execute();
            }
        }
        $stmt_ingr->close();

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => $success_message]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit();
}
// ===================================================================
// END: โค้ด PHP สำหรับจัดการ AJAX
// ===================================================================

// --- ดึงข้อมูลสำหรับ Dropdown ---
$materials_result = $conn->query("SELECT material_id, material_name FROM material ORDER BY material_name COLLATE utf8mb4_general_ci");
$units_result = $conn->query("SELECT Unit_id, Unit_name FROM unit ORDER BY Unit_name");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสูตรอาหาร - ร้านโรตีลีลา</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style/recipe.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../style/navnsidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include('../layout/navbar.php'); ?>
<?php include('../layout/sidebar.php'); ?>

<div class="main-content">
    <div class="im-header">
        <div class="im-header-left">
            <div class="im-header-icon-wrapper" style="color: #e67e22; background-color: #fdf3e6;">
                <i class="fas fa-book-open"></i>
            </div>
            <div>
                <h1 class="im-title">จัดการสูตรอาหาร</h1>
                <p class="im-desc">เพิ่ม ลบ และแก้ไขส่วนผสมในสูตรอาหารต่างๆ</p>
            </div>
        </div>
    </div>

    <div class="form-section" id="recipeFormSection">
        <div class="form-header">
            <h2 id="form-title">เพิ่มสูตรอาหารใหม่</h2>
        </div>
        <form id="recipeForm" method="POST">
            <input type="hidden" id="formAction" name="action" value="add_recipe">
            <input type="hidden" id="recipeIdToEdit" name="recipe_id_to_edit" value="">
            
            <div class="form-item"><label for="recipes_name">ชื่อสูตรอาหาร</label><input type="text" id="recipes_name" name="recipes_name" class="form-control" required></div>
            <div class="form-item">
                <label for="baseRecipeSelect">คัดลอกส่วนผสมจากสูตรอื่น (ถ้ามี)</label>
                <select id="baseRecipeSelect" class="form-control" onchange="loadBaseRecipe(this.value)">
                    <option value="">-- ไม่คัดลอก --</option>
                    <?php
                        $baseRecipes = $conn->query("SELECT Recipes_id, Recipes_name FROM recipes ORDER BY Recipes_name COLLATE utf8mb4_general_ci");
                        while($row = $baseRecipes->fetch_assoc()) { echo "<option value='" . $row['Recipes_id'] . "'>" . htmlspecialchars($row['Recipes_name']) . "</option>"; }
                    ?>
                </select>
            </div>
            <div class="form-item">
                <label>ส่วนผสม</label>
                <div id="ingredients-container"></div>
                <button type="button" class="btn-add-row" onclick="addIngredientRow()">+ เพิ่มส่วนผสม</button>
            </div>
<div class="form-actions">
    <!-- ปุ่มนี้จะแสดงเฉพาะตอน "แก้ไข" เท่านั้น -->
    <button type="button" id="cancelEditBtn" class="btn btn-cancel" style="display: none;" onclick="resetForm()">ยกเลิกการแก้ไข</button>

    <!-- START: เพิ่มปุ่มยกเลิกใหม่ที่นี่ -->
    <!-- ปุ่มนี้จะทำงานเหมือนกัน คือเรียกใช้ resetForm() -->
    <button type="button" id="resetBtn" class="btn btn-secondary" onclick="resetForm()">ยกเลิก</button>
    <!-- END: เพิ่มปุ่มยกเลิกใหม่ -->
    
    <button type="submit" id="submitBtn" class="btn btn-save">บันทึกสูตรอาหาร</button>
</div>
        </form>
    </div>

    <div class="im-table-wrapper">
        <table class="im-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 25%;">ชื่อสูตรอาหาร</th>
                    <th>ส่วนผสม</th>
                    <th style="width: 15%;">การจัดการ</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $counter = 1;
            $sql_get_recipes = "SELECT Recipes_id, Recipes_name FROM recipes ORDER BY Recipes_name COLLATE utf8mb4_general_ci";
            $recipes_list = $conn->query($sql_get_recipes);
            if ($recipes_list->num_rows > 0) {
                while ($recipe = $recipes_list->fetch_assoc()) {
                    echo "<tr><td>" . $counter . "</td><td>" . htmlspecialchars($recipe['Recipes_name']) . "</td><td>";
                    $sql_get_ingredients = "SELECT m.material_name, i.ingr_quantity, u.Unit_name FROM ingredient i JOIN material m ON i.material_id = m.material_id JOIN unit u ON i.Unit_id = u.Unit_id WHERE i.Recipes_id = " . $recipe['Recipes_id'] . " ORDER BY m.material_name COLLATE utf8mb4_general_ci";
                    $ingredients_list = $conn->query($sql_get_ingredients);
                    if ($ingredients_list->num_rows > 0) {
                        echo "<ul>";
                        while ($ingr = $ingredients_list->fetch_assoc()) { echo "<li>" . htmlspecialchars($ingr['material_name']) . " (" . rtrim(rtrim(number_format($ingr['ingr_quantity'], 2), '0'), '.') . " " . htmlspecialchars($ingr['Unit_name']) . ")</li>"; }
                        echo "</ul>";
                    } else { echo "<i>(ยังไม่มีส่วนผสม)</i>"; }
                    echo "</td><td><button type='button' class='im-action-btn im-edit' title='แก้ไข' onclick='editRecipe(" . $recipe['Recipes_id'] . ")'><i class='fas fa-edit'></i></button><button type='button' class='im-action-btn im-delete' title='ลบ' onclick='openDeletePopup(" . $recipe['Recipes_id'] . ", \"" . htmlspecialchars(addslashes($recipe['Recipes_name'])) . "\")'><i class='fas fa-trash'></i></button></td></tr>";
                    $counter++;
                }
            } else { echo "<tr><td colspan='4' style='text-align:center;'>ยังไม่มีข้อมูลสูตรอาหาร</td></tr>"; }
            ?>
            </tbody>
        </table>
    </div>
</div>

<div id="deletePopup" class="popup-overlay" style="display:none;"><div class="popup"><div class="popup-header"><h3 class="popup-title"><i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i> ยืนยันการลบ</h3><button class="popup-close-btn" onclick="closePopup('deletePopup')">&times;</button></div><div class="popup-body"><p>คุณแน่ใจหรือไม่ว่าต้องการลบสูตร "<strong id="recipeNameToDelete"></strong>"?</p></div><div class="popup-footer"><button type="button" class="btn btn-cancel" onclick="closePopup('deletePopup')">ยกเลิก</button><button type="button" id="confirmDeleteBtn" class="btn btn-danger">ยืนยันการลบ</button></div></div></div>
<div id="toast" class="toast-message" style="display: none;"></div>

<script>

 function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const content = document.querySelector('.container') || document.querySelector('.main-content');

      sidebar.classList.toggle('active');
      if (content) content.classList.toggle('shifted');
  }

  document.addEventListener('DOMContentLoaded', function() {
    
    // --- จัดการ Sidebar ---
    const sidebar = document.getElementById('sidebar');
    const content = document.querySelector('.container') || document.querySelector('.main-content');
    const toggleBtn = document.getElementById('toggleSidebar');

    if (toggleBtn && sidebar && content) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
            content.classList.toggle('shifted');
        });
    }
    
    // ปิด sidebar เมื่อคลิกข้างนอก
    // document.addEventListener('click', function (e) {
    //     if (sidebar && !sidebar.contains(e.target) && toggleBtn && !toggleBtn.contains(e.target)) {
    //         if (sidebar.classList.contains('active')) {
    //             sidebar.classList.remove('active');
    //             content.classList.remove('shifted');
    //         }
    //     }
    // });

    // --- เริ่มต้นการแสดงผล Filter ---
    // Initialize view to show all columns by default
    filterByStatus('all');

    const menuLinks = document.querySelectorAll('#sidebar a');
  menuLinks.forEach(link => {
    link.addEventListener('click', () => {
      sidebar.classList.remove('active');
      if (content) content.classList.remove('shifted');
    });
  });

  // Logout confirmation
  const logoutBtn = document.querySelector('.logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function (e) {
      if (!confirm('คุณต้องการออกจากระบบใช่หรือไม่?')) {
        e.preventDefault();
      }
    });
  }

  // Profile dropdown (ถ้ามี)
  const profile = document.querySelector('.admin-profile');
  const dropdown = document.querySelector('.dropdown-menu');
  if (profile && dropdown) {
    profile.addEventListener('click', function (e) {
      e.stopPropagation();
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });
    document.addEventListener('click', function () {
      dropdown.style.display = 'none';
    });
  }
  });

    const materials = [<?php mysqli_data_seek($materials_result, 0); while($row = $materials_result->fetch_assoc()) { echo "{ id: ".$row['material_id'].", name: '".addslashes($row['material_name'])."' },"; } ?>];
    const units = [<?php mysqli_data_seek($units_result, 0); while($row = $units_result->fetch_assoc()) { echo "{ id: ".$row['Unit_id'].", name: '".addslashes($row['Unit_name'])."' },"; } ?>];

    // ===================================
    // ===== CORE FUNCTIONS (ฟังก์ชันหลัก) =====
    // ===================================

    function showToast(message, color = '#2ecc71') {
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = message;
        toast.style.backgroundColor = color;
        toast.style.display = 'block';
        toast.style.animation = 'none';
        void toast.offsetWidth;
        toast.style.animation = 'fadeInOut 3s ease-in-out forwards';
    }

    function openPopup(id) { document.getElementById(id).style.display = 'flex'; }
    function closePopup(id) { document.getElementById(id).style.display = 'none'; }

    function createSelectElement(optionsArray, name, selectedValue = null) {
        let selectHTML = `<select name="${name}" class="form-control" required>`;
        optionsArray.forEach(option => {
            const selected = option.id == selectedValue ? ' selected' : '';
            selectHTML += `<option value="${option.id}"${selected}>${option.name}</option>`;
        });
        selectHTML += `</select>`;
        return selectHTML;
    }

    function addIngredientRow(ingredient = null) {
        const container = document.getElementById('ingredients-container');
        const newRow = document.createElement('div');
        newRow.className = 'ingredient-row-grid';
        const materialSelect = createSelectElement(materials, 'material_id[]', ingredient ? ingredient.material_id : null);
        const quantityInput = `<input type="number" name="quantity[]" placeholder="ปริมาณ" class="form-control" step="0.01" value="${ingredient ? ingredient.ingr_quantity : ''}" required>`;
        const unitSelect = createSelectElement(units, 'unit_id[]', ingredient ? ingredient.Unit_id : null); // แก้ไขเป็น Unit_id (U ตัวใหญ่)
        const removeButton = '<button type="button" class="btn-remove-ingredient" onclick="this.parentElement.remove()">×</button>';
        newRow.innerHTML = materialSelect + quantityInput + unitSelect + removeButton;
        container.appendChild(newRow);
    }

    // ==================================================
    // ===== START: ฟังก์ชันที่หายไป ถูกนำกลับมาแล้ว =====
    // ==================================================
    async function editRecipe(recipeId) {
        const response = await fetch(`?get_recipe_details=${recipeId}`);
        const data = await response.json();
        if (data.recipe) {
            document.getElementById('form-title').innerText = 'แก้ไขสูตรอาหาร: ' + data.recipe.Recipes_name;
            document.getElementById('submitBtn').innerText = 'บันทึกการแก้ไข';
            document.getElementById('submitBtn').className = 'btn btn-warning';
            document.getElementById('cancelEditBtn').style.display = 'inline-block';
            document.getElementById('formAction').value = 'update_recipe';
            document.getElementById('recipeIdToEdit').value = data.recipe.Recipes_id;
            document.getElementById('recipes_name').value = data.recipe.Recipes_name;
            document.getElementById('baseRecipeSelect').value = '';
            const container = document.getElementById('ingredients-container');
            container.innerHTML = '';
            if (data.ingredients && data.ingredients.length > 0) {
                data.ingredients.forEach(ing => addIngredientRow(ing));
            } else { addIngredientRow(); }
            document.getElementById('recipeFormSection').scrollIntoView({ behavior: 'smooth' });
        } else { showToast('ไม่สามารถดึงข้อมูลสูตรอาหารได้', '#e74c3c'); }
    }
    
function loadBaseRecipe(recipeId) {
    // 1. ดึง Element ของ container ส่วนผสมมาก่อน
    const container = document.getElementById('ingredients-container');
    
    // 2. ถ้าผู้ใช้ไม่ได้เลือกสูตรใดๆ (เลือก "-- ไม่คัดลอก --")
    if (!recipeId) {
        // ให้ล้างเฉพาะส่วนผสม แล้วเพิ่มแถวว่าง 1 แถวให้
        container.innerHTML = '';
        addIngredientRow();
        return; // จบการทำงาน
    }
    
    // 3. ถ้าผู้ใช้เลือกสูตร ให้ไปดึงข้อมูล
    fetch(`?get_recipe_details=${recipeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.recipe && data.ingredients) {
                // --- นี่คือส่วนสำคัญ ---
                // เราจะไม่เรียก resetForm() อีกต่อไป
                
                // 4. ล้าง "เฉพาะ" ส่วนผสมเก่าทิ้ง
                container.innerHTML = ''; 

                // 5. วนลูปสร้างแถวส่วนผสมใหม่จากข้อมูลที่ดึงมา
                if (data.ingredients.length > 0) {
                    data.ingredients.forEach(ing => addIngredientRow(ing));
                } else {
                    // ถ้าสูตรที่คัดลอกมาไม่มีส่วนผสม ให้เพิ่มแถวว่าง 1 แถว
                    addIngredientRow(); 
                }
            } else {
                showToast('❌ ไม่สามารถดึงข้อมูลส่วนผสมได้', '#e74c3c');
            }
        })
        .catch(error => {
            console.error('Error loading base recipe:', error);
            showToast('❌ เกิดข้อผิดพลาดในการเชื่อมต่อ', '#e74c3c');
        });
}

    function resetForm() {
        document.getElementById('recipeForm').reset();
        document.getElementById('ingredients-container').innerHTML = '';
        addIngredientRow();
        document.getElementById('form-title').innerText = 'เพิ่มสูตรอาหารใหม่';
        document.getElementById('submitBtn').innerText = 'บันทึกสูตรอาหาร';
        document.getElementById('submitBtn').className = 'btn btn-save';
        document.getElementById('cancelEditBtn').style.display = 'none';
        document.getElementById('formAction').value = 'add_recipe';
        document.getElementById('recipeIdToEdit').value = '';
    }
    // ================================================
    // ===== END: สิ้นสุดส่วนของฟังก์ชันที่นำกลับมา =====
    // ================================================

    function openDeletePopup(recipeId, recipeName) {
        document.getElementById('recipeNameToDelete').textContent = recipeName;
        document.getElementById('confirmDeleteBtn').onclick = () => confirmDelete(recipeId);
        openPopup('deletePopup');
    }
    
    function confirmDelete(recipeId) {
        const formData = new FormData();
        formData.append('action', 'delete_recipe');
        formData.append('recipe_id', recipeId);
        fetch('', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('✅ ' + data.message);
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    showToast('❌ ' + data.message, '#e74c3c');
                }
            })
            .catch(() => showToast('❌ เกิดข้อผิดพลาดในการเชื่อมต่อ', '#e74c3c'));
        closePopup('deletePopup');
    }

    function submitRecipeForm(formElement) {
        const formData = new FormData(formElement);
        
        if (!formData.get('recipes_name').trim()) {
            showToast('❌ กรุณากรอกชื่อสูตรอาหาร', '#e74c3c');
            return;
        }
        if (!formData.has('material_id[]')) {
            showToast('❌ กรุณาเพิ่มส่วนผสมอย่างน้อย 1 รายการ', '#e74c3c');
            return;
        }

        fetch('', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('✅ ' + data.message);
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    showToast('❌ ' + data.message, '#e74c3c');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('❌ เกิดข้อผิดพลาดร้ายแรงในการเชื่อมต่อ', '#e74c3c');
            });
    }

    // ==================================================
    // ===== EVENT LISTENERS (จัดระเบียบใหม่ทั้งหมด) =====
    // ==================================================
    document.addEventListener('DOMContentLoaded', () => {
        // 1. เริ่มต้นฟอร์มด้วยส่วนผสม 1 แถว
        if (document.getElementById('ingredients-container').children.length === 0) {
            addIngredientRow();
        }

        // 2. ตั้งค่า Event Listener สำหรับฟอร์มหลัก
        const recipeForm = document.getElementById('recipeForm');
        if(recipeForm) {
            recipeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitRecipeForm(this);
            });
        }
        
        // 3. จัดการ Toast message จาก PHP (ถ้ามี)
        <?php if (!empty($toast_message)): ?>
            showToast('⚠️ <?php echo addslashes($toast_message); ?>', '#f39c12');
        <?php endif; ?>
    });
</script>

</body>
</html>
<?php $conn->close(); ?>