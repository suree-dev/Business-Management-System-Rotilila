<?php
include('../data/db_connect.php');
session_start();
// สมมติว่าชื่อพนักงานบัญชีเก็บใน session['username'] หรือ session['fullname']
$cashier = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : (isset($_SESSION['username']) ? $_SESSION['username'] : '-');
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order = null;
$order_items = [];
if ($order_id > 0) {
    $sql = "SELECT * FROM order1 WHERE order_id = $order_id";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        $order = $row;
    }
    $sql_items = "SELECT m.menu_name, i.quantity, m.menu_price FROM order_items i JOIN menu m ON i.menu_id = m.menu_ID WHERE i.order_id = $order_id";
    $result_items = mysqli_query($conn, $sql_items);
    while ($row = mysqli_fetch_assoc($result_items)) {
        $order_items[] = $row;
    }
}
function format_thai_date($datetime) {
    $ts = strtotime($datetime);
    $months = [
        "", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.",
        "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."
    ];
    $d = date('j', $ts);
    $m = $months[(int)date('n', $ts)];
    $y = date('Y', $ts) + 543;
    $h = date('H:i', $ts);
    return "$d/$m/$y $h";
}

// [แก้ไข] ดึง table_num จาก $order แทน $_GET เพื่อความถูกต้อง
$table_number = isset($order['table_num']) ? htmlspecialchars($order['table_num']) : '1';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ใบเสร็จรับเงิน - ร้านโรตีลีลา</title>
    <link rel="stylesheet" href="../style/payment.css?v=<?=time()?>">
    <link rel="stylesheet" href="../style/receipt.css?v=<?=time()?>">
</head>
<body>
<div class="receipt-slip">
    <?php if ($order): ?>
        <div class="receipt-header">
            <h2>ร้านโรตีลีลา</h2>
            <div>ตลาดเก่า สิโรรส 2</div>
            <div>ต.สะเตง อ.เมือง จ.ยะลา</div>
            <div>โทร. 063-585-1106</div>
        </div>

        <div class="receipt-hr"></div>

        <div class="receipt-where">
            <div><?= $order['service_type'] === 'dine-in' ? 'เสิร์ฟที่โต๊ะ' : 'สั่งกลับบ้าน' ?></div>
            <?php if ($order['service_type'] === 'dine-in'): ?>
                <div>โต๊ะ: <?= htmlspecialchars($order['table_num'] ?? '-') ?></div>
            <?php else: ?>
                <div>ลูกค้า: <?= htmlspecialchars($order['customer_name'] ?? '-') ?></div>
            <?php endif; ?>
        </div>

        <div class="receipt-hr"></div>

        <div class="receipt-detail">
            <div style="display: flex; justify-content: space-between;">
                <span>ใบเสร็จ: #<?= $order['order_id'] ?></span>
                <span>แคชเชียร์: <?= htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username'] ?? '-') ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>วันที่: <?= format_thai_date($order['Order_date']) ?></span>
                <span>เวลา: <?= date('H:i:s', strtotime($order['Order_date'])) ?></span>
            </div>
        </div>

        <div class="receipt-hr"></div>

        <div class="receipt-list">
            <?php 
            $total = 0; 
            foreach ($order_items as $item): 
                $sum = $item['quantity'] * $item['menu_price']; 
                $total += $sum; 
            ?>
            <div class="receipt-list-item">
                <span><?= htmlspecialchars($item['menu_name']) ?> x<?= $item['quantity'] ?></span>
                <span><?= number_format($sum, 2) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="receipt-hr"></div>
        
        <div class="receipt-summary">
            <div class="summary-left">จำนวน: <?= count($order_items) ?></div>
            <div class="summary-right">รวม<span class="summary-colon">:</span> <?= number_format($total, 2) ?></div>
            <div class="summary-right">ส่วนลด<span class="summary-colon">:</span> 0.00</div>
            <div class="summary-right">ยอดสุทธิ<span class="summary-colon">:</span> <?= number_format($total, 2) ?></div>
        </div>

        <div class="receipt-hr"></div>
        
        <?php 
        $pay_methods = ['cash' => 'เงินสด', 'qr' => 'QR Code'];
        $pay_method_text = $pay_methods[$order['payment_method']] ?? 'ไม่ระบุ';
        ?>

        <div class="receipt-footer">
            ขอบคุณที่ใช้บริการ<br>
            กรุณาตรวจสอบความถูกต้อง
        </div>
        
        <a href="../?table=<?= $table_number ?>" class="done-btn" id="done-btn">กลับสู่หน้าหลัก</a>
    <?php else: ?>
        <div class="error-message">ไม่พบข้อมูลออเดอร์</div>
    <?php endif; ?>
</div>
</div>
<!-- [แก้ไข] ลบ script ที่เคยอยู่ตรงนี้ออกไปทั้งหมด -->
<script src="../js/sale.js?v=<?= time() ?>"></script>
</body>
</html>