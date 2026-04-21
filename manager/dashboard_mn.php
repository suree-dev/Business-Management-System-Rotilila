<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../data/db_connect.php'); 

$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// =================================================================
// PHP LOGIC - NO CHANGES WERE MADE TO THE FUNCTIONALITY
// =================================================================
$start_date_input = $_GET['start_date'] ?? date('Y-m-d');
$end_date_input = $_GET['end_date'] ?? date('Y-m-d');
$end_date_for_query = $end_date_input . ' 23:59:59';

date_default_timezone_set('Asia/Bangkok');
$today = date("Y-m-d");

// KPI Data
$stmt_sales = $conn->prepare("SELECT SUM(total_amount) as total_sales FROM order1 WHERE status = 'paid' AND Order_date BETWEEN ? AND ?");
$stmt_sales->bind_param("ss", $start_date_input, $end_date_for_query);
$stmt_sales->execute();
$sales_in_range = $stmt_sales->get_result()->fetch_assoc()['total_sales'] ?? 0;

$stmt_orders = $conn->prepare("SELECT COUNT(order_id) as total_orders FROM order1 WHERE status = 'paid' AND Order_date BETWEEN ? AND ?");
$stmt_orders->bind_param("ss", $start_date_input, $end_date_for_query);
$stmt_orders->execute();
$orders_in_range = $stmt_orders->get_result()->fetch_assoc()['total_orders'] ?? 0;

$sql_online = "SELECT COUNT(DISTINCT user_id) as online_users FROM attendance WHERE DATE(check_in_time) = '$today' AND check_out_time IS NULL";
$result_online = $conn->query($sql_online);
$online_users = $result_online->fetch_assoc()['online_users'] ?? 0;

$sql_total_users = "SELECT COUNT(user_ID) as total_users FROM users WHERE status = 'Active'";
$result_total_users = $conn->query($sql_total_users);
$total_users = $result_total_users->fetch_assoc()['total_users'] ?? 0;

$sql_low_stock = "SELECT COUNT(material_id) as low_stock_count FROM material WHERE material_quantity <= min_stock";
$result_low_stock = $conn->query($sql_low_stock);
$low_stock_count = $result_low_stock->fetch_assoc()['low_stock_count'] ?? 0;

// Sales Chart Data
$sales_data = [];
$sales_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date));
    $th_days = ['Sun'=>'อา.', 'Mon'=>'จ.', 'Tue'=>'อ.', 'Wed'=>'พ.', 'Thu'=>'พฤ.', 'Fri'=>'ศ.', 'Sat'=>'ส.'];
    $sql = "SELECT SUM(total_amount) as daily_sales FROM order1 WHERE DATE(Order_date) = '$date' AND status = 'paid'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $sales_data[] = $row['daily_sales'] ?? 0;
    $sales_labels[] = $th_days[$day_name];
}
$max_sales = !empty($sales_data) ? max($sales_data) : 0;
$suggested_max_y = ($max_sales > 0) ? ceil($max_sales * 1.2 / 1000) * 1000 : 3000;

// Employee Status Data
$sql_employee_status = "
    SELECT u.user_ID, u.full_name, u.role, a.status 
    FROM attendance a JOIN users u ON a.user_id = u.user_ID
    WHERE DATE(a.check_in_time) = '$today' AND a.status IN ('present', 'late')
    GROUP BY u.user_ID, u.full_name, u.role, a.status ORDER BY u.full_name
";
$result_employee_status = $conn->query($sql_employee_status);
$employees_present = [];
if($result_employee_status) {
    while ($row = $result_employee_status->fetch_assoc()) { $employees_present[] = $row; }
}

// Best Selling Items Data
$stmt_sales_items = $conn->prepare("
    SELECT m.menu_name, SUM(oi.quantity) as total_quantity, SUM(m.menu_price * oi.quantity) as total_revenue
    FROM order_items oi JOIN order1 o ON oi.order_id = o.order_id JOIN menu m ON oi.menu_id = m.menu_ID
    WHERE o.status = 'paid' AND o.Order_date BETWEEN ? AND ?
    GROUP BY m.menu_name ORDER BY total_quantity DESC LIMIT 4
");
$stmt_sales_items->bind_param("ss", $start_date_input, $end_date_for_query);
$stmt_sales_items->execute();
$result_sales_items = $stmt_sales_items->get_result();
$sales_items = [];
if($result_sales_items){
    while ($row = $result_sales_items->fetch_assoc()) { $sales_items[] = $row; }
}

// Low Stock Data
$sql_stock = "
SELECT 
    m.material_name, 
    m.material_quantity, 
    u.unit_name, 
    m.min_stock
FROM material m
JOIN unit u ON m.Unit_id = u.unit_id
WHERE m.material_quantity <= m.min_stock
ORDER BY m.material_quantity ASC
LIMIT 6";
$result_stock = $conn->query($sql_stock);
$stock_items = [];
if($result_stock){
    while ($row = $result_stock->fetch_assoc()) {
        $status_text = 'ใกล้หมด'; $status_class = 'warning';
        if ($row['material_quantity'] <= 0) { $status_text = 'หมดแล้ว!'; $status_class = 'danger'; }
        $row['status_text'] = $status_text; $row['status_class'] = $status_class;
        $stock_items[] = $row;
    }
}

// Helper Function
function format_thai_date($datetime) {
    if (!$datetime || $datetime == 'ไม่มีข้อมูล') return 'ไม่มีข้อมูล';
    $ts = strtotime($datetime);
    $months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    return date('j', $ts) . " " . $months[(int)date('n', $ts)] . " " . (date('Y', $ts) + 543) . " " . date('H:i', $ts);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด</title>
    
    <!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../style/navnsidebar.css?v=<?= time() ?>"> 
    <link rel="stylesheet" href="../style/manager.css?v=<?= time() ?>"> <!-- <<< เรียกใช้ไฟล์ CSS ใหม่ -->

</head>
<body>

    <?php include('../layout/navbar.php'); ?>
    <?php include('../layout/sidebar.php'); ?>

    <div class="main-content">
        <div class="dashboard-container">
            
            <header class="dashboard-header">
                <h1><i class="fas fa-chart-pie"></i>แดชบอร์ด</h1>
                <form method="get" class="date-filter-form">
                    <label for="start_date">ข้อมูลตั้งแต่:</label>
                    <input type="text" id="start_date" name="start_date" class="date-input">
                    <label for="end_date">ถึง:</label>
                    <input type="text" id="end_date" name="end_date" class="date-input">
                    <button type="submit" class="filter-btn"><i class="fas fa-filter"></i> กรอง</button>
                </form>
            </header>
            
            <?php
                $latest_updated_at_result = mysqli_query($conn, "SELECT updated_at FROM material ORDER BY updated_at DESC LIMIT 1");
                $latest_updated_at_row = mysqli_fetch_assoc($latest_updated_at_result);
                $latest_updated_at = $latest_updated_at_row['updated_at'] ?? 'ไม่มีข้อมูล';
            ?>
            <div class="latest-update">
                อัปเดตข้อมูลวัตถุดิบล่าสุด: <?= format_thai_date($latest_updated_at) ?> น.
            </div>
            
            <div class="kpi-cards-grid">
                <div class="card kpi-card">
                    <div class="card-icon"><i class="fa-solid fa-baht-sign"></i></div>
                    <div class="card-details">
                        <div class="value"><?= number_format($sales_in_range, 2) ?></div>
                        <div class="label">ยอดขาย (ตามช่วงเวลา)</div>
                    </div>
                </div>
                <div class="card kpi-card">
                    <div class="card-icon"><i class="fa-solid fa-receipt"></i></div>
                    <div class="card-details">
                        <div class="value"><?= number_format($orders_in_range) ?></div>
                        <div class="label">จำนวนออเดอร์</div>
                    </div>
                </div>
                <div class="card kpi-card">
                    <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="card-details">
                        <div class="value"><?= $online_users ?>/<?= $total_users ?></div>
                        <div class="label">พนักงานเข้างานวันนี้</div>
                    </div>
                </div>
                <div class="card kpi-card">
                    <div class="card-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="card-details">
                        <div class="value"><?= $low_stock_count ?></div>
                        <div class="label">วัตถุดิบใกล้หมด</div>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="card sales-chart-card">
                    <h2 class="card-title"><i class="fa-solid fa-chart-line"></i>ยอดขาย 7 วันล่าสุด</h2>
                    <div class="chart-wrapper">
                        <?php if (array_sum($sales_data) > 0): ?>
                            <canvas id="salesChart"></canvas>
                        <?php else: ?>
                            <div class="no-data-message">ยังไม่มีข้อมูลการขายในช่วง 7 วันที่ผ่านมา</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card employee-status-card">
                    <h2 class="card-title"><i class="fa-solid fa-user-check"></i>สถานะพนักงานวันนี้</h2>
                    <ul class="employee-list">
                        <?php if (!empty($employees_present)): foreach ($employees_present as $emp): ?>
                        <li class="employee-item">
                            <div class="employee-info">
                                <div class="employee-avatar"><?= mb_substr($emp['full_name'], 0, 1) ?></div>
                                <div>
                                    <div class="employee-name"><?= htmlspecialchars($emp['full_name']) ?></div>
                                    <div class="employee-role"><?= htmlspecialchars($emp['role']) ?></div>
                                </div>
                            </div>
                            <div class="status-dot <?= $emp['status'] == 'late' ? 'orange' : 'green' ?>"></div>
                        </li>
                        <?php endforeach; else: ?>
                            <li class="no-data-message-static">ไม่มีพนักงานที่เข้างานในวันนี้</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="card bestseller-card">
                    <h2 class="card-title"><i class="fa-solid fa-trophy"></i>สินค้าขายดี (ตามช่วงเวลา)</h2>
                    <table>
                        <thead><tr><th>อันดับ</th><th>สินค้า</th><th>จำนวนขาย (หน่วย)</th><th>รายได้รวม (บาท)</th></tr></thead>
                        <tbody>
                            <?php if (!empty($sales_items)): $rank = 1; foreach ($sales_items as $item): ?>
                            <tr>
                                <td><?= $rank++ ?></td>
                                <td><?= htmlspecialchars($item['menu_name']) ?></td>
                                <td><?= number_format($item['total_quantity']) ?></td>
                                <td><?= number_format($item['total_revenue'], 2) ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="4" class="no-data-message-static">ยังไม่มีข้อมูลการขายสำหรับช่วงเวลานี้</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card stock-card">
                    <h2 class="card-title"><i class="fa-solid fa-boxes-stacked"></i>สต็อกวัตถุดิบที่ต้องตรวจสอบ</h2>
                    <div class="stock-grid">
                        <?php if(!empty($stock_items)): foreach($stock_items as $item): ?>
                        <div class="stock-item <?= $item['status_class'] ?>">
                            <div class="name"><?= htmlspecialchars($item['material_name']) ?></div>
                            <div class="quantity"><?= rtrim(rtrim(number_format($item['material_quantity'], 2), '0'), '.') ?> <?= $item['unit_name'] ?></div>
                            <div class="status"><?= $item['status_text'] ?></div>
                        </div>
                        <?php endforeach; else: ?>
                            <p class="no-data-message-static" style="grid-column: 1 / -1;">ไม่มีวัตถุดิบที่ใกล้หมดในขณะนี้</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Chart.js Setup (with visual improvements) ---
    const chartCanvas = document.getElementById('salesChart');
    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(106, 90, 205, 0.3)');
        gradient.addColorStop(1, 'rgba(106, 90, 205, 0)');

        new Chart(ctx, {
            type: 'line', 
            data: { 
                labels: <?= json_encode($sales_labels) ?>, 
                datasets: [{ 
                    label: 'ยอดขาย', data: <?= json_encode($sales_data) ?>, 
                    borderColor: '#6A5ACD', backgroundColor: gradient, 
                    borderWidth: 3, fill: true, tension: 0.4, 
                    pointBackgroundColor: '#6A5ACD', pointBorderColor: '#ffffff', 
                    pointBorderWidth: 2, pointRadius: 5, pointHoverRadius: 7 
                }] 
            },
            options: { 
                responsive: true, maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#212529', titleFont: { family: 'Sarabun', weight: 'bold' },
                        bodyFont: { family: 'Sarabun' }, padding: 10, cornerRadius: 8,
                        callbacks: {
                            label: (context) => ' ยอดขาย: ' + context.formattedValue + ' บาท'
                        }
                    }
                }, 
                scales: { 
                    y: { 
                        beginAtZero: true, suggestedMax: <?= $suggested_max_y ?>, 
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }, 
                        ticks: { 
                            font: { family: 'Sarabun' },
                            callback: (value) => value.toLocaleString() + ' ฿'
                        } 
                    }, 
                    x: { 
                        grid: { display: false },
                        ticks: { font: { family: 'Sarabun' } }
                    } 
                } 
            }
        });
    }

    // --- Flatpickr Date Picker (Thai Year) ---
    flatpickr.localize(flatpickr.l10ns.th);
    const datePickerConfig = {
        altInput: true, altFormat: "j/n/Y", dateFormat: "Y-m-d",
        onReady: (d,s,i) => updateThaiYear(i),
        onMonthChange: (d,s,i) => setTimeout(() => updateThaiYear(i), 100),
        onYearChange: (d,s,i) => setTimeout(() => updateThaiYear(i), 100),
        onOpen: (d,s,i) => setTimeout(() => updateThaiYear(i), 100),
        onChange: (d,s,i) => updateThaiYear(i)
    };
    
    function updateThaiYear(instance) {
        if(instance.altInput){
            const date = instance.selectedDates[0];
            if(date) {
                const thaiYear = date.getFullYear() + 543;
                instance.altInput.value = instance.formatDate(date, "j/n/").concat(thaiYear);
            }
        }
        const yearInput = instance.calendarContainer.querySelector('.numInput.cur-year');
        if (yearInput) {
            yearInput.value = instance.currentYear + 543;
        }
    }
    
    flatpickr("#start_date", { ...datePickerConfig, defaultDate: "<?= htmlspecialchars($start_date_input) ?>" });
    flatpickr("#end_date", { ...datePickerConfig, defaultDate: "<?= htmlspecialchars($end_date_input) ?>" });
});

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

<?php
$conn->close();
?>