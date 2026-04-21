<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = strtolower($_SESSION['role'] ?? '');
include('../data/db_connect.php'); 

function format_thai_date($datetime) {
    if (!$datetime) return '-';
    $ts = strtotime($datetime);
    $months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    $d = date('j', $ts);
    $m = $months[(int)date('n', $ts)];
    $y = date('Y', $ts) + 543;
    $h = date('H:i', $ts);
    return "$d $m $y $h น.";
}

// ดึงข้อมูล Categories สำหรับใช้ในฟอร์มและฟิลเตอร์
$categories_for_forms = [];
$category_sql = "SELECT cate_id, cate_name FROM categories WHERE cate_id IN (1, 2, 3, 4, 5, 6) ORDER BY FIELD(cate_id, 1, 3, 4, 2, 5, 6)";
$category_result = mysqli_query($conn, $category_sql);
while ($row = mysqli_fetch_assoc($category_result)) {
    $categories_for_forms[] = $row;
}

// ดึงวันที่อัปเดตล่าสุด
$latest_updated_at_result = mysqli_query($conn, "SELECT updated_at FROM menu ORDER BY updated_at DESC LIMIT 1");
$latest_updated_at_row = mysqli_fetch_assoc($latest_updated_at_result);
$latest_updated_at = $latest_updated_at_row['updated_at'] ?? null;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>จัดการรายการอาหาร - ร้านโรตีลีลา</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../style/editmenu.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../style/navnsidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include('../layout/navbar.php'); ?>
<?php include('../layout/sidebar.php'); ?>

<div class="ingredient-manager-container">
    <!-- Header -->
    <div class="im-header">
        <div class="im-header-left">
            <div class="im-header-icon-wrapper" style="color: #27ae60; background-color: #e9f7ef;">
                <i class="fas fa-utensils"></i>
            </div>
            <div>
                <h1 class="im-title">จัดการรายการอาหาร</h1>
                <p class="im-desc">เพิ่ม ลบ แก้ไข และจัดหมวดหมู่เมนูอาหาร</p>
            </div>
        </div>
        <div class="im-header-tabs">
            <button class="im-tab active" id="tab-menus" onclick="showTab('menus')">จัดการเมนู</button>
            <button class="im-tab" id="tab-categories" onclick="showTab('categories')">จัดการประเภท</button>
        </div>
    </div>

    <!-- ===== START: เนื้อหาแท็บจัดการเมนู ===== -->
    <div id="tab-menus-content"> 
        <!-- Filter & Action Bar -->
        <div class="im-filter-bar">
            <div class="im-search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="menu-search-input" placeholder="ค้นหาเมนู...">
            </div>
            <select id="categoryFilter">
                <option value="all">ทุกประเภท</option>
                <?php foreach ($categories_for_forms as $cat): ?>
                    <option value="cate<?= $cat['cate_id'] ?>"><?= $cat['cate_name'] ?></option>
                <?php endforeach; ?>
            </select>
            <div class="last-updated-info">
                <?php if ($latest_updated_at): ?>
                    <i class="fas fa-history"></i> อัปเดตล่าสุด: <?= format_thai_date($latest_updated_at) ?>
                <?php endif; ?>
            </div>
            <button class="im-add-btn" onclick="openPopup('addPopup')"><i class="fas fa-plus"></i> เพิ่มเมนู</button>
        </div>

        <!-- Menu Table -->
        <div class="im-table-wrapper">
            <table class="im-table">
                <thead>
                    <tr>
                        <th>ลำดับ</th>
                        <th>ชื่อเมนู</th>
                        <th>ราคา (บาท)</th>
                        <th>ประเภท</th>
                        <th>สถานะ</th>
                        <th>การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- ข้อมูลจะถูกสร้างโดย JavaScript ที่นี่ -->
                </tbody>
            </table>
        </div>
        
        <div id="pagination" class="pagination"></div>
    </div>
    <!-- ===== END: เนื้อหาแท็บจัดการเมนู ===== -->


    <!-- ===== START: เนื้อหาแท็บจัดการประเภท ===== -->
    <div id="tab-categories-content" style="display:none;">
        <div class="im-cate-header">
            <h2>จัดการประเภทเมนู</h2>
            <button class="im-add-btn" onclick="openCategoryModal()">
                <i class="fas fa-plus"></i> เพิ่มประเภท
            </button>
        </div>
        <div id="categories-grid" class="im-categories-grid">
            <!-- ข้อมูลประเภทจะถูกสร้างโดย JavaScript ที่นี่ -->
        </div>
    </div>
    <!-- ===== END: เนื้อหาแท็บจัดการประเภท ===== -->

</div>


<!-- Popups (ไม่มีการเปลี่ยนแปลง) -->
<!-- ===== START: POPUP เพิ่มเมนู (โครงสร้างใหม่) ===== -->
<div id="addPopup" class="popup-overlay" style="display: none;">
    <div class="popup" style="max-width: 550px;">
        <div class="popup-header">
            <h3 class="popup-title"><i class="fas fa-plus-circle"></i> เพิ่มเมนูใหม่</h3>
            <button class="popup-close-btn" onclick="closePopup('addPopup')">&times;</button>
        </div>
        <form id="addMenuForm" novalidate>
            <div class="form-item">
                <label for="menu_name">ชื่อเมนู</label>
                <input type="text" id="menu_name" name="menu_name" placeholder="เช่น โรตีใส่ไข่" required>
            </div>
             <div class="form-item">
                <label for="menu_price">ราคา</label>
                <input type="number" step="1.0" id="menu_price" name="menu_price" placeholder="0.00" required>
            </div>
            <div class="form-item">
                <label>ประเภทอาหาร (เลือกได้มากกว่า 1)</label>
                <div class="category-checkboxes-grid">
                  <?php foreach ($categories_for_forms as $cat): ?>
                      <label class='category-checkbox'>
                          <input type='checkbox' name='food_category[]' value='<?= $cat['cate_id'] ?>'> <?= $cat['cate_name'] ?>
                      </label>
                  <?php endforeach; ?>
                </div>
            </div>
            <div class="form-item">
                <label for="menu_image">รูปภาพเมนู</label>
                <input type="file" id="menu_image" name="menu_image" class="custom-file-input" accept="image/*">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-cancel" onclick="closePopup('addPopup')">ยกเลิก</button>
                <button type="submit" class="btn btn-save">บันทึก</button>
            </div>
        </form>
    </div>
</div>
<!-- ===== END: POPUP เพิ่มเมนู ===== -->


<!-- ===== START: POPUP แก้ไขเมนู (โครงสร้างใหม่) ===== -->
<div id="editPopup" class="popup-overlay" style="display:none;">
    <div class="popup" style="max-width: 550px;">
        <div class="popup-header">
            <h3 class="popup-title"><i class="fas fa-edit"></i> แก้ไขเมนู</h3>
            <button class="popup-close-btn" onclick="closePopup('editPopup')">&times;</button>
        </div>
        <form id="editMenuForm" novalidate>
            <input type="hidden" name="menu_ID" id="editMenuID">
            <div class="form-item">
                <label for="editMenuName">ชื่อเมนู</label>
                <input type="text" name="menu_name" id="editMenuName" required>
            </div>
            <div class="form-item">
                <label for="editMenuPrice">ราคา</label>
                <input type="number" step="1.0" name="menu_price" id="editMenuPrice" required>
            </div>
            <div class="form-item">
                <label>ประเภทอาหาร (เลือกได้มากกว่า 1)</label>
                <div id="editCategoryCheckboxes" class="category-checkboxes-grid">
                    <?php foreach ($categories_for_forms as $cat): ?>
                      <label class='category-checkbox'>
                          <input type='checkbox' name='food_category[]' value='<?= $cat['cate_id'] ?>'> <?= $cat['cate_name'] ?>
                      </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-item">
                 <label>รูปเมนูปัจจุบัน</label>
                 <div class="image-preview-container">
                    <img id="currentMenuImage" src="" alt="รูปเมนู">
                    <small id="currentImageFileName"></small>
                 </div>
            </div>
            <div class="form-item">
                <label for="edit_menu_image">เลือกรูปเมนูใหม่ (ถ้าต้องการเปลี่ยน)</label>
                <input type="file" id="edit_menu_image" name="menu_image" class="custom-file-input" accept="image/*">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-cancel" onclick="closePopup('editPopup')">ยกเลิก</button>
                <button type="submit" class="btn btn-save">บันทึก</button>
            </div>
        </form>
    </div>
</div>
<!-- ===== END: POPUP แก้ไขเมนู ===== -->


<!-- ===== START: POPUP ยืนยันการลบ (โครงสร้างใหม่) ===== -->
<div id="deletePopup" class="popup-overlay" style="display:none;">
  <div class="popup" style="max-width: 400px;">
    <div class="popup-header">
        <h3 class="popup-title"><i class="fas fa-exclamation-triangle" style="color: #e53e3e;"></i> ยืนยันการดำเนินการ</h3>
        <button class="popup-close-btn" onclick="closePopup('deletePopup')">&times;</button>
    </div>
    <form id="deleteMenuForm" style="padding: 1.5rem; text-align: center;">
      <input type="hidden" name="menu_ID" id="deleteMenuID">
      <p style="margin-bottom: 1.5rem; font-size: 1.1rem;">คุณแน่ใจหรือไม่ว่าต้องการลบเมนูนี้?</p>
      <div class="form-actions" style="border-top: none; padding-top: 0;">
        <button type="button" class="btn btn-cancel" onclick="closePopup('deletePopup')">ยกเลิก</button>
        <button type="submit" class="btn btn-delete">ยืนยัน</button>
      </div>
    </form>
  </div>
</div>
<!-- ===== END: POPUP ยืนยันการลบ ===== -->

<div id="categoryModal" class="popup-overlay" style="display:none;">
  <div class="popup" style="max-width:400px;">
    <div class="popup-header">
        <h3 id="categoryModalTitle" class="popup-title">เพิ่มประเภทเมนู</h3>
        <button class="popup-close-btn" onclick="closeCategoryModal()">&times;</button>
    </div>
    <form id="categoryForm" autocomplete="off" style="padding: 1.5rem;">
      <input type="hidden" name="cate_id" id="cateId">
      
      <!-- เหลือแค่ฟอร์มสำหรับชื่อ -->
      <div class="form-item">
          <label>ชื่อประเภท</label>
          <input type="text" name="cate_name" id="cateName" placeholder="เช่น เมนูแนะนำ, เครื่องดื่ม" required>
      </div>
      
      <div class="form-actions" style="margin-top: 1rem;">
        <button type="button" class="btn btn-cancel" onclick="closeCategoryModal()">ยกเลิก</button>
        <button type="submit" class="btn btn-save">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<div id="toast" class="toast-message" style="display: none;"></div>

<script src="../js/editmenu.js?v=<?= time() ?>"></script>
</body>
</html>