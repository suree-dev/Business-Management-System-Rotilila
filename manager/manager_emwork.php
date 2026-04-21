<?php
// --- PHP LOGIC (ส่วนนี้ไม่มีการเปลี่ยนแปลงการทำงาน) ---
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include('../data/db_connect.php');
$conn->set_charset("utf8");
date_default_timezone_set('Asia/Bangkok');
$today = date("Y-m-d");
$sql_present = "SELECT COUNT(DISTINCT user_id) AS present_count FROM attendance WHERE DATE(check_in_time) = '$today' AND status IN ('present', 'late')";
$result_present = $conn->query($sql_present);
$present_count = $result_present->fetch_assoc()['present_count'] ?? 0;
$sql_total = "SELECT COUNT(*) AS total_employees FROM users WHERE role IN ('sales', 'accountant', 'manager', 'admin', 'owner')";
$result_total = $conn->query($sql_total);
$total_employees = $result_total->fetch_assoc()['total_employees'] ?? 0;
$absent_count = max(0, $total_employees - $present_count);

function formatThaiDateOnly($dateStr) {
    $ts = strtotime($dateStr);
    $months = ["", "ม.ค", "ก.พ", "มี.ค", "เม.ย", "พ.ค", "มิ.ย", "ก.ค", "ส.ค", "ก.ย", "ต.ค", "พ.ย", "ธ.ค"];
    $d = date('j', $ts);
    $m = $months[(int)date('n', $ts)];
    $y = date('Y', $ts) + 543;
    return "$d $m $y";
}

// START: ฟังก์ชันแปลงเวลาเป็น "xx นาทีที่แล้ว"
function time_ago($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->s = floor($diff->d / 7);
    $diff->d -= $diff->s * 7;

    $string = array(
        'y' => 'ปี', 'm' => 'เดือน', 's' => 'สัปดาห์', 'd' => 'วัน', 'h' => 'ชั่วโมง', 'i' => 'นาที', 's' => 'วินาที',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . 'ที่แล้ว' : 'เมื่อสักครู่';
}
// END: ฟังก์ชันแปลงเวลา

// START: Query ข้อมูลกิจกรรมล่าสุดของพนักงาน
$activity_logs = [];
$sql_logs = "SELECT a.action, a.log_timestamp, u.full_name 
             FROM activity_log a
             JOIN users u ON a.user_id = u.user_ID
             ORDER BY a.log_timestamp DESC
             LIMIT 15"; // ดึงมาแสดง 15 รายการล่าสุด
$result_logs = $conn->query($sql_logs);
if ($result_logs && $result_logs->num_rows > 0) {
    while($row = $result_logs->fetch_assoc()){
        $activity_logs[] = $row;
    }
}
// END: Query ข้อมูลกิจกรรมล่าสุด
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ภาพรวมสถานะพนักงาน - ร้านโรตีลีลา</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../style/navnsidebar.css?v=<?= time() ?>">
    
    <style>
        :root {
            --primary-color: #3498db; --primary-hover: #2980b9; --success-color: #27ae60; --warning-color: #f39c12; --danger-color: #e74c3c; --text-dark: #2c3e50; --text-secondary: #7f8c8d; --bg-color: #f4f7f9; --panel-bg: #ffffff; --border-color: #e0e6ed; --selected-bg: #eaf3fb; --font-family: 'Kanit', sans-serif; --border-radius-lg: 16px;
        }
        body, html { margin: 0; padding: 0; font-family: var(--font-family); background-color: var(--bg-color); color: var(--text-dark); }
        .main-content { margin-top: 60px; padding: 1rem 2rem; transition: margin-left 0.3s ease; }
        .main-content.shifted { margin-left: 250px; }
        .ingredient-manager-container { max-width: 1600px; margin: 0 auto; padding-top: 2rem; }
        .im-header { display: flex; align-items: center; background: var(--panel-bg); border-radius: var(--border-radius-lg); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); padding: 20px 28px; border: 1px solid var(--border-color); }
        .im-header-left { display: flex; align-items: center; gap: 16px; }
        .im-header-icon-wrapper { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
        .im-header-icon-wrapper i { font-size: 1.5rem; }
        .im-title { font-size: 1.75rem; font-weight: 600; color: var(--text-dark); margin: 0 0 4px 0; }
        .im-desc { color: var(--text-secondary); font-size: 0.95rem; margin: 0; }
        .kpi-cards-grid { display: grid; gap: 1.5rem; margin-top: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
        .kpi-card { background-color: var(--panel-bg); border-radius: var(--border-radius-lg); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); border: 1px solid var(--border-color); padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: 1.25rem; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); }
        .kpi-card .card-icon { font-size: 1.5rem; width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .kpi-card .card-details .value { font-size: 2rem; font-weight: 600; }
        .kpi-card .card-details .label { font-size: 0.9rem; color: var(--text-secondary); }
        .dashboard-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-top: 1.5rem; }
        .card { background-color: var(--panel-bg); border-radius: var(--border-radius-lg); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); border: 1px solid var(--border-color); display: flex; flex-direction: column; }
        .card.full-width { grid-column: 1 / -1; } /* คลาสใหม่สำหรับ Card ที่ยาวเต็ม */
        .card-title { font-size: 1.1rem; font-weight: 600; padding: 1.25rem 1.5rem 0 1.5rem; margin-bottom: 1rem; }
        .card-title i { margin-right: 0.75rem; color: var(--primary-color); }
        .table-wrapper { flex-grow: 1; overflow-y: auto; max-height: 350px; padding: 0 1.5rem 1rem 1.5rem; }
        .im-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        .im-table th, .im-table td { padding: 12px 0; text-align: left; border-bottom: 1px solid var(--border-color); }
        .im-table thead { position: sticky; top: 0; background: var(--panel-bg); }
        .im-table th { font-weight: 500; color: var(--text-secondary); }
        .im-table tbody tr:last-child td { border-bottom: none; }
        .no-data-message { text-align: center; padding: 2rem 0; color: var(--text-secondary); }
        .table-wrapper::-webkit-scrollbar { width: 6px; } .table-wrapper::-webkit-scrollbar-track { background: #f1f1f1; } .table-wrapper::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
        .im-status { display: inline-block; padding: 6px 16px; border-radius: 16px; font-size: 0.9rem; font-weight: 600; }
        .im-status { background: #e9f7ef; color: #27ae60; }
        .im-status-expiring { background: #fef5e7; color: #f39c12; }
        .im-status-out { background: #fbeaea; color: #e74c3c; }

        /* START: CSS สำหรับ Activity Log */
        .activity-log-list { list-style: none; padding: 0 1.5rem 1rem 1.5rem; margin: 0; max-height: 350px; overflow-y: auto; }
        .activity-log-list::-webkit-scrollbar { width: 6px; } .activity-log-list::-webkit-scrollbar-track { background: #f1f1f1; } .activity-log-list::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
        .activity-log-item { display: flex; gap: 1rem; padding: 1rem 0; border-bottom: 1px solid var(--border-color); }
        .activity-log-item:last-child { border-bottom: none; }
        .activity-log-icon { font-size: 1rem; color: var(--primary-color); width: 24px; text-align: center; margin-top: 4px; }
        .activity-log-details .action { font-weight: 500; }
        .activity-log-details .action strong { color: var(--text-dark); }
        .activity-log-details .time { font-size: 0.85rem; color: var(--text-secondary); }
        /* END: CSS สำหรับ Activity Log */
    </style>
</head>
<body>
    <?php include('../layout/navbar.php'); ?>
    <?php include('../layout/sidebar.php'); ?>
    
    <div class="main-content">
        <div class="ingredient-manager-container">
            <div class="im-header">
                <div class="im-header-left">
                    <div class="im-header-icon-wrapper" style="color: #16a085; background-color: #e8f6f3;"><i class="fas fa-clipboard-user"></i></div>
                    <div>
                        <h1 class="im-title">ภาพรวมการเข้างาน</h1>
                        <p class="im-desc">สถานะการเข้างาน ขาด และมาสายของพนักงานวันที่: <?= formatThaiDateOnly($today) ?></p>
                    </div>
                </div>
            </div>

            <div class="kpi-cards-grid">
                <div class="kpi-card">
                    <div class="card-icon" style="color: #27ae60; background-color: #e9f7ef;"><i class="fa-solid fa-user-check"></i></div>
                    <div class="card-details"><div class="value"><?= $present_count ?></div><div class="label">พนักงานเข้างาน</div></div>
                </div>
                <div class="kpi-card">
                    <div class="card-icon" style="color: #e74c3c; background-color: #fbeaea;"><i class="fa-solid fa-user-times"></i></div>
                    <div class="card-details"><div class="value"><?= $absent_count ?></div><div class="label">ขาด / ยังไม่เข้างาน</div></div>
                </div>
            </div>

            <!-- START: Card สำหรับแสดงกิจกรรมล่าสุด -->
            <div class="dashboard-grid" style="grid-template-columns: 1fr;"> <!-- ปรับให้มี 1 column เพื่อให้ card นี้ยาวเต็ม -->
                 <div class="card full-width">
                    <h3 class="card-title"><i class="fa-solid fa-history"></i> การทำงานล่าสุดของพนักงาน</h3>
                    <ul class="activity-log-list">
                       <?php if (!empty($activity_logs)): ?>
                           <?php foreach ($activity_logs as $log): ?>
                               <li class="activity-log-item">
                                   <div class="activity-log-icon"><i class="fas fa-history"></i></div>
                                   <div class="activity-log-details">
                                       <div class="action"><strong><?= htmlspecialchars($log['full_name']) ?></strong> <?= htmlspecialchars($log['action']) ?></div>
                                       <div class="time"><?= time_ago($log['log_timestamp']) ?></div>
                                   </div>
                               </li>
                           <?php endforeach; ?>
                       <?php else: ?>
                           <li class="no-data-message" style="border: none;">ยังไม่มีกิจกรรมล่าสุดในระบบ</li>
                       <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="dashboard-grid">
                <div class="card">
                    <h3 class="card-title"><i class="fa-solid fa-right-to-bracket"></i> พนักงานที่เข้างานและมาสาย</h3>
                    <div class="table-wrapper">
                        <table class="im-table">
                            <thead><tr><th>ชื่อ-สกุล</th><th style="text-align: center;">สถานะ</th><th style="text-align: right;">เวลาเข้างาน</th></tr></thead>
                            <tbody>
                                <?php
                                    $sql_present_details = "SELECT u.full_name, a.check_in_time FROM attendance a JOIN users u ON a.user_id = u.user_ID WHERE DATE(a.check_in_time) = '$today' AND a.status IN ('present', 'late') GROUP BY u.user_ID ORDER BY a.check_in_time ASC";
                                    $result_present_details = $conn->query($sql_present_details);
                                    if ($result_present_details->num_rows > 0) {
                                        while ($row = $result_present_details->fetch_assoc()) {
                                            $check_in = new DateTime($row['check_in_time']);
                                            $start_time_today = new DateTime($today . ' 09:05:00');
                                            $is_late = $check_in > $start_time_today;
                                            $status_class = $is_late ? 'im-status-expiring' : 'im-status';
                                            $status_text = $is_late ? 'มาสาย' : 'เข้างาน';
                                            echo "<tr><td>" . htmlspecialchars($row['full_name']) . "</td><td style='text-align: center;'><span class='im-status {$status_class}'>{$status_text}</span></td><td style='text-align: right;'>" . date("H:i", strtotime($row['check_in_time'])) . " น.</td></tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' class='no-data-message'>ไม่มีพนักงานเข้าสู่ระบบในวันนี้</td></tr>";
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title"><i class="fa-solid fa-user-slash"></i> พนักงานที่ขาดงาน</h3>
                    <div class="table-wrapper">
                        <table class="im-table">
                            <thead><tr><th>ชื่อ-สกุล</th><th style="text-align: center;">สถานะ</th></tr></thead>
                            <tbody>
                                <?php
                                    $sql_absent_details = "SELECT full_name FROM users WHERE role IN ('sales', 'accountant', 'manager', 'admin', 'owner') AND user_ID NOT IN (SELECT user_id FROM attendance WHERE DATE(check_in_time) = '$today')";
                                    $result_absent_details = $conn->query($sql_absent_details);
                                    if ($result_absent_details->num_rows > 0) {
                                        while ($row = $result_absent_details->fetch_assoc()) {
                                            echo "<tr><td>" . htmlspecialchars($row['full_name']) . "</td><td style='text-align: center;'><span class='im-status im-status-out'>ขาดงาน</span></td></tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='2' class='no-data-message'>ไม่มีพนักงานขาดงานในวันนี้</td></tr>";
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- ===== END: โครงสร้าง HTML ใหม่ ===== -->
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

<?php
$conn->close();
?>