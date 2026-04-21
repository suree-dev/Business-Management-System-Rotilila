<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = strtolower($_SESSION['role'] ?? '');
include('../data/db_connect.php');
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ระบบจัดการร้าน - จัดการประเภทอาหาร</title>
<link rel="stylesheet" href="../style/menu.css?v=932">
<!-- font awesome ยังเอาไว้เผื่อปุ่มแก้ไขลบ -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include('../layout/navbar.php'); ?>
<?php include('../layout/sidebar.php'); ?>

<div class="main-content">
    <h2>จัดการประเภทอาหาร</h2>

    <?php
    // ดึงวันที่ updated_at ล่าสุดจากเมนู (ถ้ามี)
    $latest_updated_at_result = mysqli_query($conn, "SELECT updated_at FROM menu ORDER BY updated_at DESC LIMIT 1");
    $latest_updated_at_row = mysqli_fetch_assoc($latest_updated_at_result);
    $latest_updated_at = $latest_updated_at_row['updated_at'] ?? 'ไม่มีข้อมูล';
    ?>

    <div class="button-container" style="display: flex; align-items: center; gap: 15px;">
        <div style="font-weight: lighter;">
            อัปเดตล่าสุดเมื่อ: <?= date('d-m-Y H:i', strtotime($latest_updated_at)) ?>
        </div>
        <button class="btn btn-add" onclick="openPopup('addPopup')">+ เพิ่มประเภทอาหาร</button>
    </div>

    <!-- POPUP: เพิ่มประเภทอาหาร -->
    <div id="addPopup" class="popup-overlay" style="display: none;">
        <div class="popup">
            <h3>เพิ่มประเภทอาหารใหม่</h3>
            <form id="addCategoryForm" novalidate>
                <input type="text" id="menu_name" name="menu_name" placeholder="ชื่ออาหาร" required><br><br>
                <input type="text" id="cate_name" name="cate_name" placeholder="ชื่อประเภทอาหาร" required><br><br>
                <button type="submit" class="btn btn-save">บันทึก</button>
                <button type="button" class="btn btn-cancel" onclick="closePopup('addPopup')">ยกเลิก</button>
            </form>
        </div>
    </div>

    <!-- POPUP: แก้ไขประเภทอาหาร -->
    <div id="editPopup" class="popup-overlay" style="display:none;">
        <div class="popup">
            <h3>แก้ไขประเภทอาหาร</h3>
            <form id="editCategoryForm" novalidate>
                <input type="hidden" name="id" id="editCategoryID">
                <input type="text" name="menu_name" id="editMenuName" placeholder="ชื่ออาหาร" required><br><br>
                <input type="text" name="cate_name" id="editCateName" placeholder="ชื่อประเภทอาหาร" required><br><br>
                <button type="submit" class="btn btn-save">บันทึก</button>
                <button type="button" class="btn btn-cancel" onclick="closePopup('editPopup')">ยกเลิก</button>
            </form>
        </div>
    </div>

    <!-- POPUP: ยืนยันการลบประเภทอาหาร -->
    <div id="deletePopup" class="popup-overlay" style="display:none;">
        <div class="popup">
            <h3>คุณแน่ใจหรือไม่ว่าต้องการลบประเภทอาหารนี้?</h3>
            <form id="deleteCategoryForm" action="delete_category.php" method="POST">
                <input type="hidden" name="id" id="deleteCategoryID">
                <button type="submit" class="btn btn-delete">ยืนยัน</button>
                <button type="button" class="btn btn-cancel" onclick="closePopup('deletePopup')">ยกเลิก</button>
            </form>
        </div>
    </div>

    <div id="toast" class="toast-message" style="display: none;"></div>

    <!-- ตารางประเภทอาหาร -->
    <table class="menu-table">
    <thead>
        <tr>
            <th>ลำดับ</th>
            <th>ชื่อเมนู</th>
            <th>ประเภท</th>
            <th>แก้ไข</th>
            <th>ลบ</th>
        </tr>
    </thead>
    <tbody>

    <?php
    $sql = "SELECT id, menu_name, cate_name FROM menu_categories ORDER BY cate_name, menu_name";
    $result = mysqli_query($conn, $sql);
    $index = 1;

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>{$index}</td>";
            echo "<td>" . htmlspecialchars($row['menu_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['cate_name']) . "</td>";
            echo "<td>
                <button class='btn-icon' onclick=\"openEditPopup({$row['id']}, '".htmlspecialchars(addslashes($row['cate_name']))."')\">
                    <i class='fas fa-edit'></i>
                </button>
            </td>";
            echo "<td>
                <button class='btn-icon delete' onclick=\"openDeletePopup({$row['id']})\">
                    <i class='fas fa-trash-alt'></i>
                </button>
            </td>";
            echo "</tr>";
            $index++;
        }
    } else {
        echo "<tr><td colspan='5'>ไม่มีข้อมูลประเภทอาหาร</td></tr>";
    }
    ?>
    </tbody>
    </table>

</div>

<script>
function openPopup(id) {
    document.getElementById(id).style.display = 'flex';
}
function closePopup(id) {
    document.getElementById(id).style.display = 'none';
}
function openEditPopup(id, cateName) {
    openPopup('editPopup');
    document.getElementById('editCategoryID').value = id;
    document.getElementById('editCateName').value = cateName;
}
function openDeletePopup(id) {
    openPopup('deletePopup');
    document.getElementById('deleteCategoryID').value = id;
}
</script>

    <script src="../js/category.js?v=82"></script>

<div style="height: 1000px;"></div>
</body>
</html>
