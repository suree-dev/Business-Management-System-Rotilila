<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = strtolower($_SESSION['role'] ?? '');
include('../data/db_connect.php'); 
include('../material/stock_manager.php');

//-- REQUIREMENT 2: Auto-update pending orders --
try {
    $conn->query("UPDATE order1 SET status='processing' WHERE status='pending' AND Order_date < NOW() - INTERVAL 2 MINUTE");
} catch (Exception $e) {
    // Optional: log the error
}

function format_thai_date($date) {
    if (empty($date)) return '-';
    $ts = strtotime($date);
    if ($ts === false) return '-';

    $months = [
        "", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.",
        "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."
    ];
    $day = date('j', $ts);
    $month = $months[(int)date('n', $ts)];
    $year = date('Y', $ts) + 543;
    return "$day $month $year";
}

// Fetch low stock items (No changes)
$low_stock_items = [];
$sql_low_stock = "SELECT material_name FROM material WHERE (base_quantity <= min_stock AND min_stock > 0) OR base_quantity = 0";
$low_stock_result = $conn->query($sql_low_stock);
if ($low_stock_result && $low_stock_result->num_rows > 0) {
    while ($row = $low_stock_result->fetch_assoc()) {
        $low_stock_items[] = $row['material_name'];
    }
}

//-- MODIFIED: Handle all POST requests --
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- REQUIREMENT 1: QR Code and Table Management ---
if (isset($_POST['toggle_qr'])) {
    $table_num = $_POST['table_num'] ?? '';
    if (!empty($table_num)) {
        $conn->begin_transaction();
        try {
            // 1. ดึงข้อมูลสถานะปัจจุบันของโต๊ะ
            $stmt_check = $conn->prepare("SELECT qr_status, current_group_id FROM tables WHERE table_num = ?");
            $stmt_check->bind_param('s', $table_num);
            $stmt_check->execute();
            $table_result = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();

            if ($table_result) {
                if ($table_result['qr_status'] === 'active') {
                    // --- Action: ปิดโต๊ะ (CLOSE QR) ---
                    // ตรรกะใหม่: แค่เปลี่ยนสถานะและล้าง Group ID
                    // การเคลียร์บิลจะเกิดขึ้นในหน้าชำระเงิน
                    $stmt_update = $conn->prepare("UPDATE tables SET qr_status = 'inactive', current_group_id = NULL WHERE table_num = ?");
                    $stmt_update->bind_param('s', $table_num);
                    $stmt_update->execute();
                    $stmt_update->close();
                    
                } else {
                    // --- Action: เปิดโต๊ะ (OPEN QR) ---
                    // ตรรกะใหม่: ไม่ต้องสร้างออเดอร์ว่างๆ อีกต่อไป
                    // แค่เปลี่ยนสถานะโต๊ะเป็น active และรอให้ลูกค้าสั่งอาหาร
                    $stmt_update = $conn->prepare("UPDATE tables SET qr_status = 'active', current_group_id = NULL WHERE table_num = ?");
                    $stmt_update->bind_param('s', $table_num);
                    $stmt_update->execute();
                    $stmt_update->close();
                }
            }
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_flash_message'] = "เกิดข้อผิดพลาดในการจัดการโต๊ะ: " . $e->getMessage();
        }
    }
}
    // --- Handle Order Actions (accept, done, delete) ---
    elseif (isset($_POST['order_id'])) {
        $order_id = intval($_POST['order_id']);

        if (isset($_POST['accept'])) {
            $conn->begin_transaction();
            try {
                // (Original 'accept' code)
                $sql_items = "SELECT oi.menu_id, oi.quantity, m.Recipes_id FROM order_items oi JOIN menu m ON oi.menu_id = m.menu_ID WHERE oi.order_id = ?";
                $stmt_items = $conn->prepare($sql_items);
                $stmt_items->bind_param('i', $order_id);
                $stmt_items->execute();
                $order_items_result = $stmt_items->get_result();
                if (!$order_items_result || $order_items_result->num_rows === 0) throw new Exception("ไม่พบรายการสินค้าในออเดอร์นี้");

                while ($item = $order_items_result->fetch_assoc()) {
                    if (empty($item['Recipes_id'])) continue;
                    $sql_recipe = "SELECT material_id, ingr_quantity, Unit_id FROM ingredient WHERE Recipes_id = ?";
                    $stmt_recipe = $conn->prepare($sql_recipe);
                    $stmt_recipe->bind_param('i', $item['Recipes_id']);
                    $stmt_recipe->execute();
                    $recipe_result = $stmt_recipe->get_result();

                    while ($material = $recipe_result->fetch_assoc()) {
                        $total_quantity_to_deduct = $material['ingr_quantity'] * $item['quantity'];
                        $deduct_result = deduct_stock($conn, $material['material_id'], $total_quantity_to_deduct, $material['Unit_id']);
                        if (!$deduct_result['success']) {
                            $mat_name_res = $conn->query("SELECT material_name FROM material WHERE material_id = {$material['material_id']}");
                            $mat_name = $mat_name_res->fetch_assoc()['material_name'] ?? "ID: {$material['material_id']}";
                            throw new Exception("วัตถุดิบ '" . htmlspecialchars($mat_name) . "' ไม่เพียงพอ (" . $deduct_result['message'] . ")");
                        }
                    }
                }
                $sql_update_order = "UPDATE order1 SET status='processing' WHERE order_id=?";
                $stmt_order = $conn->prepare($sql_update_order);
                $stmt_order->bind_param('i', $order_id);
                $stmt_order->execute();
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_flash_message'] = $e->getMessage();
            }
        } elseif (isset($_POST['done'])) {
            $conn->query("UPDATE order1 SET status='done' WHERE order_id=$order_id");
        } elseif (isset($_POST['delete_order'])) {
            $conn->query("DELETE FROM order_items WHERE order_id=$order_id");
            $conn->query("DELETE FROM order1 WHERE order_id=$order_id");
        }
    }
    
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ระบบจัดการร้าน - ร้านโรตีลีลา</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style/sale.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../style/navnsidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include('../layout/navbar.php'); ?>
<?php include('../layout/sidebar.php'); ?>

<div class="container main-content">
    <div class="page-header">
        <h2>ระบบจัดการร้าน</h2>
        <div class="header-date"><?= format_thai_date(date('Y-m-d')) ?></div>
    </div>
    
    <!--//-- ADDED: Tab navigation structure --//-->
    <div class="sale-header-tabs">
        <button class="sale-tab active" id="tab-tables" onclick="showTab('tables')">
            <i class="fas fa-chair"></i> จัดการโต๊ะ (เปิด/ปิด QR)
        </button>
        <button class="sale-tab" id="tab-orders" onclick="showTab('orders')">
            <i class="fas fa-receipt"></i> จัดการใบสั่งซื้อ
        </button>
    </div>

    <?php if (isset($_SESSION['error_flash_message'])): ?>
        <div class="alert alert-danger" style="margin-top: 1rem;">
            <?= htmlspecialchars($_SESSION['error_flash_message']); ?>
        </div>
        <?php unset($_SESSION['error_flash_message']); ?>
    <?php endif; ?>

    <!--//-- ADDED: Content for Tab 1: Table Management --//-->
    <div id="content-tables" class="tab-content">
    <?php
    // -- MODIFIED: เปลี่ยน current_order_id เป็น current_group_id --
    $sql_tables = "SELECT table_num, qr_status, current_group_id FROM tables ORDER BY CAST(table_num AS UNSIGNED)";
        $tables_result = $conn->query($sql_tables);
        ?>
        <div class="table-management-container">
            <div class="table-grid">
                <?php if ($tables_result && $tables_result->num_rows > 0):
                    while($table = $tables_result->fetch_assoc()):
                        $is_active = ($table['qr_status'] === 'active');
                        $card_class = $is_active ? 'active' : 'inactive';
                        $btn_class = $is_active ? 'btn-danger' : 'btn-success';
                        $btn_text = $is_active ? 'ปิด' : 'เปิด';
                        $btn_icon = $is_active ? 'fas fa-times' : 'fas fa-check';
                ?>
                    <div class="table-card <?=$card_class?>">
                        <div class="table-info">
                            <i class="fas fa-utensils"></i>
                            <span>โต๊ะ <?=htmlspecialchars($table['table_num'])?></span>
                        </div>
                        <form method="post" class="table-action-form">
                            <input type="hidden" name="table_num" value="<?=htmlspecialchars($table['table_num'])?>">
                            <button type="submit" name="toggle_qr" class="table-action-btn <?=$btn_class?>">
                                <i class="fas <?=$btn_icon?>"></i> <?=$btn_text?>
                            </button>
                        </form>
                    </div>
                <?php endwhile; else: ?>
                    <p>ไม่พบข้อมูลโต๊ะในระบบ (กรุณาเพิ่มข้อมูลในตาราง 'tables')</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!--//-- ADDED: Content for Tab 2: Order Management --//-->
    <div id="content-orders" class="tab-content">
        <?php
        $today = date('Y-m-d');
        $sql_orders = "SELECT 
          o.order_id, o.group_id, o.Order_date, o.status, o.service_type, o.table_num, o.customer_name, o.total_amount,
          GROUP_CONCAT(CONCAT(m.menu_name, ' x', i.quantity) SEPARATOR '|||') AS menu_list
        FROM order1 o
        LEFT JOIN order_items i ON o.order_id = i.order_id
        LEFT JOIN menu m ON i.menu_id = m.menu_ID
        WHERE o.status != 'paid' OR (o.status = 'paid' AND DATE(o.Order_date) = '$today')
        GROUP BY o.order_id
        ORDER BY o.Order_date ASC";

        $result = mysqli_query($conn, $sql_orders);
        $orders_by_status = ['pending' => [], 'processing' => [], 'done' => [], 'paid' => []];
        if ($result) {
            while($row = mysqli_fetch_assoc($result)) {
                if ($row['status'] === 'pending' && empty($row['menu_list'])) {
                    continue;
                }
                if (isset($orders_by_status[$row['status']])) {
                    $orders_by_status[$row['status']][] = $row;
                }
            }
        }
        $status_count = [
          'pending' => count($orders_by_status['pending']),
          'processing' => count($orders_by_status['processing']),
          'done' => count($orders_by_status['done']),
          'paid' => count($orders_by_status['paid'])
        ];
        ?>
        <!-- Status Filter Cards -->
        <div class="status-filter-container">
            <div class="status-card" id="filter-pending" onclick="filterByStatus('pending')">
                <div class="count pending-count"><?=$status_count['pending']?></div>
                <div class="label">รอสักครู่</div>
            </div>
            <div class="status-card" id="filter-processing" onclick="filterByStatus('processing')">
                <div class="count processing-count"><?=$status_count['processing']?></div>
                <div class="label">กำลังปรุง</div>
            </div>
            <div class="status-card" id="filter-done" onclick="filterByStatus('done')">
                <div class="count done-count"><?=$status_count['done']?></div>
                <div class="label">พร้อมเสิร์ฟ</div>
            </div>
            <div class="status-card" id="filter-paid" onclick="filterByStatus('paid')">
                <div class="count paid-count"><?=$status_count['paid']?></div>
                <div class="label">ชำระเงินแล้ว</div>
            </div>
            <button class="show-all-btn" id="filter-all" onclick="filterByStatus('all')">
                <i class="fas fa-eye"></i> แสดงทั้งหมด
            </button>
        </div>

        <!-- Order Columns -->
        <div class="order-columns-container">
            <!-- Pending Column -->
            <div class="order-column" id="column-pending" data-status-column="pending">
                <div class="column-header pending-bg">
                    <h3><i class="fas fa-hourglass-half"></i> คำสั่งซื้อใหม่</h3>
                    <span class="column-count"><?=$status_count['pending']?></span>
                </div>
                <div class="column-body">
                    <?php if (empty($orders_by_status['pending'])): ?>
                        <div class="no-orders-placeholder">ไม่มีคำสั่งซื้อ</div>
                    <?php else: foreach ($orders_by_status['pending'] as $row): ?>
                    <div class="order-card border-pending">
                        <div class="card-header">
                            <span class="order-id">#<?=$row['order_id']?></span>
                            <span class="order-time"><?=date('H:i', strtotime($row['Order_date']))?></span>
                        </div>
                        <div class="card-customer">
                            <?php if ($row['service_type'] === 'dine-in'): ?>
                                <i class="fas fa-store"></i> โต๊ะ <?=htmlspecialchars($row['table_num'])?>
                            <?php else: ?>
                                <i class="fas fa-box"></i> กลับบ้าน: <?=htmlspecialchars($row['customer_name'])?>
                            <?php endif; ?>
                        </div>
                        <ul class="card-items">
                            <?php $menu_items = explode('|||', $row['menu_list'] ?? '');
                            foreach ($menu_items as $item) echo '<li>' . htmlspecialchars(trim($item)) . '</li>'; ?>
                        </ul>
                        <div class="card-footer">
                            <span class="order-total"><?=number_format($row['total_amount'], 2)?> บาท</span>
                            <form method="post" class="action-form">
                                <input type="hidden" name="order_id" value="<?=$row['order_id']?>">
                                <button class="order-action-btn accept" name="accept">รับออเดอร์ <i class="fas fa-check"></i></button>
                            </form>
                        </div>
                         <button class="order-action-btn delete" onclick="confirmDelete(<?=$row['order_id']?>)"><i class="fas fa-trash-alt"></i></button>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Processing Column -->
            <div class="order-column" id="column-processing" data-status-column="processing">
                <div class="column-header processing-bg">
                    <h3><i class="fas fa-utensils"></i> กำลังปรุง</h3>
                    <span class="column-count"><?=$status_count['processing']?></span>
                </div>
                <div class="column-body">
                    <?php if (empty($orders_by_status['processing'])): ?>
                        <div class="no-orders-placeholder">ไม่มีคำสั่งซื้อ</div>
                    <?php else: foreach ($orders_by_status['processing'] as $row): ?>
                    <div class="order-card border-processing">
                         <div class="card-header">
                            <span class="order-id">#<?=$row['order_id']?></span>
                            <span class="order-time"><?=date('H:i', strtotime($row['Order_date']))?></span>
                        </div>
                        <div class="card-customer">
                            <?php if ($row['service_type'] === 'dine-in'): ?>
                                <i class="fas fa-store"></i> โต๊ะ <?=htmlspecialchars($row['table_num'])?>
                            <?php else: ?>
                                <i class="fas fa-box"></i> กลับบ้าน: <?=htmlspecialchars($row['customer_name'])?>
                            <?php endif; ?>
                        </div>
                        <ul class="card-items">
                            <?php $menu_items = explode('|||', $row['menu_list'] ?? '');
                            foreach ($menu_items as $item) echo '<li>' . htmlspecialchars(trim($item)) . '</li>'; ?>
                        </ul>
                        <div class="card-footer">
                             <span class="order-total"><?=number_format($row['total_amount'], 2)?> บาท</span>
                            <form method="post" class="action-form">
                                <input type="hidden" name="order_id" value="<?=$row['order_id']?>">
                                <button class="order-action-btn done" name="done">อาหารพร้อม <i class="fas fa-bell"></i></button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Done Column -->
            <div class="order-column" id="column-done" data-status-column="done">
                <div class="column-header done-bg">
                    <h3><i class="fas fa-check-circle"></i> พร้อมเสิร์ฟ</h3>
                    <span class="column-count"><?=$status_count['done']?></span>
                </div>
                <div class="column-body">
                    <?php if (empty($orders_by_status['done'])): ?>
                        <div class="no-orders-placeholder">ไม่มีคำสั่งซื้อ</div>
                    <?php else: foreach ($orders_by_status['done'] as $row): ?>
                    <div class="order-card border-done">
                        <div class="card-header">
                            <span class="order-id">#<?=$row['order_id']?></span>
                            <span class="order-time"><?=date('H:i', strtotime($row['Order_date']))?></span>
                        </div>
                        <div class="card-customer">
                            <?php if ($row['service_type'] === 'dine-in'): ?>
                                <i class="fas fa-store"></i> โต๊ะ <?=htmlspecialchars($row['table_num'])?>
                            <?php else: ?>
                                <i class="fas fa-box"></i> กลับบ้าน: <?=htmlspecialchars($row['customer_name'])?>
                            <?php endif; ?>
                        </div>
                        <ul class="card-items">
                            <?php $menu_items = explode('|||', $row['menu_list'] ?? '');
                            foreach ($menu_items as $item) echo '<li>' . htmlspecialchars(trim($item)) . '</li>'; ?>
                        </ul>
                        <div class="card-footer">
                            <span class="order-total"><?=number_format($row['total_amount'], 2)?> บาท</span>
<a href="../payment/pm.sale.php?group_id=<?= $row['group_id'] ?>" class="order-action-btn pay">
    ชำระเงิน <i class="fas fa-cash-register"></i>
</a>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Paid Column -->
            <div class="order-column" id="column-paid" data-status-column="paid">
                <div class="column-header paid-bg">
                    <h3><i class="fas fa-receipt"></i> ชำระเงินแล้ว</h3>
                    <span class="column-count"><?=$status_count['paid']?></span>
                </div>
                <div class="column-body">
                    <?php if (empty($orders_by_status['paid'])): ?>
                        <div class="no-orders-placeholder">ไม่มีคำสั่งซื้อ</div>
                    <?php else: foreach ($orders_by_status['paid'] as $row): ?>
                    <div class="order-card border-paid">
                         <div class="card-header">
                            <span class="order-id">#<?=$row['order_id']?></span>
                            <span class="order-time"><?=date('H:i', strtotime($row['Order_date']))?></span>
                        </div>
                        <div class="card-customer">
                            <?php if ($row['service_type'] === 'dine-in'): ?>
                                <i class="fas fa-store"></i> โต๊ะ <?=htmlspecialchars($row['table_num'])?>
                            <?php else: ?>
                                <i class="fas fa-box"></i> กลับบ้าน: <?=htmlspecialchars($row['customer_name'])?>
                            <?php endif; ?>
                        </div>
                        <ul class="card-items">
                            <?php $menu_items = explode('|||', $row['menu_list'] ?? '');
                            foreach ($menu_items as $item) echo '<li>' . htmlspecialchars(trim($item)) . '</li>'; ?>
                        </ul>
                        <div class="card-footer">
                            <span class="order-total"><?=number_format($row['total_amount'], 2)?> บาท</span>
                            <button class="order-action-btn paid" disabled>เสร็จสิ้น <i class="fas fa-check"></i></button>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div> <!-- end order-columns-container -->
    </div>
</div> <!-- end main-content -->

<!-- Custom Delete Confirmation Popup -->
<div id="delete-order-popup" class="popup-overlay">
    <div class="popup-content">
        <h3>ยืนยันการดำเนินการ</h3>
        <p>คุณต้องการลบคำสั่งซื้อนี้ใช่หรือไม่?</p>
        <div class="popup-actions">
            <button id="confirm-delete-btn" class="btn-confirm">ยืนยัน</button>
            <button onclick="closeDeletePopup()" class="btn-cancel">ยกเลิก</button>
        </div>
    </div>
</div>

<script>
    //-- ADDED: Function to control tab visibility --//
    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
        });
        document.querySelectorAll('.sale-tab').forEach(tab => {
            tab.classList.remove('active');
        });

        document.getElementById('content-' + tabName).style.display = 'block';
        document.getElementById('tab-' + tabName).classList.add('active');
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Set the initial tab to 'tables'
        showTab('tables');
        
        // --- Sidebar management ---
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
        
        // Initialize the order filter to show all columns by default
        filterByStatus('all');
    });

    // --- Original JavaScript functions ---
    let currentOrderIdToDelete = null;

    function confirmDelete(orderId) {
      currentOrderIdToDelete = orderId;
      document.getElementById('delete-order-popup').style.display = 'flex';
    }

    function closeDeletePopup() {
      document.getElementById('delete-order-popup').style.display = 'none';
      currentOrderIdToDelete = null;
    }

    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    if (confirmDeleteBtn) {
      confirmDeleteBtn.addEventListener('click', function() {
          if (currentOrderIdToDelete) {
              const form = document.createElement('form');
              form.method = 'POST';
              form.style.display = 'none';
              form.innerHTML = `
                  <input type="hidden" name="order_id" value="${currentOrderIdToDelete}">
                  <input type="hidden" name="delete_order" value="1">
              `;
              document.body.appendChild(form);
              form.submit();
          }
      });
    }

    const deletePopup = document.getElementById('delete-order-popup');
    if (deletePopup) {
      deletePopup.addEventListener('click', function(e) {
          if (e.target === this) {
              closeDeletePopup();
          }
      });
    }

    const allColumns = document.querySelectorAll('.order-column');
    const allFilterCards = document.querySelectorAll('.status-card');
    const showAllBtn = document.getElementById('filter-all');

    function filterByStatus(status) {
      allFilterCards.forEach(card => card.classList.remove('active'));
      if (showAllBtn) showAllBtn.classList.remove('active');

      if (status === 'all') {
          allColumns.forEach(col => col.style.display = 'flex');
          if (showAllBtn) showAllBtn.classList.add('active');
      } else {
          const activeCard = document.getElementById(`filter-${status}`);
          if (activeCard) activeCard.classList.add('active');

          allColumns.forEach(col => {
              if (col.dataset.statusColumn === status) {
                  col.style.display = 'flex';
              } else {
                  col.style.display = 'none';
              }
          });
      }
    }
</script>
<script src="../js/sale.js?v=<?= time() ?>"></script>
</body>
</html>