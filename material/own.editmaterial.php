<?php include('../data/db_connect.php'); 

$units_options = [];
$sql_units = "SELECT Unit_id, Unit_name FROM unit ORDER BY Unit_name ASC";
$result_units = mysqli_query($conn, $sql_units);
while($row = mysqli_fetch_assoc($result_units)) {
    $units_options[] = $row;
}

function format_thai_date($datetime) {
    if (!$datetime) return '-';
    $ts = strtotime($datetime);
    $months = [
        "", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.",
        "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."
    ];
    $d = date('j', $ts);
    $m = $months[(int)date('n', $ts)];
    $y = date('Y', $ts) + 543;
    return "$d $m $y";
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบจัดการร้าน - ร้านโรตีลีลา</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style/material.css?v=<?= time() ?>">
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
        <div class="im-header-icon-wrapper">
            <i class="fas fa-boxes-stacked"></i>
        </div>
        <div>
            <h1 class="im-title">ระบบจัดการวัตถุดิบ</h1>
            <p class="im-desc">จัดการสต็อก วัตถุดิบ และติดตามวันหมดอายุ</p>
        </div>
    </div>
    <div class="im-header-tabs">
        <button class="im-tab active" id="tab-ingredients" onclick="showTab('ingredients')">จัดการวัตถุดิบ</button>
        <button class="im-tab" id="tab-categories" onclick="showTab('categories')">จัดการประเภทและหน่วยวัด</button>
    </div>
</div>

    <!-- เนื้อหาแต่ละแท็บ -->
    <div id="tab-ingredients-content">
      
    <!-- Stats Cards -->
    <?php
    // --- START: โค้ดที่แก้ไข ---
    $materials_for_stats = [];
    // 1. เปลี่ยน SQL ให้ดึง base_quantity ที่เป็นหน่วยกลาง
    $sql_stats = "SELECT base_quantity, min_stock, expiry_date FROM material";
    $result_stats = mysqli_query($conn, $sql_stats);
    while($row = mysqli_fetch_assoc($result_stats)) {
        $materials_for_stats[] = $row;
    }

    $total = count($materials_for_stats);
    $normal = 0;
    $low = 0; 
    $out = 0; 
    $expiring = 0;
    $today = date('Y-m-d');

    foreach ($materials_for_stats as $mat) {
        // 2. เปลี่ยนมาใช้ base_quantity ในการคำนวณสถานะสต็อก
        $stock = floatval($mat['base_quantity']);
        $min = isset($mat['min_stock']) ? floatval($mat['min_stock']) : 0;
        $exp = isset($mat['expiry_date']) ? $mat['expiry_date'] : null;

        if ($stock <= 0) { // ใช้ <= 0 เพื่อความปลอดภัย
            $out++;
        }
        // ตรวจสอบ low stock เฉพาะกรณีที่ยังไม่หมดสต็อก และมีการตั้งค่า min stock
        else if ($min > 0 && $stock <= $min) { 
            $low++;
        }

        // การคำนวณวันหมดอายุยังคงเหมือนเดิม
        if ($exp && strtotime($exp) >= strtotime($today) && strtotime($exp) - strtotime($today) <= 3 * 86400) {
            $expiring++;
        }
    }
    // --- END: โค้dที่แก้ไข ---
    ?>
    <div class="im-stats-grid">
        <div class="im-stat-card">
            <div class="im-stat-label">รวมทั้งหมด</div>
            <div class="im-stat-value"><?php echo $total; ?></div>
        </div>
        <div class="im-stat-card im-stat-low">
            <div class="im-stat-label">ใกล้หมด</div>
            <div class="im-stat-value"><?php echo $low; ?></div>
        </div>
        <div class="im-stat-card im-stat-out">
            <div class="im-stat-label">หมดสต็อก</div>
            <div class="im-stat-value"><?php echo $out; ?></div>
        </div>
        <div class="im-stat-card im-stat-expiring">
            <div class="im-stat-label">ใกล้หมดอายุ</div>
            <div class="im-stat-value"><?php echo $expiring; ?></div>
        </div>
    </div>


    <!-- Search & Filter -->
    <div class="im-filter-bar">
        <div class="im-search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="im-search-input" placeholder="ค้นหาวัตถุดิบ...">
        </div>
        <select id="im-category-filter">
            <option value="all">ทุกประเภท</option>
            <?php
            $cat_result = mysqli_query($conn, "SELECT cate_id, cate_name FROM categories WHERE cate_id IN (7,8,9,10) ORDER BY cate_id ASC");
            while ($row = mysqli_fetch_assoc($cat_result)) {
                echo "<option value='cate{$row['cate_id']}'>{$row['cate_name']}</option>";
            }
            ?>
        </select>
        <select id="im-stock-filter">
            <option value="all">ทุกสถานะ</option>
            <option value="normal">ปกติ</option>
            <option value="low">ใกล้หมด</option>
            <option value="out">หมดสต็อก</option>
            <option value="expiring">ใกล้หมดอายุ</option>
        </select>
        <button class="im-add-btn" onclick="openPopup('addMaterialPopup')"><i class="fas fa-plus"></i> เพิ่มวัตถุดิบ</button>
    </div>

    <!-- Material Table -->
<div class="im-table-wrapper">
    <table class="im-table">
        <thead>
            <tr>
                <th style="width: 60px;">ลำดับ</th>
                <th>วัตถุดิบ</th>
                <th>สินค้าคงเหลือ</th>
                <th>หน่วยหลัก</th>
                <th>หน่วยย่อย</th>
                <th>วันหมดอายุ</th>
                <th>สถานะ</th>
                <th>การดำเนินการ</th>
            </tr>
        </thead>
        <!-- แก้ไขส่วน tbody ให้เป็นพื้นที่ว่างสำหรับ JavaScript -->
        <tbody id="im-table-body">
            <!-- ข้อมูลจะถูกสร้างโดย JavaScript ที่นี่ -->
        </tbody>
    </table>
</div>
    
    <!-- เพิ่มส่วนของ Pagination -->
    <div id="pagination" class="pagination"></div>


    <!-- Popup: เพิ่มวัตถุดิบ (ปรับปรุงใหม่) -->
<div id="addMaterialPopup" class="popup-overlay" style="display: none;">
    <div class="popup">
        <div class="popup-header">
            <h3 class="popup-title"><i class="fas fa-plus-circle"></i> เพิ่มวัตถุดิบใหม่</h3>
            <button class="popup-close-btn" onclick="closePopup('addMaterialPopup')">&times;</button>
        </div>
        
        <form id="addMaterialForm" novalidate>
            <div id="material-rows-container">
                <!-- แถวสำหรับกรอกข้อมูลวัตถุดิบ (สามารถเพิ่มได้ด้วย JavaScript) -->
                <div class="material-form-row">
                    <div class="material-form-grid">
                        
                        <!-- แถวที่ 1: ชื่อ, จำนวน, หน่วย -->
                        <div class="form-item span-2">
                            <label for="material_name_0">ชื่อวัตถุดิบ</label>
                            <input type="text" id="material_name_0" name="material_name[]" placeholder="เช่น แป้งสาลี, น้ำตาล" required>
                        </div>
                        <div class="form-item">
                            <label for="material_quantity_0">จำนวน</label>
                            <input type="number" id="material_quantity_0" name="material_quantity[]" placeholder="0" required>
                        </div>
<div class="form-item">
    <label for="Unit_id_0">หน่วย</label>
    <select id="Unit_id_0" name="Unit_id[]" required>
        <option value="" disabled selected>เลือกหน่วย</option>
        <?php foreach ($units_options as $unit): ?>
            <option value="<?php echo htmlspecialchars($unit['Unit_id']); ?>">
                <?php echo htmlspecialchars($unit['Unit_name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

                        <!-- แถวที่ 2: สต็อก, วันหมดอายุ -->
                         <div class="form-item">
                            <label for="min_stock_0">จำนวนขั้นต่ำ</label>
                            <input type="number" id="min_stock_0" name="min_stock[]" placeholder="0">
                        </div>
                        <div class="form-item">
                           <label for="max_stock_0">จำนวนสูงสุด</label>
                           <input type="number" id="max_stock_0" name="max_stock[]" placeholder="0">
                        </div>
                        <div class="form-item span-2 date-container">
                            <label for="expiry_date_0">วันหมดอายุ (ถ้ามี)</label>
                            <input type="text" class="thai-buddhist-date-picker" id="expiry_date_0" name="expiry_date[]" title="วันหมดอายุ" placeholder="คลิกเพื่อเลือกวันที่">
                        </div>
                        
                        <!-- แถวที่ 3: ผู้จำหน่าย -->
                        <div class="form-item span-4">
                             <label for="supplier_0">ผู้จำหน่าย (ถ้ามี)</label>
                             <input type="text" id="supplier_0" name="supplier[]" placeholder="เช่น แม็คโคร, โลตัส">
                        </div>

                        <!-- แถวที่ 4: ประเภท -->
                        <div class="form-item span-4 category-wrapper">
                            <label>เลือกประเภทวัตถุดิบ (เลือกได้มากกว่า 1)</label>
                            <div class="category-checkboxes-grid">
                                <?php
                                $category_sql = "SELECT cate_id, cate_name FROM categories WHERE cate_id IN (7, 8, 9, 10) ORDER BY FIELD(cate_id, 7, 8, 9, 10)";
                                $category_result = mysqli_query($conn, $category_sql);
                                while ($category_row = mysqli_fetch_assoc($category_result)) {
                                    echo "<label class='category-checkbox'><input type='checkbox' name='mate_category[0][]' value='{$category_row['cate_id']}'> {$category_row['cate_name']}</label>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-remove-row" style="display:none;" title="ลบรายการนี้">×</button>
                </div>
            </div>

            <div class="add-row-container">
                <button type="button" id="add-material-row-btn" class="btn-add-row">
                    <i class="fas fa-plus"></i> เพิ่มวัตถุดิบอีกรายการ
                </button>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-cancel" onclick="closePopup('addMaterialPopup')">ยกเลิก</button>
                <button type="submit" class="btn btn-save">บันทึก</button>
            </div>
        </form>
    </div>
</div>

    <!-- Popup: ยืนยันการลบวัตถุดิบ -->
    <div id="deleteMaterialPopup" class="popup-overlay" style="display:none;">
      <div class="popup">
        <h3>ยืนยันการดำเนินการ</h3>
        <p>คุณแน่ใจหรือไม่ว่าต้องการลบวัตถุดิบนี้?</p>
        <form id="deleteMaterialForm" action="delete_material.php" method="POST">
          <input type="hidden" name="material_id" id="deleteMaterialID">
          <div class="popup-actions">
          <button type="submit" class="btn btn-delete">ยืนยัน</button>
          <button type="button" class="btn btn-cancel" onclick="closePopup('deleteMaterialPopup')">ยกเลิก</button>
        </div>
      </form>
      </div>
    </div>

    <!-- Popup: แก้ไขวัตถุดิบ -->
<div id="editMaterialPopup" class="popup-overlay" style="display: none;">
    <div class="popup">
        <div class="popup-header">
            <h3 class="popup-title"><i class="fas fa-edit"></i> แก้ไขข้อมูลวัตถุดิบ</h3>
            <button class="popup-close-btn" onclick="closePopup('editMaterialPopup')">&times;</button>
        </div>
        
        <form id="editMaterialForm" novalidate>
            <!-- Hidden input สำหรับเก็บ ID ของวัตถุดิบที่กำลังแก้ไข -->
            <input type="hidden" name="material_id" id="edit_material_id">

            <!-- โครงสร้างเหมือนฟอร์มเพิ่มข้อมูล แต่มีเพียงแถวเดียว -->
            <div class="material-form-grid">
                
                <!-- แถวที่ 1: ชื่อ, จำนวน, หน่วย -->
                <div class="form-item span-2">
                    <label for="edit_material_name">ชื่อวัตถุดิบ</label>
                    <input type="text" id="edit_material_name" name="material_name" required>
                </div>
                <div class="form-item">
                    <label for="edit_material_quantity">จำนวน</label>
                    <input type="number" id="edit_material_quantity" name="material_quantity" required>
                </div>
<div class="form-item">
    <label for="edit_Unit_id">หน่วย</label>
    <select id="edit_Unit_id" name="Unit_id" required>
        <option value="" disabled>เลือกหน่วย</option>
        <?php foreach ($units_options as $unit): ?>
            <option value="<?php echo htmlspecialchars($unit['Unit_id']); ?>">
                <?php echo htmlspecialchars($unit['Unit_name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

                <!-- แถวที่ 2: สต็อก, วันหมดอายุ -->
                 <div class="form-item">
                    <label for="edit_min_stock">จำนวนขั้นต่ำ</label>
                    <input type="number" id="edit_min_stock" name="min_stock">
                </div>
                <div class="form-item">
                   <label for="edit_max_stock">จำนวนสูงสุด</label>
                   <input type="number" id="edit_max_stock" name="max_stock">
                </div>
                <div class="form-item span-2 date-container">
                    <label for="edit_expiry_date">วันหมดอายุ (ถ้ามี)</label>
                    <input type="text" class="thai-buddhist-date-picker" id="edit_expiry_date" name="expiry_date" placeholder="คลิกเพื่อเลือกวันที่">
                </div>
                
                <!-- แถวที่ 3: ผู้จำหน่าย -->
                <div class="form-item span-4">
                     <label for="edit_supplier">ผู้จำหน่าย (ถ้ามี)</label>
                     <input type="text" id="edit_supplier" name="supplier" placeholder="เช่น แม็คโคร, โลตัส">
                </div>

                <!-- แถวที่ 4: ประเภท -->
                <div class="form-item span-4 category-wrapper">
                    <label>เลือกประเภทวัตถุดิบ (เลือกได้มากกว่า 1)</label>
                    <div id="edit_category_checkboxes" class="category-checkboxes-grid">
                        <?php
                        // ใช้ PHP วนลูปสร้าง Checkbox เหมือนเดิม
                        $category_sql = "SELECT cate_id, cate_name FROM categories WHERE cate_id IN (7, 8, 9, 10) ORDER BY FIELD(cate_id, 7, 8, 9, 10)";
                        $category_result = mysqli_query($conn, $category_sql);
                        while ($category_row = mysqli_fetch_assoc($category_result)) {
                            // สังเกตว่า name="mate_category[]" ไม่มีเลข index
                            echo "<label class='category-checkbox'><input type='checkbox' name='mate_category[]' value='{$category_row['cate_id']}'> {$category_row['cate_name']}</label>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-cancel" onclick="closePopup('editMaterialPopup')">ยกเลิก</button>
                <button type="submit" class="btn btn-save">บันทึก</button>
            </div>
        </form>
    </div>
</div>

    </div>  
<div id="tab-categories-content" style="display:none;">
    <!-- ===== ส่วนจัดการประเภท (เหมือนเดิม) ===== -->
    <div class="im-cate-header">
        <h2>จัดการประเภท</h2>
        <button class="im-add-btn" onclick="openCategoryModal()">
            <i class="fas fa-plus"></i> เพิ่มประเภท
        </button>
    </div>
    <div id="categories-grid" class="im-categories-grid">
        <!-- ข้อมูลประเภทจะถูกสร้างโดย JavaScript ที่นี่ -->
    </div>

    <!-- ===== ส่วนจัดการหน่วยวัด (ส่วนที่เพิ่มใหม่) ===== -->
<div class="im-cate-header" style="margin-top: 48px;">
        <h2>จัดการหน่วยวัด</h2>
        <button class="im-add-btn" onclick="openUnitModal()">
            <i class="fas fa-plus"></i> เพิ่มหน่วยวัด
        </button>
    </div>
    <!-- ใช้โครงสร้างตารางเหมือนเดิม -->
<div class="im-table-wrapper sub-table">
    <table class="im-table">
        <thead>
            <tr>
                <th style="width: 60px;">ลำดับ</th>
                <th>ชื่อหน่วยวัด</th>
                <!-- <th>สูตรการแปลง</th> << เพิ่มคอลัมน์นี้เข้ามา -->
                <th style="width: 150px; text-align: center;">การดำเนินการ</th>
            </tr>
        </thead>
        <tbody id="units-table-body">
            <!-- ข้อมูลหน่วยวัดจะถูกสร้างโดย JavaScript ที่นี่ -->
        </tbody>
    </table>
</div>

    <!-- Modal: เพิ่ม/แก้ไขประเภท -->
<div id="categoryModal" class="popup-overlay" style="display:none;">
    <div class="popup" style="max-width:500px;">
        <div class="popup-header">
            <h3 id="categoryModalTitle" class="popup-title">เพิ่มประเภทวัตถุดิบ</h3>
            <button class="popup-close-btn" onclick="closeCategoryModal()">&times;</button>
        </div>
        
        <form id="categoryForm" autocomplete="off">
            <!-- START: แก้ไขโครงสร้างฟอร์ม -->
             <input type="hidden" name="cate_id" id="cate_id_hidden">
            <div id="category-rows-container">
                <!-- แถวสำหรับกรอกข้อมูล (Template) -->
                <div class="category-form-row">
                    <div class="form-item">
                        <label for="cate_name_0">ชื่อประเภท</label>
                        <input type="text" name="cate_name[]" id="cate_name_0" placeholder="เช่น ผักสด, เครื่องเทศ" required>
                    </div>
                    <button type="button" class="btn-remove-row" style="display:none;" title="ลบรายการนี้">×</button>
                </div>
            </div>

            <div class="add-row-container">
                <button type="button" id="add-category-row-btn" class="btn-add-row">
                    <i class="fas fa-plus"></i> เพิ่มประเภทอีกรายการ
                </button>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-cancel" onclick="closeCategoryModal()">ยกเลิก</button>
                <button type="submit" class="btn btn-save">บันทึก</button>
            </div>
            <!-- END: แก้ไขโครงสร้างฟอร์ม -->
        </form>
    </div>
</div>


<div id="unitModal" class="popup-overlay" style="display:none;">
  <div class="popup" style="max-width:400px;">
    <h3 id="unitModalTitle">เพิ่มหน่วยวัดใหม่</h3>
    <form id="unitForm" autocomplete="off">
      <input type="hidden" name="unit_id" id="unitId">
      
      <label for="unitName">ชื่อหน่วยวัด</label>
      <input type="text" name="unit_name" id="unitName" placeholder="เช่น กรัม, ช้อนชา, ฟอง" required><br><br>

      <label for="baseUnit">หน่วยย่อยที่สุด (Base Unit)</label>
      <input type="text" name="base_unit" id="baseUnit" placeholder="เช่น กรัม, มล., ชิ้น, ฟอง" required>
      
      <!-- ===== START: เพิ่มข้อความแนะนำ ===== -->
      <p class="input-helper-text">
        * ลองพิมพ์หน่วยที่รู้จัก (เช่น กิโลกรัม, กรัม) แล้วระบบจะคำนวณตัวคูณให้
      </p>
      <!-- ===== END: เพิ่มข้อความแนะนำ ===== -->

      <label for="conversionFactor">ตัวคูณเพื่อแปลงเป็นหน่วยย่อย</label>
      <input type="number" step="0.0001" name="conversion_factor" id="conversionFactor" placeholder="ระบบจะคำนวณให้ หรือกรอกเอง" required><br><br>

      <!-- ===== START: เพิ่มส่วนแสดงผลสูตรตรงนี้ ===== -->
      <div id="formula-preview" class="formula-preview-box">
          กรอกข้อมูลเพื่อดูตัวอย่างสูตรการแปลง
      </div>
      <!-- ===== END: เพิ่มส่วนแสดงผลสูตร ===== -->
      
<div class="form-actions">
  <button type="button" class="btn btn-cancel" onclick="closePopup('unitModal')">ยกเลิก</button>
  <button type="submit" class="btn btn-save">บันทึก</button>
</div>
    </form>
  </div>
</div>

</div>
<div id="toast" class="toast-message" style="display: none;"></div>

    <!-- START: เพิ่มโค้ด 3 บรรทัดสำหรับปฏิทินไทย -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>
    <!-- END: เพิ่มโค้ด 3 บรรทัดสำหรับปฏิทินไทย -->
    <script src="../js/material.js?v=<?= time() ?>"></script>

<!-- START: โค้ดที่แก้ไขสำหรับปฏิทิน -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. ตั้งค่าภาษาไทยเป็นค่าเริ่มต้น
    flatpickr.localize(flatpickr.l10ns.th);

    /**
     * ฟังก์ชันสำหรับ "บังคับ" ให้ปีในหัวปฏิทิน (Header) แสดงเป็น พ.ศ.
     * @param {object} instance - The flatpickr instance.
     */
    const forceBuddhistYearInHeader = (instance) => {
        // ใช้ setTimeout เพื่อให้แน่ใจว่า DOM ของปฏิทินถูกสร้างเสร็จแล้วจริงๆ
        setTimeout(() => {
            // ค้นหา element ของปีในหัวปฏิทินโดยตรงผ่าน class
            const yearInput = instance.calendarContainer.querySelector('.numInput.cur-year');
            if (yearInput) {
                const gregorianYear = parseInt(yearInput.value, 10);
                
                // แปลงค่าก็ต่อเมื่อปียังเป็น ค.ศ. (น้อยกว่า 2500) เพื่อป้องกันการบวกซ้ำ
                if (gregorianYear < 2500) {
                    yearInput.value = gregorianYear + 543;
                }
            }
        }, 1); // Delay เพียง 1ms ก็เพียงพอ
    };

    // 2. สร้าง Config กลางสำหรับ DatePicker
    const datePickerConfig = {
        static: true,
        locale: "th",
        altInput: true,
        altFormat: "j F Y",      // รูปแบบที่แสดงในช่อง input (เป็น พ.ศ.)
        dateFormat: "Y-m-d",   // รูปแบบค่าที่ส่งไป Server (ต้องเป็น ค.ศ.)
        
        // ฟังก์ชันจัดรูปแบบวันที่ใน "ช่อง input"
        formatDate: (date, format) => {
            if (format === "j F Y") {
                const day = date.getDate();
                const month = flatpickr.l10ns.th.months.longhand[date.getMonth()];
                const year = date.getFullYear() + 543;
                return `${day} ${month} ${year}`;
            }
            return flatpickr.formatDate(date, format);
        },

        // 3. ใช้ Event Hooks ทั้งหมดที่เกี่ยวข้อง เพื่อให้แน่ใจว่าปีเป็น พ.ศ. เสมอ
        onReady: (selectedDates, dateStr, instance) => forceBuddhistYearInHeader(instance),
        onOpen: (selectedDates, dateStr, instance) => forceBuddhistYearInHeader(instance),
        onMonthChange: (selectedDates, dateStr, instance) => forceBuddhistYearInHeader(instance),
        onYearChange: (selectedDates, dateStr, instance) => forceBuddhistYearInHeader(instance)
    };
    
    // 4. สร้างฟังก์ชัน Global สำหรับให้ material.js เรียกใช้
    window.initializeDatepickers = function() {
        // ทำลาย instance เก่าก่อนทุกครั้งที่ถูกเรียก เพื่อป้องกันข้อผิดพลาด
        const existingPickers = document.querySelectorAll(".thai-buddhist-date-picker.flatpickr-input");
        existingPickers.forEach(picker => {
            if (picker._flatpickr) {
                picker._flatpickr.destroy();
            }
        });
        
        // สร้าง instance ใหม่ด้วย config ที่ถูกต้อง
        flatpickr(".thai-buddhist-date-picker", datePickerConfig);
    };
    
    // หมายเหตุ: เราจะไม่เรียก initializeDatepickers() ที่นี่
    // เพราะ material.js จะเป็นผู้เรียกใช้หลังจากโหลดข้อมูลเสร็จแล้ว
});
</script>
<!-- END: โค้ดที่แก้ไขสำหรับปฏิทิน -->
</body>
</html>