<?php
include('../data/db_connect.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'sales' && $_SESSION['role'] !== 'owner')) {
    header('Location: ../auth/login.php');
    exit;
}

// --- NEW LOGIC: ดึง "กลุ่มบิล" ที่ชำระเงินแล้ว ---
$sql_paid_groups = "SELECT 
    group_id, 
    table_num, 
    customer_name, 
    service_type, 
    SUM(total_amount) as group_total, 
    MIN(Order_date) as first_order_date 
FROM order1 
WHERE status = 'paid' AND group_id IS NOT NULL 
GROUP BY group_id 
ORDER BY first_order_date DESC";

$result_groups = mysqli_query($conn, $sql_paid_groups);
$paid_groups = [];
while ($row = mysqli_fetch_assoc($result_groups)) {
    $paid_groups[] = $row;
}

// --- NEW LOGIC: ดึงข้อมูล "กลุ่มบิล" ที่เลือก ---
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : ($paid_groups[0]['group_id'] ?? 0);
$selected_group = null;
$group_items = [];

if ($group_id > 0) {
    // 1. ดึงข้อมูลสรุปของกลุ่มที่เลือก
    $sql_summary = "SELECT 
        group_id, table_num, service_type, customer_name, 
        SUM(total_amount) AS total_bill, 
        MIN(Order_date) AS first_order_date,
        MAX(payment_method) AS payment_method, 
        MAX(received_amount) AS received_amount,  
        MAX(change_amount) AS change_amount 
        FROM order1 
        WHERE group_id = $group_id AND status = 'paid'
        GROUP BY group_id";
    $result_summary = mysqli_query($conn, $sql_summary);
    $selected_group = mysqli_fetch_assoc($result_summary);
    
    if ($selected_group) {
        // 2. ดึงรายการอาหารทั้งหมดในกลุ่ม
        $sql_items = "SELECT m.menu_name, m.menu_price, SUM(i.quantity) as total_quantity
                      FROM order_items i 
                      JOIN menu m ON i.menu_id = m.menu_ID 
                      JOIN order1 o ON i.order_id = o.order_id
                      WHERE o.group_id = $group_id AND o.status = 'paid'
                      GROUP BY i.menu_id
                      ORDER BY m.menu_name";
        $result_items = mysqli_query($conn, $sql_items);
        while($item = mysqli_fetch_assoc($result_items)) {
            $group_items[] = $item;
        }
    }
}

// ฟังก์ชันจัดรูปแบบวันที่ (เหมือนเดิม)
function format_thai_date($datetime) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ใบเสร็จรับเงิน - ร้านโรตีลีลา</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style/receipt.css?v=<?=time()?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../style/navnsidebar.css?v=<?=time()?>">

</head>
<body>
<?php include('../layout/navbar.php'); ?>
<?php include('../layout/sidebar.php'); ?>

<div class="container main-content">
    <div class="receipt-page-flex">
        <!-- ฝั่งซ้าย: ประวัติออเดอร์ -->
<div class="order-history-list">
    <?php
    if (empty($paid_groups)) {
        echo '<div class="no-orders-message"><h3>ไม่มีใบเสร็จ</h3><p>ยังไม่มีออเดอร์ที่ชำระเงินแล้ว</p></div>';
    } else {
        // แบ่งกลุ่มบิลตามวัน
        $groups_by_date = [];
        foreach ($paid_groups as $g) {
            $date = date('Y-m-d', strtotime($g['first_order_date']));
            $groups_by_date[$date][] = $g;
        }

        foreach ($groups_by_date as $date => $groups_list) {
            echo '<div class="order-history-section">';
            echo '<div class="order-history-date">' . format_thai_date($date) . '</div>';
            foreach ($groups_list as $g) {
                $selected = ($g['group_id'] == $group_id) ? 'selected' : '';
                $identifier = $g['service_type'] === 'dine-in' ? 'โต๊ะ ' . $g['table_num'] : 'กลับบ้าน';
                
                echo '<a href="?group_id=' . $g['group_id'] . '" class="order-history-item ' . $selected . '">';
                echo '<div class="left">';
                echo '<span class="amount">' . number_format($g['group_total'], 2) . '</span> บาท';
                echo '<span class="time">' . date('H:i', strtotime($g['first_order_date'])) . '</span>';
                echo '</div>';
                echo '<div class="order-id">' . $identifier . ' (#' . $g['group_id'] . ')</div>';
                echo '<span class="status paid">ชำระแล้ว</span>';
                echo '</a>';
            }
            echo '</div>';
        }
    }
    ?>
</div>

        <!-- ฝั่งขวา: ใบเสร็จ -->
<div class="receipt-slip">
    <?php if ($selected_group): ?>
        <div class="receipt-header">
            <h2>ร้านโรตีลีลา</h2>
            <div>ตลาดเก่า สิโรรส 2</div>
            <div>ต.สะเตง อ.เมือง จ.ยะลา</div>
            <div>โทร. 063-585-1106</div>
        </div>

        <div style="text-align: center; font-size: medium;">ใบเสร็จรับเงิน</div>
        <div class="receipt-hr"></div>

        <div class="receipt-where">
            <div><?= $selected_group['service_type'] === 'dine-in' ? 'เสิร์ฟที่โต๊ะ' : 'สั่งกลับบ้าน' ?></div>
            <?php if ($selected_group['service_type'] === 'dine-in'): ?>
                <div>โต๊ะ: <?= htmlspecialchars($selected_group['table_num'] ?? '-') ?></div>
            <?php else: ?>
                <div>ลูกค้า: <?= htmlspecialchars($selected_group['customer_name'] ?? '-') ?></div>
            <?php endif; ?>
        </div>
        <div class="receipt-hr"></div>

        <div class="receipt-detail">
            <div style="display: flex; justify-content: space-between;">
                <span>บิลกลุ่ม: #<?= $selected_group['group_id'] ?></span>
                <span>แคชเชียร์: <?= htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username'] ?? '-') ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>วันที่: <?= format_thai_date($selected_group['first_order_date']) ?></span>
                <span>เวลา: <?= date('H:i:s', strtotime($selected_group['first_order_date'])) ?></span>
            </div>
        </div>
        <div class="receipt-hr"></div>

        <div class="receipt-list">
            <?php $total = 0; $total_quantity = 0; foreach ($group_items as $item): 
                $sum = $item['total_quantity'] * $item['menu_price']; 
                $total += $sum; 
                $total_quantity += $item['total_quantity'];
            ?>
            <div class="receipt-list-item">
                <span><?= htmlspecialchars($item['menu_name']) ?> x<?= $item['total_quantity'] ?></span>
                <span><?= number_format($sum, 2) ?> บาท</span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="receipt-hr"></div>

        <div class="receipt-summary">
            <div class="summary-left">จำนวน: <?= $total_quantity ?> รายการ</div>
            <div class="summary-right">รวม<span class="summary-colon">:</span> <?= number_format($total, 2) ?> บาท</div>
            <div class="summary-right">ส่วนลด<span class="summary-colon">:</span> 0.00 บาท</div>
            <div class="summary-right">ยอดสุทธิ<span class="summary-colon">:</span> <?= number_format($selected_group['total_bill'], 2) ?> บาท</div>
        </div>
        <div class="receipt-hr"></div>

        <?php 
            $pay_methods = ['cash' => 'เงินสด', 'qr' => 'QR Code'];
            $pay_method_text = $pay_methods[$selected_group['payment_method']] ?? 'ไม่ระบุ';
        ?>
        <?php if ($selected_group['payment_method'] === 'cash'): ?>
    
    <!-- บรรทัดสำหรับเงินสด -->
    <div class="receipt-list-item">
        <span><?= $pay_method_text ?></span>
        <span><?= number_format($selected_group['received_amount'], 2) ?> บาท</span>
    </div>
    
    <!-- บรรทัดสำหรับเงินทอน -->
    <div class="receipt-list-item">
        <span>เงินทอน</span>
        <span><?= number_format($selected_group['change_amount'], 2) ?> บาท</span>
    </div>

<?php else: // สำหรับ QR Code หรือวิธีอื่นๆ ?>

    <!-- บรรทัดสำหรับวิธีชำระเงินอื่นๆ -->
    <div class="receipt-list-item">
        <span><?= $pay_method_text ?></span>
        <span><?= number_format($selected_group['total_bill'], 2) ?> บาท</span>
    </div>

<?php endif; ?>
        <div class="receipt-hr"></div>

        <div class="receipt-footer">
            ขอบคุณที่ใช้บริการ<br>
            กรุณาตรวจสอบความถูกต้อง
        </div>
    <?php else: ?>
        <div class="no-orders-message">
            <h3>เลือกใบเสร็จ</h3>
            <p>กรุณาเลือกรายการทางด้านซ้ายเพื่อดูรายละเอียด</p>
        </div>
    <?php endif; ?>
</div>
    </div>
</div>
<script>
    function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const content = document.querySelector('.main-content');
  sidebar.classList.toggle('active');
  if (content) content.classList.toggle('shifted');
}

document.addEventListener('DOMContentLoaded', function () {
  // Sidebar toggle
  const toggleBtn = document.getElementById('toggleSidebar');
  const sidebar = document.getElementById('sidebar');
  const content = document.querySelector('.main-content');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      sidebar.classList.toggle('active');
      if (content) content.classList.toggle('shifted');
    });
  }

  // ปิด sidebar เมื่อคลิกข้างนอก
  document.addEventListener('click', function (e) {
    if (sidebar && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
      sidebar.classList.remove('active');
      if (content) content.classList.remove('shifted');
    }
  });

  // ปิด sidebar เมื่อคลิกเมนู
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
</script>
</body>
</html>
