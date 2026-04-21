<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = strtolower($_SESSION['role'] ?? ''); // strtolower ทำให้เป็นตัวพิมพ์เล็กทั้งหมด
                                                      //ทำเพราะระบบจับสิทธิ์ไม่ได้
?>

<aside class="sidebar" id="sidebar">
  <ul id="sidebar-menu">
    
    <?php if ($role === 'owner'): ?>
      <li><a href="../dashboard/dashboard.php">แดชบอร์ด</a></li>
      <li><a href="../manager/manager_emwork.php">การทำงานของพนักงาน</a></li>
    <?php endif; ?>

    <?php if ($role === 'sales'): ?>
      <li><a href="../sale/menu.php">สั่งอาหาร</a></li>
      <li><a href="../sale/sale.php">จัดการรายการอาหาร</a></li>
      <li><a href="../payment/pm.sale.php">ชำระเงิน</a></li>
      <li><a href="../payment/receipt.php">รายการชำระเงิน</a></li>
    <?php endif; ?>

    <?php if ($role === 'manager'): ?>
      <li><a href="../manager/dashboard_mn.php">แดชบอร์ด</a></li>        
      <li><a href="../material/own.editmaterial.php">จัดการรายการวัตถุดิบ</a></li>
      <li><a href="../recipe/own.editrecipe.php">จัดการสูตรอาหาร</a></li>
      <li><a href="../menu/own.editmenu.php">จัดการรายการอาหาร</a></li>
      <li><a href="../employee/employees.php">จัดการข้อมูลพนักงาน</a></li>
      <li><a href="../manager/manager_emwork.php">การทำงานของพนักงาน</a></li>
      <li><a href="../payment/sales_report.php" style="display: block;
    padding: 1rem;
    color: #ecf0f1;
    text-decoration: none;">สรุปรายงานการขาย</a></li>
    <li><a href="../report/report.php">รายงาน</a></li>
   <?php endif; ?>

  </ul>



</aside>
