<?php 
// --- ADDED: START - QR Status Check (The Security Guard) ---
// ส่วนนี้คือ "ยาม" ที่จะตรวจสอบสถานะโต๊ะก่อนแสดงเมนู
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../data/db_connect.php'); // **สำคัญ:** ตรวจสอบว่า path นี้ถูกต้อง

// 1. รับหมายเลขโต๊ะจาก URL และป้องกัน Injection
if (!isset($_GET['table']) || !ctype_alnum(str_replace('-', '', $_GET['table']))) { // ตรวจสอบให้เข้มงวดขึ้น
    // ถ้าไม่มี table number ในลิงก์ หรือมีอักขระแปลกปลอม
    echo "<!DOCTYPE html><html lang='th'><head><title>ผิดพลาด</title><link href='https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;700&display=swap' rel='stylesheet'><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'></head><body style='text-align: center; padding: 50px; font-family: Kanit, sans-serif;'>";
    echo "<h1><i class='fas fa-exclamation-triangle' style='color: #ffc107;'></i> ไม่พบหมายเลขโต๊ะ</h1><p style='font-size: 1.2rem; color: #6c757d;'>กรุณาสแกน QR Code ที่โต๊ะของท่านอีกครั้ง</p>";
    echo "</body></html>";
    exit(); // หยุดการทำงานทันที
}
$table_number = $_GET['table']; // รับค่าหลังจากตรวจสอบแล้ว

// 2. ตรวจสอบสถานะของโต๊ะนี้ในฐานข้อมูล
$stmt = $conn->prepare("SELECT qr_status FROM tables WHERE table_num = ?");
$stmt->bind_param('s', $table_number);
$stmt->execute();
$result = $stmt->get_result();
$table_data = $result->fetch_assoc();
$stmt->close();

// 3. ตรวจสอบเงื่อนไข
// ถ้า "ไม่เจอโต๊ะนี้ในระบบ" หรือ "สถานะไม่ใช่ active"
if (!$table_data || $table_data['qr_status'] !== 'active') {
    // แสดงข้อความแจ้งเตือน และหยุดการทำงานของหน้าเว็บทันที
    echo "<!DOCTYPE html><html lang='th'><head><title>โต๊ะยังไม่เปิดให้บริการ</title><link href='https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;700&display=swap' rel='stylesheet'><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'></head><body style='text-align: center; padding: 50px; font-family: Kanit, sans-serif;'>";
    echo "<div style='max-width: 500px; margin: auto;'>";
    echo "<h1><i class='fas fa-times-circle' style='color: #dc3545; font-size: 4rem; margin-bottom: 1rem;'></i></h1>";
    echo "<h1 style='font-size: 1.8rem;'>โต๊ะนี้ยังไม่เปิดให้บริการ</h1>";
    echo "<p style='font-size: 1.2rem; color: #6c757d;'>โปรดติดต่อพนักงานเพื่อเปิดใช้งานโต๊ะของท่าน</p>";
    echo "</div>";
    echo "</body></html>";
    exit(); // คำสั่งนี้สำคัญที่สุด คือการหยุดไม่ให้โค้ดส่วนที่เหลือแสดงผล
}
// ถ้าโต๊ะเปิดใช้งานอยู่ (active) โค้ดจะทำงานต่อไปยังส่วนแสดงผลเมนูตามปกติ...
// --- ADDED: END ---


function can_make_recipe($conn, $recipe_id, $menu_name = '') { // Added menu_name for better error logging
    if (empty($recipe_id)) return true;
    $sql = "SELECT i.ingr_quantity, m.base_quantity FROM ingredient i JOIN material m ON i.material_id = m.material_id WHERE i.Recipes_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error); return false; }
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) { $stmt->close(); return true; }
    while ($row = $result->fetch_assoc()) {
        if ($row['ingr_quantity'] > $row['base_quantity']) { // Changed material_quantity to base_quantity
            $stmt->close(); 
            return false; 
        }
    }
    $stmt->close();
    return true;
}

// --- ดึงข้อมูลเมนูและหมวดหมู่ ---
$categories = [];
$menus_by_category = [];
$sql_all = "SELECT m.*, c.cate_name, c.cate_id FROM menu m JOIN menu_categories mc ON m.menu_ID = mc.menu_ID JOIN categories c ON mc.cate_ID = c.cate_id WHERE m.visible = 1 ORDER BY c.cate_id, m.menu_ID";
$result_all = mysqli_query($conn, $sql_all);
if ($result_all) {
    while($row = mysqli_fetch_assoc($result_all)){
        if (!isset($menus_by_category[$row['cate_name']])) {
            $menus_by_category[$row['cate_name']] = [];
            $categories[] = ['name' => $row['cate_name'], 'id' => 'cat-' . $row['cate_id']];
        }
        $menus_by_category[$row['cate_name']][] = $row;
    }
}
// ไม่ต้องดึง table_number อีกแล้ว เพราะเราดึงไว้ข้างบนแล้ว
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta charset="UTF-8">
    <title>ร้านโรตีลีลา</title>
    <link rel="stylesheet" href="../style/customer.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="mobile-container">
    <header class="main-header">
        <i class="bi bi-arrow-left header-back-btn" id="header-back-btn" style="display: none;"></i>
        
        <div class="header-content" id="header-content">
            <div class="table-info">
                 <i class="bi bi-geo-alt-fill"></i>
                <span id="table-name">โต๊ะ <?= htmlspecialchars($table_number) ?></span>
            </div>
            <div class="header-icons">
                <i class="bi bi-search" id="search-icon"></i>
                <i class="bi bi-cart3" id="open-cart-btn"></i>
                <span id="cart-count-indicator" class="cart-count-indicator"></span>
            </div>
        </div>
        
        <div class="header-title" id="header-title" style="display: none;">ตะกร้าของฉัน</div>

        <div class="search-bar-container" id="search-bar-container" style="display: none;">
            <input type="text" id="searchInput" placeholder="ค้นหาเมนู..." autocomplete="off">
            <i class="bi bi-x-lg" id="close-search-btn"></i>
        </div>
    </header>

    <main id="main-content">
        <div id="menu-view">
            <div class="restaurant-banner">
                <img src="../picture/banner.jpg" alt="Restaurant Banner" class="banner-image">
                <div class="restaurant-info">
                    <div class="logo-wrapper"> <img src="../picture/logo.png" alt="Logo" class="restaurant-logo"> </div>
                    <h1>ร้านโรตีลีลา</h1>
                </div>
            </div>

            <nav class="menu-categories" id="menu-categories-nav">
                <?php foreach ($categories as $index => $category): ?>
                    <a href="#<?= $category['id'] ?>" class="<?= $index === 0 ? 'active' : '' ?>"><?= htmlspecialchars($category['name']) ?></a>
                <?php endforeach; ?>
            </nav>
            
            <div class="menu-list-container">
                <?php foreach ($menus_by_category as $category_name => $menus): ?>
                    <?php 
                        $cat_id = '';
                        foreach($categories as $cat) { if ($cat['name'] === $category_name) { $cat_id = $cat['id']; break; } }
                    ?>
                    <section id="<?= $cat_id ?>" class="menu-category-section">
                        <h2 class="category-title"><?= htmlspecialchars($category_name) ?></h2>
                        <div class="menu-list">
                            <?php foreach ($menus as $row): ?>
                                <?php
                                    $id = $row['menu_ID'];
                                    $name = htmlspecialchars($row['menu_name']);
                                    $price = htmlspecialchars($row['menu_price']);
                                    $image = '../' . htmlspecialchars(ltrim($row['menu_image'], './')); // แก้ไข path รูปภาพ
                                    $is_available = can_make_recipe($conn, $row['Recipes_id'] ?? null, $name);
                                    $card_class = $is_available ? 'menu-item' : 'menu-item disabled';
                                    $onclick_action = $is_available ? "addItemToCart(this, '$id', '$name', '$price', '$image')" : "";
                                ?>
                                <div class="<?= $card_class ?>" data-id="<?= $id ?>" onclick="<?= $onclick_action ?>">
                                    <img src="<?= $image ?>" alt="<?= $name ?>" class="menu-item-image">
                                    <div class="menu-item-details">
                                        <h3 class="menu-item-name"><?= $name ?></h3>
                                        <p class="menu-item-price"><?= $price ?> บาท</p>
                                    </div>
                                    <div class="menu-item-add">
                                        <span class="quantity-in-cart" style="display: none;">0</span>
                                    </div>
                                    <?php if (!$is_available): ?>
                                        <div class="out-of-stock-overlay"><span>หมด</span></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="cart-view" style="display: none;">
            <div class="cart-items-list" id="cart-items-list">
                <!-- รายการในตะกร้าจะถูกสร้างโดย JS -->
            </div>

            <div class="cart-page-footer">
                <div class="cart-summary">
                    <span>รวมทั้งหมด</span>
                    <span class="total-price" id="cart-page-total">0.00 บาท</span>
                </div>
                <button class="checkout-btn" id="confirm-order-btn">ยืนยันรายการ</button>
                <button class="cancel-order-btn" id="cancel-order-btn" style="display: none;">ยกเลิกออเดอร์</button>
            </div>
        </div>
    </main>

    <footer class="cart-bottom-bar" id="cart-bottom-bar" style="display: none;">
        <div class="cart-summary-info">
             <i class="bi bi-cart3"></i>
            <span id="bottom-bar-item-count">0 รายการในตะกร้า</span>
        </div>
        <div class="cart-summary-total">
            <span id="bottom-bar-total">0 บาท</span>
        </div>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/customer.js?v=<?= time() ?>"></script>

</body>
</html>