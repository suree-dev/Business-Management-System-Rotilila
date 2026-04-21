<?php 
// โค้ด PHP ด้านบนยังคงเหมือนเดิมทั้งหมด 
include('../data/db_connect.php'); 
include('../material/stock_manager.php');

function can_make_recipe($conn, $recipe_id) {
    if (empty($recipe_id)) {
        return true;
    }
    $sql = "SELECT i.ingr_quantity, i.Unit_id AS required_unit_id, m.base_quantity AS stock_base_quantity, m.material_name FROM ingredient i JOIN material m ON i.material_id = m.material_id WHERE i.Recipes_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        return false;
    }
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        return true;
    }
    while ($row = $result->fetch_assoc()) {
        $required_base_info = convert_to_base_unit($conn, (float)$row['ingr_quantity'], (int)$row['required_unit_id']);
        $required_base_quantity = (float)$required_base_info['base_quantity'];
        $stock_base_quantity = (float)$row['stock_base_quantity'];
        if ($required_base_quantity > $stock_base_quantity) {
            $stmt->close();
            return false;
        }
    }
    $stmt->close();
    return true;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>ร้านโรตีลีลา</title>
    <link rel="stylesheet" href="../style/menu.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../style/navnsidebar.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        .menu-image-container {
            position: relative;
            display: block;
            line-height: 0;
        }
        .menu-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background-color: #f0f0f0;
        }
        .menu-card.disabled img {
            filter: grayscale(90%);
        }
        .unavailable-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.75);
            color: #b22222;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: bold;
            text-align: center;
            border-radius: 10px;
            z-index: 2;
            pointer-events: none;
        }
        .unavailable-overlay::after {
            content: "หมด";
        }
   
    </style>
</head>
<body>

<?php include('../layout/navbar.php'); ?>
<?php include('../layout/sidebar.php'); ?>

    <div class="container main-content">
        <!-- ===== ส่วนแสดงเมนู (menu-section) ไม่มีการเปลี่ยนแปลงโครงสร้าง HTML ===== -->
        <div class="menu-section">
            <div class="header">
                <h1>🍽️ รายการอาหาร</h1>
            </div>
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="ค้นหารายการอาหาร..." onkeyup="filterMenu()">
                <button type="button" onclick="clearSearch()"><i class="fas fa-times"></i></button>
            </div>
            <div class="category-tabs">
                <button class="tab active" onclick="showCategory('roti')">รายการโรตี</button>
                <button class="tab" onclick="showCategory('drink')">รายการเครื่องดื่ม</button>
            </div>
            <div class="menu-slider">
                <!-- เมนูโรตี -->
                <div class="menu-page active" id="roti-page">
                    <div class="menu-grid">
                        <?php
                        $category = 'เมนูโรตี';
                        $sql = "SELECT m.* FROM menu m JOIN menu_categories mc ON m.menu_ID = mc.menu_ID JOIN categories c ON mc.cate_ID = c.cate_ID WHERE m.visible = 1 AND c.cate_name = ? ORDER BY m.menu_name";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, 's', $category);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $id = $row['menu_ID'];
                            $names = htmlspecialchars($row['menu_name']);
                            $price = htmlspecialchars($row['menu_price']);
                            $image = '../' . htmlspecialchars($row['menu_image'] ?? '');
                            $recipe_id = $row['Recipes_id'] ?? null;
                            $is_available = can_make_recipe($conn, $recipe_id);
                            $card_class = $is_available ? 'menu-card' : 'menu-card disabled';
                            $unavailable_overlay = !$is_available ? "<div class='unavailable-overlay'></div>" : "";
                            echo "<div class='$card_class' data-id='$id' data-name='$names' data-price='$price'><div class='menu-image-container'>$unavailable_overlay<img src='$image' alt='$names' class='menu-image'></div><div class='menu-info'><span class='menu-name'>$names</span><span class='menu-price'>$price บาท</span></div></div>";
                        }
                        ?>
                    </div>
                </div>
                <!-- เมนูเครื่องดื่ม -->
                <div class="menu-page" id="drink-page">
                    <div class="menu-grid">
                        <?php
                        $category = 'เครื่องดื่ม';
                        $sql = "SELECT m.* FROM menu m JOIN menu_categories mc ON m.menu_ID = mc.menu_ID JOIN categories c ON mc.cate_ID = c.cate_ID WHERE m.visible = 1 AND c.cate_name = ? ORDER BY m.menu_name";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, 's', $category);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $id = $row['menu_ID'];
                            $names = htmlspecialchars($row['menu_name']);
                            $price = htmlspecialchars($row['menu_price']);
                            $image = '../' . htmlspecialchars($row['menu_image'] ?? '');
                            $recipe_id = $row['Recipes_id'] ?? null;
                            $is_available = can_make_recipe($conn, $recipe_id);
                            $card_class = $is_available ? 'menu-card' : 'menu-card disabled';
                            $unavailable_overlay = !$is_available ? "<div class='unavailable-overlay'></div>" : "";
                            echo "<div class='$card_class' data-id='$id' data-name='$names' data-price='$price'><div class='menu-image-container'>$unavailable_overlay<img src='$image' alt='$names' class='menu-image'></div><div class='menu-info'><span class='menu-name'>$names</span><span class='menu-price'>$price บาท</span></div></div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ===== ส่วนแสดงรายการสั่งซื้อ (order-section) - มีการแก้ไข ===== -->
        <div class="order-section">
            <div class="order-header">
                <h2>🛒 คำสั่งซื้อ</h2>
            </div>
            
            <div class="customer-info">
                <!-- ส่วนเลือกประเภทการสั่ง -->
                <div class="service-type-selector">
                    <label class="service-type-option">
                        <input type="radio" name="service-type" value="dine-in" checked>
                        <span>🏪 ทานที่ร้าน</span>
                    </label>
                    <label class="service-type-option">
                        <input type="radio" name="service-type" value="takeaway">
                        <span>📦 กลับบ้าน</span>
                    </label>
                </div>

                <!-- ส่วนสำหรับ ทานที่ร้าน (Dine-in) -->
                <div id="dine-in-fields">
                    <label>หมายเลขโต๊ะ:</label>
                    <div class="table-selection">
                        <span id="selected-table-display">ยังไม่ได้เลือก</span>
                        <button id="select-table-btn" class="btn-select-table">เลือกโต๊ะ</button>
                    </div>
                    <!-- ใช้ input แบบ hidden เพื่อเก็บค่าโต๊ะที่เลือก -->
                    <input type="hidden" id="table-number-input">
                </div>

                <!-- ส่วนสำหรับ กลับบ้าน (Takeaway) -->
                <div id="takeaway-fields" style="display: none;">
                    <input type="text" id="customer-name-input" placeholder="ชื่อผู้รับ">
                </div>
            </div>
            
            <div class="cart-items" id="cart-items">
                <!-- รายการสินค้าจะถูกเพิ่มที่นี่โดย JavaScript -->
                <div class="empty-cart">
                    <div class="empty-cart-icon">🛍️</div>
                    <p>ยังไม่มีรายการสั่งอาหาร</p>
                </div>
            </div>
            
            <div class="cart-summary" id="cart-summary" style="display: none;">
                <div class="cart-total-group">
                    <span>รวมทั้งหมด:</span>
                    <span class="cart-total"><span id="total">0</span> บาท</span>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary" id="menu-checkout-btn">สั่งอาหาร</button>
                <button class="btn btn-secondary" onclick="clearCart()">ยกเลิก</button>
            </div>
        </div>
    </div>

    <!-- ===== POPUPs SECTION ===== -->

    <!-- Popup: เลือกโต๊ะ -->
    <div id="table-select-modal" class="popup-overlay" style="display:none;">
        <div class="popup-box" style="max-width: 500px;">
            <span class="popup-close" id="close-table-modal-btn">×</span>
            <h3 style="margin-bottom: 20px;">เลือกหมายเลขโต๊ะ</h3>
            <div id="table-grid" class="table-grid-container">
                <!-- ปุ่มโต๊ะจะถูกสร้างโดย JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- Popup: ยืนยันคำสั่งซื้อ (เหมือนเดิม) -->
    <div id="order-summary-modal" class="popup-overlay" style="display:none;z-index:3000;">
        <div class="popup-box" style="max-width:400px;">
            <h3 style="margin-bottom:18px; color:#333;">ยืนยันคำสั่งซื้อ</h3>
            <div id="order-summary-content" style="background:#f8f9fa; color:#333; border-radius:8px; padding:16px; font-size:1em; margin-bottom:18px;"></div>
            <div style="display:flex; gap:16px; justify-content:center;">
                <button id="confirm-order-btn" class="btn btn-primary" style="min-width:80px;">ยืนยัน</button>
                <button id="cancel-order-btn" class="btn btn-secondary" style="min-width:80px;">ยกเลิก</button>
            </div>
        </div>
    </div>

    <!-- Popup: แจ้งเตือน (เหมือนเดิม) -->
<div id="alert-popup-overlay" style="display:none;">
    <div class="popup-box">
        <h3 id="alert-title">แจ้งเตือน</h3>
        <p id="alert-message"></p>
        <!-- เพิ่ม div ครอบปุ่ม -->
        <div class="popup-actions">
            <button id="alert-confirm-btn" class="btn-popup btn-primary-popup">ยืนยัน</button>
            <button id="alert-cancel-btn" class="btn-popup btn-secondary-popup">ยกเลิก</button>
        </div>
    </div>
</div>

    <script src="../js/menu.js?v=<?= time() ?>"></script>
</body>
</html>