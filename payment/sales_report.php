<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'owner' && $_SESSION['role'] !== 'sales' && $_SESSION['role'] !== 'manager')) {
    header('Location: ../auth/login.php');
    exit;
}

include('../data/db_connect.php');

// ใช้ format Y-m-d สำหรับการ query และแปลงกลับเมื่อแสดงผล
$start_date_input = $_GET['start_date'] ?? date('Y-m-d');
$end_date_input = $_GET['end_date'] ?? date('Y-m-d');

// สร้างวันที่สิ้นสุดที่ถูกต้องสำหรับการ query แบบ BETWEEN
$end_date_for_query = $end_date_input . ' 23:59:59';

// --- 1. ข้อมูลสรุปภาพรวม ---
$stmt_summary = $conn->prepare("SELECT SUM(total_amount) AS total_sales, COUNT(order_id) AS total_orders, AVG(total_amount) AS avg_order_value FROM order1 WHERE status = 'paid' AND Order_date BETWEEN ? AND ?");
$stmt_summary->bind_param("ss", $start_date_input, $end_date_for_query);
$stmt_summary->execute();
$summary_result = $stmt_summary->get_result()->fetch_assoc();

// --- 2. ข้อมูลสำหรับกราฟช่องทางการชำระเงิน ---
$stmt_payment = $conn->prepare("SELECT payment_method, SUM(total_amount) AS total, COUNT(order_id) AS count FROM order1 WHERE status = 'paid' AND Order_date BETWEEN ? AND ? GROUP BY payment_method");
$stmt_payment->bind_param("ss", $start_date_input, $end_date_for_query);
$stmt_payment->execute();
$payment_methods_data = $stmt_payment->get_result()->fetch_all(MYSQLI_ASSOC);

// --- 3. ข้อมูลสำหรับกราฟประเภทบริการ ---
$stmt_service = $conn->prepare("SELECT service_type, SUM(total_amount) AS total, COUNT(order_id) AS count FROM order1 WHERE status = 'paid' AND Order_date BETWEEN ? AND ? GROUP BY service_type");
$stmt_service->bind_param("ss", $start_date_input, $end_date_for_query);
$stmt_service->execute();
$service_types_data = $stmt_service->get_result()->fetch_all(MYSQLI_ASSOC);

// --- 4. ข้อมูลสำหรับกราฟเมนูขายดี ---
$stmt_top_menus = $conn->prepare("SELECT m.menu_name, SUM(i.quantity) AS total_quantity FROM order_items i JOIN menu m ON i.menu_id = m.menu_ID JOIN order1 o ON i.order_id = o.order_id WHERE o.status = 'paid' AND o.Order_date BETWEEN ? AND ? GROUP BY m.menu_name ORDER BY total_quantity DESC LIMIT 10");
$stmt_top_menus->bind_param("ss", $start_date_input, $end_date_for_query);
$stmt_top_menus->execute();
$top_menus_data = $stmt_top_menus->get_result()->fetch_all(MYSQLI_ASSOC);

function translate_key($key, $type) {
    $translations = [
        'payment_method' => ['cash' => 'เงินสด', 'qr' => 'QR Code'],
        'service_type' => ['dine-in' => 'ทานที่ร้าน', 'takeaway' => 'สั่งกลับบ้าน']
    ];
    return $translations[$type][$key] ?? $key;
}

// --- เตรียมข้อมูล JSON สำหรับ Chart.js ---
$payment_chart_labels = array_map(fn($pm) => translate_key($pm['payment_method'], 'payment_method'), $payment_methods_data);
$payment_chart_values = array_column($payment_methods_data, 'total');

$service_chart_labels = array_map(fn($st) => translate_key($st['service_type'], 'service_type'), $service_types_data);
$service_chart_values = array_column($service_types_data, 'total');

$top_menus_chart_labels = array_column($top_menus_data, 'menu_name');
$top_menus_chart_values = array_column($top_menus_data, 'total_quantity');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุปรายงานการขาย - ร้านโรตีลีลา</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style/report.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../style/navnsidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include('../layout/navbar.php'); ?>
    <?php include('../layout/sidebar.php'); ?>

    <div class="main-content">
        <div class="report-container">
<div class="im-header">
    <div class="im-header-left">
        <div class="im-header-icon-wrapper" style="color: #2980b9; background-color: #eaf3fb;">
            <i class="fas fa-chart-pie"></i>
        </div>
        <div>
            <h1 class="im-title">สรุปรายงานการขาย</h1>
            <p class="im-desc">ภาพรวมยอดขาย, ช่องทาง และเมนูขายดี</p>
        </div>
    </div>
</div>

<form method="get" class="im-filter-bar">
    <div class="filter-group">
        <label for="start_date">ข้อมูลตั้งแต่วันที่:</label>
        <input type="text" id="start_date" name="start_date" class="date-input">
        <label for="end_date">ถึงวันที่:</label>
        <input type="text" id="end_date" name="end_date" class="date-input">
    </div>
    <div class="action-group">
        <button type="submit" class="im-add-btn" style="background-color: var(--primary-color);"><i class="fas fa-filter"></i> กรองข้อมูล</button>
    </div>
</form>

            <div class="summary-cards-grid">
                <div class="summary-card">
                    <div class="card-icon-wrapper bg-green"><i class="fas fa-coins"></i></div>
                    <div class="card-details">
                        <span class="card-title">ยอดขายรวม</span>
                        <span class="card-value"><?= number_format($summary_result['total_sales'] ?? 0, 2) ?> บาท</span>
                    </div>
                </div>
                 <div class="summary-card">
                    <div class="card-icon-wrapper bg-blue"><i class="fas fa-receipt"></i></div>
                    <div class="card-details">
                        <span class="card-title">จำนวนออเดอร์</span>
                        <span class="card-value"><?= number_format($summary_result['total_orders'] ?? 0) ?></span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="card-icon-wrapper bg-yellow"><i class="fas fa-chart-line"></i></div>
                    <div class="card-details">
                        <span class="card-title">ยอดขายเฉลี่ย</span>
                        <span class="card-value"><?= number_format($summary_result['avg_order_value'] ?? 0, 2) ?> บาท</span>
                    </div>
                </div>
            </div>

            <div class="report-grid">
                <div class="report-section chart-card">
                    <h2><i class="fas fa-credit-card"></i> ช่องทางการชำระเงิน</h2>
                    <div class="chart-container" style="height:250px;"><canvas id="paymentMethodChart"></canvas></div>
                </div>
                <div class="report-section chart-card">
                    <h2><i class="fas fa-store"></i> ประเภทบริการ</h2>
                    <div class="chart-container" style="height:250px;"><canvas id="serviceTypeChart"></canvas></div>
                </div>
                <div class="report-section chart-card full-width">
                    <h2><i class="fas fa-utensils"></i> 10 อันดับเมนูขายดี</h2>
                    <div class="chart-container" style="height:400px;"><canvas id="topMenusChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>



<script>

function togglePassword() {
  const input = document.getElementById("password");
  const icon = document.querySelector("#togglePasswordBtn i");

  const isHidden = input.type === "password";
  input.type = isHidden ? "text" : "password";

  icon.classList.toggle("fa-eye", isHidden);
  icon.classList.toggle("fa-eye-slash", !isHidden);
}

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
//   if (profile && dropdown) {
//     profile.addEventListener('click', function (e) {
//       e.stopPropagation();
//       dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
//     });
//     document.addEventListener('click', function () {
//       dropdown.style.display = 'none';
//     });
//   }
});

document.addEventListener('DOMContentLoaded', function () {
    const chartFont = 'Sarabun, sans-serif';

    // --- START: โค้ดสำหรับปฏิทิน พ.ศ. (แก้ไขใหม่) ---
    flatpickr.localize(flatpickr.l10ns.th);

    // ฟังก์ชันสำหรับแปลงปีในปฏิทินและในช่อง Input
    function updateToThaiYear(instance) {
        // แปลงปีในช่องแสดงผล (altInput)
        if (instance.altInput && instance.selectedDates.length > 0) {
            const selectedDate = instance.selectedDates[0];
            const gregorianYear = selectedDate.getFullYear();
            const thaiYear = gregorianYear + 543;
            const displayValue = instance.formatDate(selectedDate, "d/m/Y");
            instance.altInput.value = displayValue.replace(gregorianYear.toString(), thaiYear.toString());
        }

        // แปลงปีที่แสดงบนหัวปฏิทิน
        const yearElement = instance.calendarContainer.querySelector('.flatpickr-current-month .numInput.cur-year');
        if (yearElement) {
            // **จุดที่แก้ไข:**
            // เราจะดึงค่าปี ค.ศ. จาก instance.currentYear ซึ่งเป็นค่าภายในของ flatpickr
            // แทนการอ่านจาก yearElement.value ที่อาจถูกแก้ไขเป็น พ.ศ. ไปแล้ว
            // วิธีนี้จะป้องกันการบวก 543 ซ้ำซ้อน
            const currentGregorianYear = instance.currentYear;
            yearElement.value = currentGregorianYear + 543;
        }
    }
    
    const datePickerConfig = {
        altInput: true,
        altFormat: "d/m/Y",       // รูปแบบที่แสดงให้ผู้ใช้เห็น
        dateFormat: "Y-m-d",      // รูปแบบที่ส่งไปให้ server
        locale: "th",
        // ดักจับ Event ต่างๆ เพื่อเรียกใช้ฟังก์ชันแปลงปี
        onReady: (selectedDates, dateStr, instance) => updateToThaiYear(instance),
        onChange: (selectedDates, dateStr, instance) => updateToThaiYear(instance),
        onMonthChange: (selectedDates, dateStr, instance) => {
            setTimeout(() => updateToThaiYear(instance), 100);
        },
        onYearChange: (selectedDates, dateStr, instance) => {
             setTimeout(() => updateToThaiYear(instance), 100);
        },
        onOpen: (selectedDates, dateStr, instance) => {
             setTimeout(() => updateToThaiYear(instance), 100);
        }
    };
    
    flatpickr("#start_date", { ...datePickerConfig, defaultDate: "<?= htmlspecialchars($start_date_input) ?>" });
    flatpickr("#end_date", { ...datePickerConfig, defaultDate: "<?= htmlspecialchars($end_date_input) ?>" });
    // --- END: โค้ดสำหรับปฏิทิน ---


    // --- โค้ดกราฟ (แก้ไข Syntax) ---
    const paymentCtx = document.getElementById('paymentMethodChart');
    if (paymentCtx && <?= json_encode(!empty($payment_chart_labels)) ?>) {
        new Chart(paymentCtx, {
            type: 'doughnut',
            data: { labels: <?= json_encode($payment_chart_labels) ?>, datasets: [{ label: 'ยอดขาย', data: <?= json_encode($payment_chart_values) ?>, backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545'], borderColor: '#fff', borderWidth: 2 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { font: { family: chartFont, size: 14 }}} } }
        });
    }

    const serviceCtx = document.getElementById('serviceTypeChart');
    if (serviceCtx && <?= json_encode(!empty($service_chart_labels)) ?>) {
        // ***** จุดที่แก้ไข Syntax Error *****
        new Chart(serviceCtx, {
            type: 'doughnut',
            data: { labels: <?= json_encode($service_chart_labels) ?>, datasets: [{ label: 'ยอดขาย', data: <?= json_encode($service_chart_values) ?>, backgroundColor: ['#007bff', '#6f42c1', '#fd7e14'], borderColor: '#fff', borderWidth: 2 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { font: { family: chartFont, size: 14 }}}} }
        });
    }
    
    const topMenusCtx = document.getElementById('topMenusChart');
    if (topMenusCtx && <?= json_encode(!empty($top_menus_chart_labels)) ?>) {
        new Chart(topMenusCtx, {
            type: 'bar',
            data: { labels: <?= json_encode($top_menus_chart_labels) ?>, datasets: [{ label: 'จำนวนที่ขายได้ (หน่วย)', data: <?= json_encode($top_menus_chart_values) ?>, backgroundColor: 'rgba(0, 123, 255, 0.6)', borderColor: 'rgba(0, 123, 255, 1)', borderWidth: 1 }] },
            options: {
                responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { font: { family: chartFont, size: 14 } }, title: { display: true, text: 'ชื่อเมนู', font: { family: chartFont, size: 16, weight: 'bold' } } },
                    y: { beginAtZero: true, ticks: { font: { family: chartFont } }, title: { display: true, text: 'จำนวนที่ขายได้ (หน่วย)', font: { family: chartFont, size: 16, weight: 'bold' } } }
                }
            }
        });
    }
});
</script>
</body>
</html>