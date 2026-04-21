<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
// ตรวจสอบสถานะ session และเริ่ม session ถ้ายังไม่ได้เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = strtolower($_SESSION['role'] ?? '');

include('../data/db_connect.php');

// ✅ เช็กว่ามี owner แล้วหรือยัง
$ownerExists = false;
$checkOwner = mysqli_query($conn, "SELECT user_ID FROM users WHERE role = 'owner'");
if (mysqli_num_rows($checkOwner) > 0) {
    $ownerExists = true;
}

// ดึง role ของ user ที่กำลังแก้ไข
$editingUserRole = '';
if (isset($_GET['user_ID'])) {
    $editID = intval($_GET['user_ID']);
    $query = mysqli_query($conn, "SELECT role FROM users WHERE user_ID = $editID");
    if ($row = mysqli_fetch_assoc($query)) {
        $editingUserRole = $row['role'];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="UTF-8">
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
<title>จัดการพนักงาน - ร้านโรตีลีลา</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../style/employee.css?v=<?= time() ?>">
<link rel="stylesheet" href="../style/navnsidebar.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>

<?php include('../layout/navbar.php'); ?>
<?php include('../layout/sidebar.php'); ?>



<div class="main-content">
    <div class="ingredient-manager-container"> <!-- ใช้ Container ใหม่ -->
    <!-- Header -->
    <div class="im-header">
        <div class="im-header-left">
            <div class="im-header-icon-wrapper" style="color: #8e44ad; background-color: #f4ecf7;">
                <i class="fas fa-users-cog"></i>
            </div>
            <div>
                <h1 class="im-title">จัดการข้อมูลพนักงาน</h1>
                <p class="im-desc">เพิ่ม ลบ และแก้ไขข้อมูลพนักงานในระบบ</p>
            </div>
        </div>
    </div>

    <!-- Filter & Action Bar -->
    <div class="im-filter-bar">
        <div class="im-search-box">
             <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="ค้นหาชื่อ, ตำแหน่ง, เบอร์โทร...">
        </div>
        <button class="im-add-btn" onclick="openPopup('addPopup')"><i class="fas fa-plus"></i> เพิ่มพนักงาน</button>
    </div>

        <!-- ตารางพนักงาน -->
        <table class="employee-table">
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>username</th>
                    <th>ชื่อ - นามสกุล</th>
                    <th>ตำแหน่ง</th>
                    <th>เบอร์โทร</th>
                    <th>อีเมล</th>
                    <th>ดำเนินการ</th>
                </tr>
            </thead>
            <tbody id="employeeTableBody">
                <!-- ข้อมูลจะถูกโหลดมาที่นี่โดย JavaScript -->
            </tbody>
        </table>

        <!-- ส่วนของ Pagination -->
        <div class="pagination-container">
            <div class="items-per-page-selector">
                <label for="itemsPerPage">แสดง:</label>
                <select id="itemsPerPage">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                </select>
                <label>รายการ</label>
            </div>
            <div id="pagination-controls" class="pagination-controls">
                <!-- ปุ่ม Pagination จะถูกสร้างโดย JavaScript -->
            </div>
        </div>
    </div>
</div>


<!-- POPUP: เพิ่มพนักงาน -->
<div id="addPopup" class="popup-overlay" style="display: none;">
  <div class="popup">
    <h3 style="margin-bottom: 20px;">เพิ่มพนักงานใหม่</h3>
    <form id="addEmployeeForm">

      <p id="generatedUsername" class="readonly-username" >ชื่อผู้ใช้</p>
      <input type="hidden" id="username" name="username">

      <input type="text" id="full_name" name="full_name" oninput="checkFullName(); generatePassword();" placeholder="ชื่อ - นามสกุล"><br><br>
      <div id="full_name-error" class="error-message"></div>

      <input type="text" id="phone" name="phone" oninput="checkPhone(); generatePassword();" placeholder="เบอร์โทรศัพท์"><br><br>
      <div id="phone-error" class="error-message"></div>

      <input type="email" id="email" name="email" oninput="checkEmail()" placeholder="อีเมล"><br><br>
      <div id="email-error" class="error-message"></div>
      
      <select id="role" name="role" oninput="validateRole()" >
                <option value="" disabled selected hidden>-- ตำแหน่ง --</option>
                <option value="sales">sales</option>
                <option value="accountant">accountant</option>
                <option value="manager">manager</option>
                <?php if (!$ownerExists): ?>
            <option value="owner">owner</option>
                <?php endif; ?>
      </select>
      <div id="role-error" class="error-message"></div>

<div class="password-container">
  <input type="password" id="password" name="password_hash" placeholder="รหัสผ่าน" readonly>
  <button type="button" id="togglePasswordBtn" onclick="togglePassword()">
    <i class="fas fa-eye-slash"></i>
  </button>
</div>
    <button type="button" id="resetButton" class="btn btn-cancel" onclick="closePopup('addPopup')">ยกเลิก</button>
    <button type="submit" class="btn btn-save">บันทึก</button>
    </form>
  </div>
</div>

<!-- POPUP: แก้ไขพนักงาน -->
<div id="editPopup" class="popup-overlay" style="display:none;">
  <div class="popup">
    <h3>แก้ไขข้อมูลพนักงาน</h3>
    <form id="editEmployeeForm" novalidate>
      <input type="hidden" name="user_ID" id="editUserID">

      <input type="text" name="username" id="editUsername" oninput="checkEditUsername()" placeholder="Username" readonly><br><br>
      <div id="edit-username-error" class="error-message"></div>

      <input type="text" name="full_name" id="editFullName" oninput="checkEditFullName()" placeholder="ชื่อ - นามสกุล"><br><br>
      <div id="edit-full_name-error" class="error-message"></div> 

      <input type="text" name="phone" id="editPhone" oninput="checkEditPhone()" placeholder="เบอร์โทรศัพท์"><br><br>
      <div id="edit-phone-error" class="error-message"></div>

      <input type="email" name="email" id="editEmail" oninput="checkEditEmail()" placeholder="อีเมล" readonly><br><br>
      <div id="edit-email-error" class="error-message"></div>

      <select id="editRole" name="role" oninput="validateEditRole()">
                <option value="" disabled selected hidden>-- ตำแหน่ง --</option>
                <option value="sales">sales</option>
                <option value="accountant">accountant</option>
                <option value="manager">manager</option>
                <?php // แก้ไข: ถ้าผู้ใช้เป็น admin จะไม่เห็นตัวเลือก owner เลย
                if ($role !== 'admin'): ?>
                <option value="owner">owner</option> 
                <?php endif; ?>
      </select>
      <div id="edit-role-error" class="error-message"></div>
    <button type="button" class="btn btn-cancel" onclick="closePopup('editPopup')">ยกเลิก</button>
    <button type="submit" class="btn btn-save">บันทึก</button>
      
    </form>
  </div>
</div>

<!-- POPUP: ยืนยันการลบ -->
<div id="deletePopup" class="popup-overlay" style="display:none;">
  <div class="popup">
    <h3>คุณแน่ใจหรือไม่ว่าต้องการลบพนักงานนี้?</h3>
    <form id="deleteEmployeeForm" action="delete_employee.php" method="POST">
      <input type="hidden" name="user_ID" id="deleteUserID">
      <button type="submit" class="btn btn-delete">ยืนยัน</button>
      <button type="button" class="btn btn-cancel" onclick="closePopup('deletePopup')">ยกเลิก</button>
    </form>
  </div>
</div>

<div id="toast" class="toast-message" style="display: none;"></div>

<script>
    const LOGGED_IN_USER_ROLE = '<?php echo $role; ?>';
</script>
<script src="../js/employee.js?v=<?= time() ?>"></script>

</body>
</html>