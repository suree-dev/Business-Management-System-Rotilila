<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = strtolower($_SESSION['role'] ?? '');
include('../data/db_connect.php');

// --- NEW LOGIC: จัดการการชำระเงินผ่าน POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $table_num_to_clear = $_POST['table_num'] ?? null;
    $received_amount = isset($_POST['received_amount']) ? floatval($_POST['received_amount']) : null;

    if ($group_id > 0) {
        $conn->begin_transaction();
        try {
            // 1. อัปเดตสถานะออเดอร์ทั้งหมดในกลุ่มเป็น 'paid' และตั้งค่า payment_method
            $stmt_update_orders = $conn->prepare("UPDATE order1 SET status = 'paid', payment_method = ? WHERE group_id = ? AND status != 'cancelled'");
            $stmt_update_orders->bind_param('si', $payment_method, $group_id);
            $stmt_update_orders->execute();

            // 2. (ใหม่) ถ้าเป็นการจ่ายเงินสด ให้คำนวณและบันทึกยอดเงิน
            if ($payment_method === 'cash' && $received_amount !== null) {
                // ดึงยอดรวมที่แท้จริงของบิลจากฐานข้อมูลเพื่อความปลอดภัย
                $total_res = $conn->query("SELECT SUM(total_amount) as total FROM order1 WHERE group_id = $group_id AND status != 'cancelled'");
                $total_bill = $total_res->fetch_assoc()['total'];
                $change_amount = $received_amount - $total_bill;

                // บันทึกยอดเงินลงใน "ออเดอร์แรก" ของกลุ่ม (ตัวที่เป็น group_id)
                $stmt_update_cash = $conn->prepare("UPDATE order1 SET received_amount = ?, change_amount = ? WHERE order_id = ?");
                $stmt_update_cash->bind_param('ddi', $received_amount, $change_amount, $group_id);
                $stmt_update_cash->execute();
            }

            // 3. เคลียร์โต๊ะ (ถ้าเป็น dine-in)
            if ($table_num_to_clear) {
                $stmt_clear_table = $conn->prepare("UPDATE tables SET current_group_id = NULL, qr_status = 'inactive' WHERE table_num = ?");
                $stmt_clear_table->bind_param('s', $table_num_to_clear);
                $stmt_clear_table->execute();
            }
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
        }
    }
    header("Location: pm.sale.php");
    exit();
}


// --- NEW LOGIC: รับ group_id จาก URL ---
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

// --- NEW QUERY: ดึง "กลุ่มบิล" ที่พร้อมชำระ (มีอย่างน้อย 1 ออเดอร์สถานะ 'done') ---
$sql_groups = "SELECT 
    g.group_id,
    g.table_num,
    g.service_type,
    g.customer_name,
    g.group_total,
    g.first_order_time,
    GROUP_CONCAT(CONCAT(m.menu_name, ' x', i.quantity) SEPARATOR ', ') AS menu_list
FROM (
    -- Subquery เพื่อคำนวณยอดรวมของแต่ละกลุ่มให้ถูกต้องก่อน
    SELECT 
        group_id,
        table_num,
        service_type,
        customer_name,
        SUM(total_amount) AS group_total,
        MIN(Order_date) AS first_order_time
    FROM order1
    WHERE group_id IN (
        SELECT DISTINCT group_id FROM order1 WHERE status = 'done'
    ) AND status != 'cancelled'
    GROUP BY group_id
) AS g
-- Join กับ order1 อีกครั้งเพื่อหา order_id ที่จะไป join กับ items
JOIN order1 o ON g.group_id = o.group_id
JOIN order_items i ON o.order_id = i.order_id
JOIN menu m ON i.menu_id = m.menu_ID
WHERE o.status != 'cancelled'
GROUP BY g.group_id
ORDER BY g.first_order_time ASC";

$result_groups = mysqli_query($conn, $sql_groups);
$payment_groups = [];
while($row = mysqli_fetch_assoc($result_groups)) {
    $payment_groups[] = $row;
}

// --- NEW LOGIC: ดึงข้อมูล "กลุ่มบิล" ที่เลือก ---
$selected_group = null;
if ($group_id > 0) {
    // 1. ดึงข้อมูลสรุปของกลุ่ม
    $sql_selected_summary = "SELECT 
        group_id, table_num, service_type, customer_name, 
        SUM(total_amount) AS total_bill, 
        MIN(Order_date) AS first_order_time
        FROM order1 
        WHERE group_id = $group_id AND status != 'cancelled'
        GROUP BY group_id";
    $result_summary = mysqli_query($conn, $sql_selected_summary);
    $selected_group = mysqli_fetch_assoc($result_summary);
    
    if ($selected_group) {
        // 2. ดึงรายการอาหารทั้งหมดในกลุ่ม
        $sql_items = "SELECT m.menu_name, m.menu_price, SUM(i.quantity) as total_quantity
                      FROM order_items i 
                      JOIN menu m ON i.menu_id = m.menu_ID 
                      JOIN order1 o ON i.order_id = o.order_id
                      WHERE o.group_id = $group_id AND o.status != 'cancelled'
                      GROUP BY i.menu_id
                      ORDER BY m.menu_name";
        $result_items = mysqli_query($conn, $sql_items);
        $group_items = [];
        while($item = mysqli_fetch_assoc($result_items)) {
            $group_items[] = $item;
        }
        $selected_group['items'] = $group_items;

        // 3. เก็บ order_ids ไว้ใช้ (ถ้าจำเป็น)
        $sql_order_ids = "SELECT order_id FROM order1 WHERE group_id = $group_id AND status != 'cancelled'";
        $result_order_ids = mysqli_query($conn, $sql_order_ids);
        $order_ids = [];
        while($row = mysqli_fetch_assoc($result_order_ids)) {
            $order_ids[] = $row['order_id'];
        }
        $selected_group['order_ids'] = $order_ids;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ระบบชำระเงิน - ร้านโรตีลีลา</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style/pm.sale.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../style/navnsidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include('../layout/navbar.php'); ?>
<?php include('../layout/sidebar.php'); ?>
<div class="container main-content">
    <div class="payment-container">
        <!-- Left Panel - Order List -->
        <div class="order-list-panel">
            <h2>คำสั่งซื้อพร้อมชำระเงิน</h2>

<!-- Search Box -->
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" placeholder="ค้นหาด้วยหมายเลขออเดอร์, ชื่อลูกค้า หรือ หมายเลขโต๊ะ" onkeyup="filterOrders()">
        </div>

        <!-- Order List -->
        <div class="order-list">
            <?php if (empty($payment_groups)): ?>
    <div class="empty-state">
        <i class="fas fa-receipt empty-icon"></i>
        <p>ไม่มีบิลที่พร้อมชำระเงิน</p>
    </div>
<?php else: ?>
    <?php foreach ($payment_groups as $group): ?>
        <div class="order-card <?= ($selected_group && $selected_group['group_id'] == $group['group_id']) ? 'selected' : '' ?>" 
             onclick="selectGroup(<?= $group['group_id'] ?>)">
            <div class="order-header">
                <div class="order-info">
                    <h3>
                        <?= htmlspecialchars(
                            $group['service_type'] === 'dine-in'
                                ? 'โต๊ะ ' . $group['table_num']
                                : 'คุณ' . $group['customer_name']
                        ) ?>
                    </h3>
                    <p class="order-time"><?= date('H:i', strtotime($group['first_order_time'])) ?></p>
                </div>
                <div class="order-total">
                    <p class="total-amount"><?= number_format($group['group_total'], 2) ?> บาท</p>
                    <span class="status-badge">พร้อมชำระเงิน</span>
                </div>
            </div>
            <div class="order-items">
                <p class="items-label">รายการ:</p>
                <?php 
                $menu_items = explode(', ', $group['menu_list']);
                $display_items = array_slice($menu_items, 0, 2);
                foreach ($display_items as $item) echo '<p class="item">' . htmlspecialchars(trim($item)) . '</p>';
                if (count($menu_items) > 2) echo '<p class="more-items">และอีก ' . (count($menu_items) - 2) . ' รายการ</p>';
                ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
        </div>
    </div>

    <!-- Right Panel - Payment Processing -->
    <div class="payment-panel">
        <?php if ($selected_group): ?>
    <h2>ดำเนินการชำระเงิน</h2>
    
    <!-- Order Details -->
    <div class="order-details-card">
        <div class="order-summary">
            <div class="order-basic-info">
                <h3>
                    <?= htmlspecialchars(
                        $selected_group['service_type'] === 'dine-in'
                            ? 'โต๊ะ ' . $selected_group['table_num']
                            : 'คุณ' . $selected_group['customer_name']
                    ) ?>
                </h3>
                <p class="order-time">ออเดอร์แรก: <?= date('H:i', strtotime($selected_group['first_order_time'])) ?></p>
            </div>
            <div class="order-total-display">
                <p class="total-amount-large"><?= number_format($selected_group['total_bill'], 2) ?> บาท</p>
            </div>
        </div>

        <!-- Items List -->
        <div class="items-section">
            <h4>รายการอาหารทั้งหมด</h4>
            <?php foreach ($selected_group['items'] as $item): ?>
                <div class="item-row">
                    <span class="item-name"><?= htmlspecialchars($item['menu_name']) ?> x<?= $item['total_quantity'] ?></span>
                    <span class="item-price"><?= number_format($item['menu_price'] * $item['total_quantity'], 2) ?> บาท</span>
                </div>
            <?php endforeach; ?>
            
            <div class="total-section">
                <div class="total-row">
                    <span>รวมทั้งสิ้น</span>
                    <span class="final-total"><?= number_format($selected_group['total_bill'], 2) ?> บาท</span>
                </div>
            </div>
        </div>
    </div>
    
<div class="payment-method-card">
    <h4>เลือกวิธีการชำระเงิน</h4>
    <div class="payment-methods">
        <button class="payment-method-btn" onclick="selectPaymentMethod('cash')">
            <div class="method-icon cash-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <span>เงินสด</span>
        </button>
        <button class="payment-method-btn" onclick="selectPaymentMethod('qr')">
            <div class="method-icon qr-icon">
                <i class="fas fa-qrcode" style="color: white;"></i>
            </div>
            <span>QR Code</span>
        </button>
    </div>
</div>

<!-- SECTION: Cash Payment Input -->
<div id="cash-input-section" class="cash-input-card" style="display: none;">
    <h4>จำนวนเงินที่รับจากลูกค้า</h4>
    <input type="number" id="receivedAmount" step="0.01" placeholder="กรอกจำนวนเงิน" oninput="calculateChange()">
    <div id="change-display" class="change-display" style="display: none;">
        <div class="change-row">
            <span>เงินทอน:</span>
            <span id="changeAmount" class="change-amount">0.00 บาท</span>
        </div>
    </div>
</div>
    
    <!-- *** สร้างฟอร์มที่ซ่อนไว้สำหรับส่งข้อมูล *** -->
    <form id="payment-form" method="post" action="pm.sale.php" style="display: none;">
        <input type="hidden" name="group_id" value="<?= $selected_group['group_id'] ?>">
        <input type="hidden" name="table_num" value="<?= htmlspecialchars($selected_group['table_num']) ?>">
        <input type="hidden" id="form-payment-method" name="payment_method" value="">
        <input type="hidden" id="form-received-amount" name="received_amount" value="">
        <input type="hidden" name="process_payment" value="1">
    </form>

    <!-- Process Payment Button -->
    <button id="processPaymentBtn" class="process-payment-btn" onclick="processPayment()" disabled>
        <i class="fas fa-check"></i> ดำเนินการชำระเงิน
    </button>

<?php else: ?>
            <div class="empty-payment-state">
                <i class="fas fa-credit-card empty-icon"></i>
                <p class="empty-title">เลือกออเดอร์ที่ต้องการชำระเงิน</p>
                <p class="empty-subtitle">คลิกที่ออเดอร์ทางด้านซ้ายเพื่อเริ่มต้น</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>
<!-- Receipt Modal -->
<div id="receiptModal" class="receipt-modal" style="display: none;">
    <div class="receipt-content">
        <div class="receipt-header">
            <h3>ใบเสร็จรับเงิน</h3>
            <button onclick="closeReceipt()" class="close-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="receipt-body" id="receipt-body-printable">
            <div class="receipt-store-info">
                <h4>ร้านโรตีลีลา</h4>
                <p>ตลาดเก่า สิโรรส 2</p>
                <p>โทร: 063-585-1106</p>
            </div>
            
            <div class="receipt-order-info">
                <!-- เปลี่ยนจาก <p> เป็น <div> เพื่อการจัดวางที่ดีกว่า -->
                <div class="receipt-info-row">
                    <span><strong>ออเดอร์:</strong></span>
                    <span id="receiptOrderId"></span>
                </div>
                <div class="receipt-info-row">
                    <span><strong>ลูกค้า:</strong></span>
                    <span id="receiptCustomerName"></span>
                </div>
                <div class="receipt-info-row" id="receipt-table-row">
                    <span><strong>โต๊ะ:</strong></span>
                    <span id="receiptTable"></span>
                </div>
                <div class="receipt-info-row">
                    <span><strong>เวลา:</strong></span>
                    <span id="receiptTime"></span>
                </div>
            </div>
            
            <!-- เพิ่มหัวตารางรายการอาหาร -->
            <div class="receipt-items-header">
                <span>รายการ</span>
                <span>จำนวนเงิน</span>
            </div>
            <div class="receipt-items" id="receiptItems">
                <!-- Items will be populated by JavaScript -->
            </div>
            
            <div class="receipt-total">
                <div class="receipt-total-row total">
                    <span>รวมเป็นเงิน</span>
                    <span id="receiptTotal"></span>
                </div>
                <div class="receipt-total-row">
                    <span>วิธีชำระเงิน</span>
                    <span id="receiptPaymentMethod"></span>
                </div>
                <div id="receiptCashInfo" style="display: none;">
                    <div class="receipt-total-row">
                        <span>รับเงินสด</span>
                        <span id="receiptReceivedAmount"></span>
                    </div>
                    <div class="receipt-total-row change">
                        <span>เงินทอน</span>
                        <span id="receiptChange"></span>
                    </div>
                </div>
            </div>
            
            <div class="receipt-footer">
                <p>ขอบคุณที่ใช้บริการ</p>
            </div>
        </div>
    
        <div class="receipt-actions">
            <button onclick="printReceipt()" class="print-btn">
                <i class="fas fa-print"></i>
                พิมพ์ใบเสร็จ
            </button>
            <button onclick="closeReceipt()" class="close-receipt-btn">
                ปิด
            </button>
        </div>
    </div>
</div>
<script>
// =================================================================
// SECTION 1: DATA FROM PHP
// =================================================================
<?php if ($selected_group): ?>
window.groupData = <?= json_encode($selected_group) ?>;
<?php else: ?>
window.groupData = null;
<?php endif; ?>

let selectedPaymentMethod = '';


// =================================================================
// SECTION 2: CORE PAYMENT FUNCTIONS
// =================================================================

// Function to navigate to the selected group's payment page
function selectGroup(groupId) {
    window.location.href = 'pm.sale.php?group_id=' + groupId;
}

// Function to handle payment method selection
function selectPaymentMethod(method) {
    selectedPaymentMethod = method;
    
    // Update button styles
    document.querySelectorAll('.payment-method-btn').forEach(btn => btn.classList.remove('selected'));
    const selectedBtn = document.querySelector(`.payment-method-btn[onclick="selectPaymentMethod('${method}')"]`);
    if(selectedBtn) selectedBtn.classList.add('selected');

    // Show/hide cash input section
    const cashInputSection = document.getElementById('cash-input-section');
    cashInputSection.style.display = (method === 'cash') ? 'block' : 'none';
    
    // Enable the process payment button
    document.getElementById('processPaymentBtn').disabled = false;
}

// Function to calculate change for cash payments
function calculateChange() {
    if (!window.groupData) return;
    const receivedAmount = parseFloat(document.getElementById('receivedAmount').value) || 0;
    const totalAmount = groupData.total_bill;
    const change = receivedAmount - totalAmount;
    
    const changeAmountEl = document.getElementById('changeAmount');
    const changeDisplayEl = document.getElementById('change-display');

    if (changeAmountEl) changeAmountEl.textContent = `${change.toFixed(2)} บาท`;
    if (changeDisplayEl) changeDisplayEl.style.display = 'block';
}

// Function to process the final payment
function processPayment() {
    if (!selectedPaymentMethod) {
        alert('กรุณาเลือกวิธีการชำระเงิน');
        return;
    }

    // Set values in the hidden form
    document.getElementById('form-payment-method').value = selectedPaymentMethod;

    if (selectedPaymentMethod === 'cash') {
        const receivedAmount = document.getElementById('receivedAmount').value;
        if (!receivedAmount || parseFloat(receivedAmount) < groupData.total_bill) {
            alert('จำนวนเงินที่รับมาไม่ถูกต้อง หรือน้อยกว่ายอดชำระ');
            return;
        }
        document.getElementById('form-received-amount').value = receivedAmount;
    }
    
    // Show receipt and then submit the form after a short delay
    showReceipt();
}

// =================================================================
// SECTION 3: RECEIPT MODAL FUNCTIONS
// =================================================================

function showReceipt() {
    if (!window.groupData) return;
    
    const customerIdentifier = groupData.service_type === 'dine-in' ? `โต๊ะ ${groupData.table_num}` : groupData.customer_name;

    document.getElementById('receiptOrderId').textContent = `บิลกลุ่ม #${groupData.group_id}`;
    document.getElementById('receiptCustomerName').textContent = customerIdentifier;
    document.getElementById('receiptTable').textContent = groupData.table_num || '-';
    document.getElementById('receiptTime').textContent = new Date(groupData.first_order_time).toLocaleTimeString('th-TH');
    document.getElementById('receiptTotal').textContent = `${parseFloat(groupData.total_bill).toFixed(2)} บาท`;
    
    // Payment Method
    const methodNames = { 'cash': 'เงินสด', 'qr': 'QR Code' };
    document.getElementById('receiptPaymentMethod').textContent = methodNames[selectedPaymentMethod];
    
    // Items
    const receiptItems = document.getElementById('receiptItems');
    receiptItems.innerHTML = '';
    groupData.items.forEach(item => {
        let itemDiv = document.createElement('div');
        itemDiv.className = 'receipt-item-row';
        itemDiv.innerHTML = `
            <span>${item.menu_name} x${item.total_quantity}</span>
            <span>${(item.menu_price * item.total_quantity).toFixed(2)} บาท</span>
        `;
        receiptItems.appendChild(itemDiv);
    });
    
    // Cash Info
    const cashInfo = document.getElementById('receiptCashInfo');
    if (selectedPaymentMethod === 'cash') {
        const receivedAmount = parseFloat(document.getElementById('receivedAmount').value) || 0;
        const change = receivedAmount - groupData.total_bill;
        document.getElementById('receiptReceivedAmount').textContent = `${receivedAmount.toFixed(2)} บาท`;
        document.getElementById('receiptChange').textContent = `${change.toFixed(2)} บาท`;
        cashInfo.style.display = 'block';
    } else {
        cashInfo.style.display = 'none';
    }
    
    document.getElementById('receiptModal').style.display = 'flex';
}

function closeReceipt() {
    // When the receipt is closed, we submit the payment form to finalize the transaction
    const form = document.getElementById('payment-form');
    if(form) {
      form.submit();
    }
}

function printReceipt() {
    const receiptContent = document.getElementById('receipt-body-printable').innerHTML;
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>ใบเสร็จ</title>');
    // You should link to an external CSS file for printing for better results
    printWindow.document.write('<style> /* Add your print styles here */ </style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(receiptContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

// =================================================================
// SECTION 4: UI & GENERAL FUNCTIONS
// =================================================================

function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const content = document.querySelector('.main-content');
  sidebar.classList.toggle('active');
  if (content) content.classList.toggle('shifted');
}

function filterOrders() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const orderList = document.querySelector(".order-list");
    const cards = orderList.getElementsByClassName('order-card');

    for (let i = 0; i < cards.length; i++) {
        const h3 = cards[i].getElementsByTagName("h3")[0];
        if (h3) {
            const txtValue = h3.textContent || h3.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                cards[i].style.display = "";
            } else {
                cards[i].style.display = "none";
            }
        }       
    }
}

document.addEventListener('DOMContentLoaded', function () {
  // Sidebar toggle
  const toggleBtn = document.getElementById('toggleSidebar');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', toggleSidebar);
  }
});

</script>
</body>
</html>