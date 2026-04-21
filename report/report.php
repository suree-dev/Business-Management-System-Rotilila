<?php
// ... ส่วน PHP ทั้งหมดเหมือนเดิม ไม่มีการเปลี่ยนแปลง ...
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['owner', 'manager'])) {
    header('Location: ../auth/login.php');
    exit;
}

include('../data/db_connect.php');

$content_type = $_GET['content_type'] ?? 'sales'; 
$report_type = $_GET['report_type'] ?? 'daily';
$start_date_input = date('Y-m-d');
$end_date_input = date('Y-m-d');
$report_title_date = '';

switch ($report_type) {
    case 'date_range':
        $start_date_input = $_GET['start_date'] ?? date('Y-m-d');
        $end_date_input = $_GET['end_date'] ?? date('Y-m-d');
        $report_title_date = "สำหรับวันที่ " . format_thai_date_short($start_date_input);
        if ($start_date_input != $end_date_input) {
            $report_title_date .= " - " . format_thai_date_short($end_date_input);
        }
        break;
    case 'monthly':
        $month_input = $_GET['month_year'] ?? date('Y-m');
        $start_date_input = date('Y-m-01', strtotime($month_input));
        $end_date_input = date('Y-m-t', strtotime($month_input));
        $report_title_date = "ประจำเดือน " . format_thai_month_year_short($month_input);
        break;
    case 'yearly':
        $year_input_be = $_GET['year'] ?? (date('Y') + 543);
        $year_input_ad = $year_input_be - 543;
        $start_date_input = $year_input_ad . '-01-01';
        $end_date_input = $year_input_ad . '-12-31';
        $report_title_date = "ประจำปี พ.ศ. " . $year_input_be;
        break;
    case 'daily':
    default:
        $selected_date = $_GET['selected_date'] ?? date('Y-m-d');
        $start_date_input = $selected_date;
        $end_date_input = $selected_date;
        $report_title_date = "สำหรับวันที่ " . format_thai_date_short($start_date_input);
        break;
}

$end_date_for_query = $end_date_input . ' 23:59:59';
$report_main_title = ''; 

if ($content_type === 'sales') {
    $report_main_title = "Dashboard สรุปยอดขาย";
    // ... ส่วน Query ข้อมูลยอดขายทั้งหมดเหมือนเดิม ...
    $stmt_summary = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) AS total_sales, COUNT(order_id) AS total_orders, COALESCE(AVG(total_amount), 0) AS avg_order_value FROM order1 WHERE status = 'paid' AND Order_date BETWEEN ? AND ?");
    if (!$stmt_summary) { die("SQL error (summary): " . $conn->error); }
    $stmt_summary->bind_param("ss", $start_date_input, $end_date_for_query);
    $stmt_summary->execute();
    $summary_result = $stmt_summary->get_result()->fetch_assoc();
    $total_sales = $summary_result['total_sales'] ?? 0;
    $stmt_payment = $conn->prepare("SELECT payment_method, SUM(total_amount) AS total, COUNT(order_id) AS count FROM order1 WHERE status = 'paid' AND Order_date BETWEEN ? AND ? GROUP BY payment_method");
    if (!$stmt_payment) { die("SQL error (payment): " . $conn->error); }
    $stmt_payment->bind_param("ss", $start_date_input, $end_date_for_query);
    $stmt_payment->execute();
    $payment_methods_data = $stmt_payment->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_service = $conn->prepare("SELECT service_type, SUM(total_amount) AS total, COUNT(order_id) AS count FROM order1 WHERE status = 'paid' AND Order_date BETWEEN ? AND ? GROUP BY service_type");
    if (!$stmt_service) { die("SQL error (service): " . $conn->error); }
    $stmt_service->bind_param("ss", $start_date_input, $end_date_for_query);
    $stmt_service->execute();
    $service_types_data = $stmt_service->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_top_menus = $conn->prepare("SELECT m.menu_name, SUM(i.quantity) AS total_quantity, SUM(m.menu_price * i.quantity) AS total_value, COUNT(DISTINCT o.order_id) AS order_count FROM order_items i JOIN menu m ON i.menu_id = m.menu_ID JOIN order1 o ON i.order_id = o.order_id WHERE o.status = 'paid' AND o.Order_date BETWEEN ? AND ? GROUP BY m.menu_ID, m.menu_name ORDER BY order_count DESC, total_value DESC LIMIT 5");
    if (!$stmt_top_menus) { die("SQL error (top menus): " . $conn->error); }
    $stmt_top_menus->bind_param("ss", $start_date_input, $end_date_for_query);
    $stmt_top_menus->execute();
    $top_menus_data = $stmt_top_menus->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_category = $conn->prepare("SELECT c.cate_name, SUM(m.menu_price * i.quantity) AS total_value FROM order_items i JOIN menu m ON i.menu_id = m.menu_ID JOIN order1 o ON i.order_id = o.order_id JOIN menu_categories mc ON m.menu_ID = mc.menu_ID JOIN categories c ON mc.cate_id = c.cate_id WHERE o.status = 'paid' AND o.Order_date BETWEEN ? AND ? GROUP BY c.cate_name ORDER BY total_value DESC");
    if (!$stmt_category) { die("SQL error (category): " . $conn->error); }
    $stmt_category->bind_param("ss", $start_date_input, $end_date_for_query);
    $stmt_category->execute();
    $category_sales_data = $stmt_category->get_result()->fetch_all(MYSQLI_ASSOC);
    $detailed_data = [];
    $details_section_title = '';
    $details_table_headers = [];
    switch ($report_type) {
        case 'yearly': $details_section_title = "สรุปยอดขายรายเดือน"; $details_table_headers = ['เดือน', 'จำนวนออเดอร์', 'ยอดขายรวม (บาท)']; $stmt_details = $conn->prepare("SELECT DATE_FORMAT(Order_date, '%Y-%m') AS month, COUNT(order_id) AS order_count, SUM(total_amount) AS monthly_sales FROM order1 WHERE status = 'paid' AND Order_date BETWEEN ? AND ? GROUP BY month ORDER BY month ASC"); break;
        case 'monthly': case 'date_range': $details_section_title = "สรุปยอดขายรายวัน"; $details_table_headers = ['วันที่', 'จำนวนออเดอร์', 'ยอดขายรวม (บาท)']; $stmt_details = $conn->prepare("SELECT DATE(Order_date) AS sale_date, COUNT(order_id) AS order_count, SUM(total_amount) AS daily_sales FROM order1 WHERE status = 'paid' AND Order_date BETWEEN ? AND ? GROUP BY sale_date ORDER BY sale_date ASC"); break;
        case 'daily': default: $details_section_title = "รายการออเดอร์ประจำวัน"; $details_table_headers = ['เวลา', 'รหัสออเดอร์', 'ยอดรวม (บาท)']; $stmt_details = $conn->prepare("SELECT order_id, total_amount, TIME(Order_date) AS sale_time FROM order1 WHERE status = 'paid' AND Order_date BETWEEN ? AND ? ORDER BY sale_time ASC"); break;
    }
    if (isset($stmt_details)) { $stmt_details->bind_param("ss", $start_date_input, $end_date_for_query); $stmt_details->execute(); $detailed_data = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC); $stmt_details->close(); }
    $insight_text = ""; $recommendation_text = "";
    if ($total_sales > 0) {
        $insight_parts_html = []; if (!empty($payment_methods_data)) { $payment_details_html = []; foreach ($payment_methods_data as $pm) { $method_name = translate_key($pm['payment_method'], 'payment_method'); $payment_details_html[] = "'" . htmlspecialchars($method_name) . "' จำนวน " . number_format($pm['count']) . " ครั้ง " . "<span class='insight-amount'>" . number_format($pm['total'], 2) . " บาท</span>"; } if (!empty($payment_details_html)) { $insight_parts_html[] = "ลูกค้าชำระเงินผ่าน " . implode(" และ ", $payment_details_html); } } if (!empty($service_types_data)) { $service_details_html = []; foreach ($service_types_data as $st) { $service_name = translate_key($st['service_type'], 'service_type'); $service_details_html[] = "'" . htmlspecialchars($service_name) . "' จำนวน " . number_format($st['count']) . " บิล " . "<span class='insight-amount'>" . number_format($st['total'], 2) . " บาท</span>"; } if (!empty($service_details_html)) { $insight_parts_html[] = "ยอดขายมาจากการ " . implode(" และ ", $service_details_html); } } $insight_text = !empty($insight_parts_html) ? implode("<br><br>", $insight_parts_html) : "ยังไม่มีข้อมูลเพียงพอสำหรับสร้างข้อมูลเชิงลึก";
        if (count($top_menus_data) > 1) { $top_menu_orders = $top_menus_data[0]['order_count']; $second_menu_orders = $top_menus_data[1]['order_count']; if ($second_menu_orders > 0 && ($top_menu_orders / $second_menu_orders) > 1.5) { $recommendation_text = "เมนู '".htmlspecialchars($top_menus_data[0]['menu_name'])."' ได้รับความนิยมอย่างชัดเจน ลองพิจารณาจัดโปรโมชั่นสำหรับเมนู '".htmlspecialchars($top_menus_data[1]['menu_name'])."' เพื่อกระตุ้นความสนใจของลูกค้า"; } else { $recommendation_text = "เมนูยอดนิยม 5 อันดับแรกมีจำนวนการสั่งซื้อที่ใกล้เคียงกัน ควรส่งเสริมการขายเมนูเหล่านี้อย่างสม่ำเสมอ หรือพิจารณาจัดเป็นเซ็ตโปรโมชั่นเพื่อเพิ่มยอดขายโดยรวม"; } } elseif (!empty($top_menus_data)) { $recommendation_text = "เมนู '".htmlspecialchars($top_menus_data[0]['menu_name'])."' เป็นเมนูที่ลูกค้าสั่งบ่อยที่สุด ควรตรวจสอบให้แน่ใจว่ามีวัตถุดิบเพียงพอต่อความต้องการเสมอ"; } else { $recommendation_text = "ข้อมูลเมนูยอดนิยมยังมีไม่มากพอที่จะให้คำแนะนำได้"; }
    } else { $insight_text = "ไม่มีข้อมูลยอดขายในช่วงเวลาที่เลือก"; $recommendation_text = "ไม่มีข้อมูลเพียงพอสำหรับสร้างข้อเสนอแนะ"; }

} elseif ($content_type === 'ingredients') {
    // --- ✨ START: อัปเดตส่วนรายงานวัตถุดิบทั้งหมด ✨ ---
    $report_main_title = "Dashboard สรุปการใช้วัตถุดิบ";
    
    $stmt_ingredients = $conn->prepare("
        SELECT 
            m.material_name AS ingredient_name,
            SUM(oi.quantity * ing.ingr_quantity) AS total_used,
            u.Unit_name AS unit
        FROM order_items oi
        JOIN order1 o ON oi.order_id = o.order_id
        JOIN ingredient ing ON oi.menu_id = ing.Recipes_id
        JOIN material m ON ing.material_id = m.material_id
        JOIN unit u ON ing.Unit_id = u.Unit_id
        WHERE o.status = 'paid' AND o.Order_date BETWEEN ? AND ?
        GROUP BY m.material_id, m.material_name, u.Unit_name
        ORDER BY total_used DESC
    ");

    if (!$stmt_ingredients) { die("SQL error (ingredients): " . $conn->error); }
    
    $stmt_ingredients->bind_param("ss", $start_date_input, $end_date_for_query);
    $stmt_ingredients->execute();
    $ingredient_usage_data = $stmt_ingredients->get_result()->fetch_all(MYSQLI_ASSOC);

    // เตรียมข้อมูลสำหรับ KPI Boxes
    $total_unique_ingredients = count($ingredient_usage_data);
    $top_ingredient_name = "-";
    $top_ingredient_usage = "-";
    if ($total_unique_ingredients > 0) {
        $top_ingredient_name = $ingredient_usage_data[0]['ingredient_name'];
        $top_ingredient_usage = number_format($ingredient_usage_data[0]['total_used'], 2) . " " . htmlspecialchars($ingredient_usage_data[0]['unit']);
    }

    // เตรียมข้อมูลสำหรับ Chart (แสดง 10 อันดับแรก)
    $top_ingredients_for_chart = array_slice($ingredient_usage_data, 0, 10);

    // เตรียมข้อความแนะนำ
    $ingredient_recommendation = "ยังไม่มีข้อมูลเพียงพอสำหรับสร้างข้อเสนอแนะ";
    if ($total_unique_ingredients > 0) {
        $ingredient_recommendation = "วัตถุดิบ '" . htmlspecialchars($top_ingredient_name) . "' ถูกใช้ไปในปริมาณมากที่สุด ควรมีการตรวจสอบสต็อกคงเหลือและวางแผนการสั่งซื้อล่วงหน้าเพื่อไม่ให้วัตถุดิบขาดสต็อก";
    }
    // --- ✨ END: อัปเดตส่วนรายงานวัตถุดิบทั้งหมด ✨ ---
}

function format_thai_date_full($date) { if (!$date) return "-"; $ts = strtotime($date); $months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"]; $month_name = $months[date('n', $ts) - 1]; $year_be = date('Y', $ts) + 543; return date('j', $ts) . " " . $month_name . " " . $year_be; }
function format_thai_date_short($date) { if (!$date) return "-"; $ts = strtotime($date); $months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."]; $month_name = $months[date('n', $ts) - 1]; $year_be = date('Y', $ts) + 543; return date('j', $ts) . " " . $month_name . " " . $year_be; }
function format_thai_month_year($date_str) { if (!$date_str) return "-"; $ts = strtotime($date_str . '-01'); $months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"]; $month_name = $months[date('n', $ts) - 1]; $year_be = date('Y', $ts) + 543; return $month_name . " " . $year_be; }
function format_thai_month_year_short($date_str) { if (!$date_str) return "-"; $ts = strtotime($date_str . '-01'); $months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"]; $month_name = $months[date('n', $ts) - 1]; $year_be = date('Y', $ts) + 543; return $month_name . " " . $year_be; }
function translate_key($key, $type) { $translations = [ 'payment_method' => ['cash' => 'เงินสด', 'qr' => 'QR Code'], 'service_type' => ['dine-in' => 'ทานที่ร้าน', 'takeaway' => 'สั่งกลับบ้าน'] ]; return $translations[$type][$key] ?? $key; }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($report_main_title) ?> - ร้านโรตีลีลา</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style/navnsidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://unpkg.com/flatpickr/dist/plugins/monthSelect/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    /* --- ✨ START: MODERN UI DECORATION ✨ --- */

:root {
    --primary-color: #3498db;
    --primary-hover: #2980b9;
    --success-color: #27ae60;
    --success-light-bg: #e8f5e9;
    --text-dark: #2c3e50;
    --text-secondary: #7f8c8d;
    --text-body: #34495e;
    --bg-color: #f4f7f9;
    --panel-bg: #ffffff;
    --border-color: #e0e6ed;
    --selected-bg: #eaf3fb;
    --disabled-color: #bdc3c7;
    --font-family: 'Kanit', sans-serif;
    --border-radius-md: 12px;
    --border-radius-lg: 16px;
    --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body, html {
    margin: 0;
    padding: 0;
    font-family: var(--font-family);
    background-color: var(--bg-color);
    color: var(--text-body);
}

.navbar {
  background: rgba(44, 62, 80, 0.95);
  color: #fff;
  padding: 0.5rem 1.5rem; /* <<< ปรับขนาด Padding เพื่อลดความสูง Navbar */
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  min-height: 60px; /* <<< กำหนดความสูงขั้นต่ำ */
}

.toggle-sidebar {
  cursor: pointer;
  font-size: 1.5rem;
  margin-right: 1rem;
}

.sidebar {
  width: 250px;
  background: #2c3e50;
  color: #fff;
  position: fixed;
  top: 60px; /* <<< ปรับตำแหน่งให้พอดีกับ Navbar ใหม่ */
  left: -250px;
  height: 100%;
  padding-top: 10px;
  transition: left 0.3s ease;
  z-index: 999;
}

.sidebar.active {
  left: 0;
}

.sidebar ul {
  list-style: none;
  padding: 0;
}

.sidebar ul li a {
  display: block;
  padding: 1rem;
  color: #ecf0f1;
  text-decoration: none;
}

.logout-btn {
  display: block;
  width: 80%;
  margin: 20px auto 20px auto;
  padding: 0.6rem 1rem;
  background-color: #e74c3c;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  cursor: pointer;
  text-align: center;
  text-decoration: none;
  transition: background-color 0.3s ease;
}

.logout-btn:hover {
  background-color: #c0392b;
}

.admin-profile {
  cursor: pointer;
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.8rem;
  color: #ffffff;
  padding: 0.3rem 1rem;
  border-radius: 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.463);
  transition: background-color 0.3s ease;
  background-color: rgba(44, 62, 80, 0.95);
}

.profile-char {
  width: 36px;
  height: 36px;
  background-color: #466380;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 18px;
  color: #fff;
  text-transform: uppercase;
}

.user-info {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.username {
  font-weight: 600;
  font-size: 0.9rem;
  color: #ffffff;
}

.role {
  font-size: 0.75rem;
  color: #d4e4ff;
}


.main-content {
    margin-top: 60px; /* <<< ปรับระยะห่างให้พอดีกับ Navbar ใหม่ */
    padding: 2rem;
    transition: margin-left 0.3s ease;
    margin-left: 0;
}

.main-content.shifted {
    margin-left: 250px;
}

    /* --- Report Controls (แถบฟิลเตอร์) --- */
    .report-controls {
        background-color: var(--panel-bg);
        padding: 1.5rem;
        border-radius: 12px; /* เพิ่มความโค้งมน */
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); /* เงาที่นุ่มนวลขึ้น */
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.5rem;
        border: 1px solid var(--border-color);
            width: 75rem;
    margin-left: -1px;
    }

    .filter-form {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .filter-form label {
        font-weight: 500;
        color: var(--text-dark);
        margin-top: 10px;
    }
    .date-input, .select-input, .month-input, .year-input {
        padding: 0.6rem 0.8rem;
        border: 1px solid #ccc;
        border-radius: 8px; /* เพิ่มความโค้งมน */
        font-family: 'Kanit', sans-serif;
        font-size: 0.95rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .date-input:focus, .select-input:focus, .month-input:focus, .year-input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(41, 128, 185, 0.2);
    }
    .filter-btn, .print-btn {
        padding: 0.7rem 1.5rem;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        transition: background-color 0.2s ease, transform 0.1s ease;
        font-weight: 500;
    }
    .filter-btn { background-color: var(--primary-color); margin-right: 80px; margin-top: 8px;}
    .print-btn { background-color: var(--success-color);         margin-left: 990px;
    margin-top: -95px;}
    .filter-btn:hover { background-color: #3498db; }
    .print-btn:hover { background-color: #1abc9c; }
    .filter-btn:active, .print-btn:active { transform: scale(0.98); }

    /* --- Report Page (การ์ดรายงานหลัก) --- */
    .report-page {
        background-color: var(--panel-bg);
        padding: 2.5rem;
        border-radius: 12px;
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.07);
        max-width: 950px;
        margin: auto;
    }

    .report-header { text-align: center; margin-bottom: 3rem; }
    .report-header h1 { font-size: 2.2rem; font-weight: 600; color: var(--text-dark); }
    .report-header h2 { font-size: 1.5rem; font-weight: 400; color: var(--text-secondary); margin-top: 0.5rem;}
    .report-header h3 { font-size: 1.2rem; font-weight: 500; color: var(--primary-color); margin-top: 1rem; }

    .report-section { margin-bottom: 3.5rem; }
    .report-section > h3 {
        font-size: 1.5rem;
        font-weight: 600;
        border-bottom: 3px solid var(--primary-color);
        padding-bottom: 0.75rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--text-dark);
    }
    .report-section > h3 i {
        color: var(--primary-color);
    }

    /* --- KPI Boxes (กล่องสรุปยอด) --- */
    .kpi-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* ใช้ Grid เพื่อการจัดวางที่แม่นยำ */
        gap: 1.5rem;
        text-align: left;
    }
    .kpi-box {
        background-color: #fff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.5rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }
    .kpi-label { font-size: 1rem; color: var(--text-secondary); margin-bottom: 0.5rem; font-weight: 500; }
    .kpi-value {
        font-size: 2.5rem;
        font-weight: 600;
        line-height: 1.2;
    }
    /* แยกสีให้แต่ละ KPI เพื่อให้เด่นชัด */
    .kpi-box:nth-child(1) .kpi-value { color: var(--primary-color); }
    .kpi-box:nth-child(2) .kpi-value { color: var(--success-color); }
    .kpi-box:nth-child(3) .kpi-value { color: var(--warning-color); }

    /* --- Charts & Data Grid --- */
    .chart-grid {
        display: contents;
        grid-template-columns: 1fr 1fr;
        gap: 2.5rem;
        align-items: flex-start; /* จัดให้ชิดบน */
    }
    .chart-grid > div,
    .chart-grid table {
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.5rem;
        background-color: #fff;
    }
    .chart-grid h4, .report-section h4 { /* สไตล์หัวข้อของกราฟและตาราง */
        font-size: 1.1rem;
        font-weight: 600;
        margin-top: 0;
        margin-bottom: 1.5rem;
        color: var(--text-dark);
    }
    .chart-container { position: relative; height: 300px; padding: 0 !important; border: none !important; }

    /* --- Tables --- */
    table {
        width: 100%;
        border-collapse: collapse;
        background-color: #fff;
    }
    th, td {
        padding: 1rem; /* เพิ่ม padding */
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }
    thead tr {
        border-bottom: 2px solid #bdc3c7;
    }
    th {
        background-color: transparent;
        font-weight: 600;
        color: var(--text-dark);
        font-size: 0.95rem;
    }
    /* Zebra-striping: สลับสีแถวเพื่อให้อ่านง่าย */
    tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    tbody tr:hover {
        background-color: #ecf0f1; /* Highlight แถวเมื่อเมาส์ชี้ */
    }

    /* --- Insight & Recommendation Boxes --- */
    .summary-box {
        padding: 1.5rem;
        border-radius: 12px;
        margin-top: 2rem;
    }
    .summary-box h4 {
        margin-top: 0;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.2rem;
        font-weight: 600;
    }
    .summary-box p { font-size: 1.05rem; line-height: 1.7; margin: 0;}
    .insight-box {
        background-color: #eaf3fb;
        border-left: 6px solid var(--info-color);
        color: #1a5276;
    }
    .insight-box h4 { color: #1a5276; }
    .insight-box p {
        font-size: 1.1rem; /* ขยายฟอนต์ให้อ่านง่ายขึ้น */
        line-height: 2;    /* เพิ่มระยะห่างระหว่างบรรทัด */
    }

    .insight-amount {
        font-weight: 600;
        color: #148f77; /* สีเขียวเข้ม อ่านง่าย */
        background-color: rgba(22, 160, 133, 0.1); /* พื้นหลังสีเขียวอ่อนโปร่งแสง */
        padding: 3px 8px;
        border-radius: 8px;
        font-size: 0.9em; /* ขนาดเล็กกว่าข้อความหลักเล็กน้อย */
        white-space: nowrap; /* ป้องกันไม่ให้ข้อความในป้ายสีตัดกลางบรรทัด */
    }
    .recommend-box {
        background-color: #e8f5e9;
        border-left: 6px solid var(--success-color);
        color: #0e6251;
    }
    .recommend-box h4 { color: #0e6251; }

    /* --- Responsive Design --- */
    @media (max-width: 992px) {
        .chart-grid { grid-template-columns: 1fr; }
        .main-content { padding: 1.5rem; }
        .report-page { padding: 1.5rem; }
    }
    @media (max-width: 768px) {
        .kpi-container { grid-template-columns: 1fr; gap: 1rem; }
        .filter-form { gap: 0.8rem; }
    }

/* --- ✨ START: FINAL & COMPLETE PRINT STYLES ✨ --- */
@media print {
    /* --- General Settings --- */
    *::before,
    *::after,
    *,
    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-shadow: none !important; /* ลบเงาทุกชนิด */
        background: transparent !important;
    }

    body {
        background-color: #fff !important; /* พื้นหลังสีขาว */
        font-family: 'Kanit', sans-serif;
        font-size: 10.5pt; /* เล็กพอดี A4 */
        color: #000;
        margin: 0;
        padding: 0;
        line-height: 1.4;
    }

    /* --- Hide Unnecessary Elements --- */
    .navbar, .sidebar, .report-controls, .btn, .no-print {
        display: none !important;
    }

    /* --- Page Layout --- */
    .main-content {
        max-width: 100%; /* ใช้พื้นที่แนวนอนเต็มกระดาษ */
        margin: 0 auto; /* จัดให้อยู่กลางหน้า */
        padding: 15mm; /* กำหนด padding ให้เหมาะกับ A4 */
    }

    /* ป้องกัน sidebar เปิดแล้วเลื่อน content */
    body.sidebar-open .main-content {
        margin-left: 0;
    }

    /* ปรับ table ให้พอดีหน้า */
    table {
        width: 100% !important;
        border-collapse: collapse;
        page-break-inside: auto;
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    th, td {
        padding: 4px 6px; /* ลด padding ให้พอดีกับ A4 */
        border: 1px solid #000; /* เพิ่มเส้นขอบสำหรับพิมพ์ */
    }

    /* ปรับหัวข้อ */
    h1, h2, h3, h4, h5, h6 {
        page-break-after: avoid;
        color: #000 !important;
    }
}
@media (max-width: 900px) { body.sidebar-open .main-content { margin-left: 0; } }
    
    @page {
        size: A4;
        margin: 2cm; /* กำหนดระยะขอบกระดาษ */
    }

    /* --- Report Structure --- */
    .report-header {
        text-align: center;
        margin-bottom: 2rem;
        page-break-after: avoid; /* ไม่ให้ขึ้นหน้าใหม่หลัง header */
    }
    .report-header h1 { font-size: 18pt; font-weight: bold; }
    .report-header h2 { font-size: 14pt; }
    .report-header h3 { font-size: 12pt; }
    
    .report-section {
        page-break-inside: avoid; /* พยายามไม่ให้ section ถูกตัดกลางหน้า */
        margin-bottom: 2.5rem;
    }
    .report-section > h3 {
        font-size: 14pt;
        border-bottom: 2px solid var(--primary-color) !important;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
        color: #000 !important;
    }

    /* --- KPI Boxes --- */
    /* รักษารูปแบบ Flexbox ให้อยู่ในแถวเดียวเหมือนบนหน้าจอ */
    .kpi-container {
        display: flex !important;
        flex-direction: row !important;
        gap: 1.5rem !important;
        justify-content: space-between !important;
    }
    .kpi-box {
        flex: 1;
        background-color: #f8f9fa !important; /* คืนสีพื้นหลัง */
        border: 1px solid var(--border-color) !important; /* คืนเส้นขอบ */
        border-radius: 8px !important;
        padding: 1.25rem;
        text-align: center;
    }
    .kpi-value { font-size: 2rem; font-weight: 600; color: var(--primary-color) !important; }
    .kpi-label { font-size: 0.9rem; color: var(--text-secondary) !important; margin-top: 0.25rem; }

    /* --- Charts & Grids --- */
    /* รักษารูปแบบ Grid 2 คอลัมน์เหมือนบนหน้าจอ */
    .chart-grid {
        display: contents;
        grid-template-columns: 1fr 1fr !important;
        gap: 2rem !important;
        align-items: flex-start;
    }
    .chart-grid > div {
        page-break-inside: avoid; /* ป้องกันการตัดกลางระหว่างหัวข้อกับกราฟ */
    }
    .chart-container {
        position: relative;
        height: 280px; /* ลดความสูงเล็กน้อยสำหรับกระดาษ A4 */
        width: 100%;
    }
    canvas {
        max-width: 100% !important;
        height: auto !important;
    }

    /* --- Summary & Insight Boxes --- */
    .summary-box {
        padding: 1.5rem;
        margin-top: 1.5rem;
        border-radius: 8px;
    }
    .insight-box {
        background-color: #eaf3fb !important;
        border-left: 5px solid var(--info-color) !important;
    }
    .recommend-box {
        background-color: #e8f5e9 !important;
        border-left: 5px solid var(--success-color) !important;
    }
    .summary-box p, .summary-box h4 {
      color: #000 !important;
    }

    /* --- Tables --- */
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 0.7rem; text-align: left; border-bottom: 1px solid #dee2e6; }
    th { background-color: #f8f9fa !important; }

    /* --- Page Breaks --- */
    .print-page-break {
        page-break-before: always;
    }
/* --- ✨ END: FINAL & COMPLETE PRINT STYLES ✨ --- */
    </style>
</head>
<body>
    <?php include('../layout/navbar.php'); ?>
    <?php include('../layout/sidebar.php'); ?>

    <div class="main-content">
        <div class="report-controls">
            <form method="get" class="filter-form">
                <div class="form-group">
                    <label for="content_type">เนื้อหารายงาน:</label>
                    <select name="content_type" id="content_type_select" class="select-input">
                        <option value="sales" <?= $content_type == 'sales' ? 'selected' : '' ?>>สรุปยอดขาย</option>
                        <option value="ingredients" <?= $content_type == 'ingredients' ? 'selected' : '' ?>>สรุปการใช้วัตถุดิบ</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="report_type">ประเภทรายงาน:</label>
                    <select name="report_type" id="report_type_select" class="select-input">
                        <option value="daily" <?= $report_type == 'daily' ? 'selected' : '' ?>>รายวัน</option>
                        <option value="date_range" <?= $report_type == 'date_range' ? 'selected' : '' ?>>เลือกช่วงวัน</option>
                        <option value="monthly" <?= $report_type == 'monthly' ? 'selected' : '' ?>>รายเดือน</option>
                        <option value="yearly" <?= $report_type == 'yearly' ? 'selected' : '' ?>>รายปี</option>
                    </select>
                </div>
                <div id="daily_filter" class="form-group"><label for="selected_date">เลือกวันที่:</label><input type="text" id="selected_date" name="selected_date" class="date-input"></div>
                <div id="range_filter" class="form-group"><label for="start_date">ตั้งแต่:</label><input type="text" id="start_date" name="start_date" class="date-input"><label for="end_date">ถึง:</label><input type="text" id="end_date" name="end_date" class="date-input"></div>
                <div id="monthly_filter" class="form-group"><label for="month_year">เลือกเดือน:</label><input type="text" id="month_year" name="month_year" class="month-input"></div>
                <div id="yearly_filter" class="form-group"><label for="year">เลือกปี พ.ศ.:</label><input type="number" id="year" name="year" class="year-input" min="2563" max="2642" value="<?= htmlspecialchars($_GET['year'] ?? (date('Y') + 543)) ?>"></div>
                <button type="submit" class="filter-btn"><i class="fas fa-filter"></i> กรองข้อมูล</button>
            </form>
            <button onclick="window.print()" class="print-btn"><i class="fas fa-print"></i> พิมพ์รายงาน</button>
        </div>

        <div class="report-page">
            <header class="report-header">
                <h1><?= htmlspecialchars($report_main_title) ?></h1>
                <h2>ร้านโรตีลีลา</h2>
                <h3><?= htmlspecialchars($report_title_date) ?></h3>
            </header>

            <?php if ($content_type === 'sales'): ?>
                <!-- === TEMPLATE รายงานสรุปยอดขาย (โค้ดเดิมทั้งหมด) === -->
                <section class="report-section"><h3><i class="fas fa-tachometer-alt"></i> ภาพรวมประสิทธิภาพ</h3><div class="kpi-container"><div class="kpi-box"><div class="kpi-value"><?= number_format($total_sales, 2) ?></div><div class="kpi-label">ยอดขายรวม (บาท)</div></div><div class="kpi-box"><div class="kpi-value"><?= number_format($summary_result['total_orders'] ?? 0) ?></div><div class="kpi-label">จำนวนออเดอร์</div></div><div class="kpi-box"><div class="kpi-value"><?= number_format($summary_result['avg_order_value'] ?? 0, 2) ?></div><div class="kpi-label">บิลเฉลี่ย (บาท)</div></div></div></section>
                <section class="report-section"><h3><i class="fas fa-list-ul"></i> <?= htmlspecialchars($details_section_title) ?></h3><div style="overflow-x:auto;"><table><thead><tr><?php foreach ($details_table_headers as $index => $header): ?><th <?= ($index > 0 && $report_type != 'daily') ? 'style="text-align:right;"' : '' ?>><?= htmlspecialchars($header) ?></th><?php endforeach; ?></tr></thead><tbody><?php if (empty($detailed_data)): ?><tr><td colspan="<?= count($details_table_headers) ?>" style="text-align:center;">ไม่มีข้อมูลในช่วงเวลาที่เลือก</td></tr><?php else: foreach ($detailed_data as $row): ?><tr><?php switch ($report_type): case 'yearly': ?><td><?= format_thai_month_year($row['month']) ?></td><td style="text-align:right;"><?= number_format($row['order_count']) ?></td><td style="text-align:right;"><?= number_format($row['monthly_sales'], 2) ?></td><?php break; ?><?php case 'monthly': case 'date_range': ?><td><?= format_thai_date_full($row['sale_date']) ?></td><td style="text-align:right;"><?= number_format($row['order_count']) ?></td><td style="text-align:right;"><?= number_format($row['daily_sales'], 2) ?></td><?php break; ?><?php case 'daily': default: ?><td><?= htmlspecialchars($row['sale_time']) ?></td><td><?= htmlspecialchars($row['order_id']) ?></td><td style="text-align:right;"><?= number_format($row['total_amount'], 2) ?></td><?php break; ?><?php endswitch; ?></tr><?php endforeach; endif; ?></tbody></table></div></section>
                <section class="report-section"><h3><i class="fas fa-chart-pie"></i> การวิเคราะห์ข้อมูลการขาย</h3><div class="chart-grid"><div><h4>ช่องทางการชำระเงิน</h4><div class="chart-container"><canvas id="paymentPieChart"></canvas></div></div><div><h4>ประเภทบริการ</h4><div class="chart-container"><canvas id="serviceBarChart"></canvas></div></div></div><div class="insight-box summary-box"><h4><i class="fas fa-lightbulb"></i> ข้อมูลเชิงลึก (Insight)</h4><p><?= $insight_text ?></p></div></section>
                <section class="report-section"><h3><i class="fas fa-tags"></i> ยอดขายตามหมวดหมู่</h3><div class="chart-grid"><div><h4>ตารางสรุปยอดขาย</h4><table><thead><tr><th>หมวดหมู่</th><th style="text-align:right;">ยอดขาย (บาท)</th></tr></thead><tbody><?php if(empty($category_sales_data)): ?><tr><td colspan="2" style="text-align:center;">ไม่มีข้อมูล</td></tr><?php else: foreach($category_sales_data as $cat): ?><tr><td><?= htmlspecialchars($cat['cate_name'] ?? 'ไม่ระบุ') ?></td><td style="text-align:right;"><?= number_format($cat['total_value'], 2) ?></td></tr><?php endforeach; endif; ?></tbody></table></div><div><h4>กราฟเปรียบเทียบ</h4><div class="chart-container"><canvas id="categoryBarChart"></canvas></div></div></div></section>
                <section class="report-section print-page-break"><h3><i class="fas fa-utensils"></i> 5 อันดับเมนูยอดนิยม (ตามจำนวนคำสั่งซื้อ)</h3><div class="chart-grid"><div><h4>ตารางสรุป</h4><table><thead><tr><th>ชื่อเมนู</th><th style="text-align:right;">จำนวนออเดอร์</th><th style="text-align:right;">ยอดขาย (บาท)</th></tr></thead><tbody><?php if(empty($top_menus_data)): ?><tr><td colspan="3" style="text-align:center;">ไม่มีข้อมูล</td></tr><?php else: foreach($top_menus_data as $menu): ?><tr><td><?= htmlspecialchars($menu['menu_name']) ?></td><td style="text-align:right;"><?= number_format($menu['order_count']) ?></td><td style="text-align:right;"><?= number_format($menu['total_value'], 2) ?></td></tr><?php endforeach; endif; ?></tbody></table></div><div><h4>กราฟเปรียบเทียบยอดขาย</h4><div class="chart-container"><canvas id="topMenusBarChart"></canvas></div></div></div><div class="recommend-box summary-box"><h4><i class="fas fa-tasks"></i> ข้อเสนอแนะ</h4><p><?= htmlspecialchars($recommendation_text) ?></p></div></section>
            
            <?php elseif ($content_type === 'ingredients'): ?>
                <!-- === ✨ START: TEMPLATE ใหม่สำหรับรายงานวัตถุดิบ ✨ === -->
                <section class="report-section">
                    <h3><i class="fas fa-boxes"></i> ภาพรวมการใช้วัตถุดิบ</h3>
                    <div class="kpi-container">
                        <div class="kpi-box">
                            <div class="kpi-value"><?= number_format($total_unique_ingredients) ?></div>
                            <div class="kpi-label">จำนวนวัตถุดิบที่ใช้ (รายการ)</div>
                        </div>
                        <div class="kpi-box">
                            <div class="kpi-value small-text"><?= htmlspecialchars($top_ingredient_name) ?></div>
                            <div class="kpi-label">วัตถุดิบที่ใช้มากที่สุด</div>
                        </div>
                        <div class="kpi-box">
                            <div class="kpi-value small-text"><?= $top_ingredient_usage ?></div>
                            <div class="kpi-label">ปริมาณที่ใช้ไป</div>
                        </div>
                    </div>
                </section>

                <section class="report-section">
                    <h3><i class="fas fa-chart-bar"></i> 10 อันดับวัตถุดิบที่ใช้มากที่สุด</h3>
                    <div class="chart-grid">
                        <div style="padding-top: 0;"> <!-- ตารางเต็ม -->
                             <h4>ตารางสรุปการใช้งานทั้งหมด</h4>
                            <div style="max-height: 350px; overflow-y: auto;">
                                <table>
                                    <thead><tr><th>ชื่อวัตถุดิบ</th><th style="text-align:right;">ปริมาณที่ใช้</th><th style="text-align:right;">หน่วย</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($ingredient_usage_data)): ?>
                                            <tr><td colspan="3" style="text-align:center;">ไม่มีข้อมูล</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($ingredient_usage_data as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['ingredient_name']) ?></td>
                                                <td style="text-align:right;"><?= number_format($row['total_used'], 2) ?></td>
                                                <td style="text-align:right;"><?= htmlspecialchars($row['unit']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                     <div class="recommend-box summary-box">
                        <h4><i class="fas fa-tasks"></i> ข้อเสนอแนะ</h4>
                        <p><?= htmlspecialchars($ingredient_recommendation) ?></p>
                    </div>
                </section>
                
                 <div class="insight-box summary-box" style="margin-top: 2rem;">
                    <h4><i class="fas fa-info-circle"></i> หมายเหตุ</h4>
                    <p>รายงานส่วนนี้เป็นการคำนวณปริมาณวัตถุดิบโดยประมาณจากยอดขายเมนูต่างๆ ซึ่งจำเป็นต้องมีการตั้งค่าสูตรอาหารในระบบหลังบ้านก่อน (ว่าแต่ละเมนูใช้วัตถุดิบอะไรบ้าง ในปริมาณเท่าไหร่) เพื่อให้ได้ข้อมูลที่แม่นยำ</p>
                </div>
                <!-- === ✨ END: TEMPLATE ใหม่สำหรับรายงานวัตถุดิบ ✨ === -->
            <?php endif; ?>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>
<script src="https://unpkg.com/flatpickr/dist/plugins/monthSelect/index.js"></script>

<!-- ✨ FIX: แก้ไขและจัดระเบียบโค้ด JavaScript ทั้งหมด -->
<script>
// ✨ FIX: สร้างฟังก์ชัน toggleSidebar ใน Global Scope
// เพื่อให้ onclick="toggleSidebar()" ในไฟล์ navbar.php สามารถเรียกใช้งานฟังก์ชันนี้ได้
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.main-content');
    
    // ตรวจสอบว่าหา element เจอหรือไม่ ก่อนที่จะสั่งให้ทำงาน
    if (sidebar && content) {
        sidebar.classList.toggle('active');
        content.classList.toggle('shifted');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // --- PART 1: CHART DRAWING (ทำงานเฉพาะเมื่อเป็นรายงานยอดขาย) ---
    const contentType = '<?= $content_type ?>';
    if (contentType === 'sales') {
        const paymentData = <?= json_encode($payment_methods_data ?? []) ?>;
        const serviceData = <?= json_encode($service_types_data ?? []) ?>;
        const topMenusData = <?= json_encode($top_menus_data ?? []) ?>;
        const categorySalesData = <?= json_encode($category_sales_data ?? []) ?>;
        const translations = {
            'payment_method': { 'cash': 'เงินสด', 'qr': 'QR Code' },
            'service_type': { 'dine-in': 'ทานที่ร้าน', 'takeaway': 'สั่งกลับบ้าน' }
        };
        const chartColors = ['#3498db', '#2ecc71', '#f1c40f', '#e74c3c', '#9b59b6', '#34495e'];
        Chart.defaults.font.family = "'Kanit', sans-serif";
        const chartOptions = { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { display: false } } };
        const pieChartOptions = { ...chartOptions, plugins: { legend: { position: 'top' } } };
        const horizontalBarOptions = { ...chartOptions, indexAxis: 'y' };

        if (document.getElementById('paymentPieChart') && paymentData.length > 0) { new Chart(document.getElementById('paymentPieChart'), { type: 'pie', data: { labels: paymentData.map(item => translations.payment_method[item.payment_method] || item.payment_method), datasets: [{ data: paymentData.map(item => item.total), backgroundColor: chartColors }] }, options: pieChartOptions }); }
        if (document.getElementById('serviceBarChart') && serviceData.length > 0) { new Chart(document.getElementById('serviceBarChart'), { type: 'bar', data: { labels: serviceData.map(item => translations.service_type[item.service_type] || item.service_type), datasets: [{ label: 'ยอดขาย (บาท)', data: serviceData.map(item => item.total), backgroundColor: chartColors }] }, options: horizontalBarOptions }); }
        if (document.getElementById('topMenusBarChart') && topMenusData.length > 0) { new Chart(document.getElementById('topMenusBarChart'), { type: 'bar', data: { labels: topMenusData.map(item => item.menu_name), datasets: [{ label: 'ยอดขาย (บาท)', data: topMenusData.map(item => item.total_value), backgroundColor: chartColors }] }, options: horizontalBarOptions }); }
        if (document.getElementById('categoryBarChart') && categorySalesData.length > 0) { new Chart(document.getElementById('categoryBarChart'), { type: 'bar', data: { labels: categorySalesData.map(item => item.cate_name || 'ไม่ระบุ'), datasets: [{ label: 'ยอดขาย (บาท)', data: categorySalesData.map(item => item.total_value), backgroundColor: chartColors }] }, options: chartOptions }); }
    }

    // --- PART 2: FLATPCIKR INITIALIZATION (ทำงานทุกครั้ง) ---
    function setupThaiDatePicker(selector, config) {
        const commonConfig = { 
            locale: "th",
            onReady: (_, __, inst) => updateDisplay(inst), 
            onChange: (_, __, inst) => updateDisplay(inst), 
            onMonthChange: (_, __, inst) => setTimeout(() => updateDisplay(inst), 100), 
            onYearChange: (_, __, inst) => setTimeout(() => updateDisplay(inst), 100), 
            onOpen: (_, __, inst) => setTimeout(() => updateDisplay(inst), 100) 
        };
        function updateDisplay(inst) {
            const yearInput = inst.calendarContainer.querySelector('.numInput.cur-year'); 
            if (yearInput) { yearInput.value = parseInt(inst.currentYear, 10) + 543; } 
            if (inst.altInput && inst.selectedDates.length > 0) { 
                const selectedDate = inst.selectedDates[0]; 
                const thaiYear = selectedDate.getFullYear() + 543; 
                let formattedString = inst.formatDate(selectedDate, inst.config.altFormat); 
                inst.altInput.value = formattedString.replace(String(selectedDate.getFullYear()), String(thaiYear)); 
            } 
        }
        flatpickr(selector, { ...commonConfig, ...config });
    }

    setupThaiDatePicker("#selected_date", { altInput: true, altFormat: "j F Y", dateFormat: "Y-m-d", defaultDate: "<?= htmlspecialchars($start_date_input) ?>" });
    setupThaiDatePicker("#start_date", { altInput: true, altFormat: "j F Y", dateFormat: "Y-m-d", defaultDate: "<?= htmlspecialchars($start_date_input) ?>" });
    setupThaiDatePicker("#end_date", { altInput: true, altFormat: "j F Y", dateFormat: "Y-m-d", defaultDate: "<?= htmlspecialchars($end_date_input) ?>" });
    setupThaiDatePicker("#month_year", { 
        altInput: true, 
        altFormat: "F Y", 
        dateFormat: "Y-m", 
        defaultDate: "<?= htmlspecialchars($_GET['month_year'] ?? date('Y-m')) ?>", 
        plugins: [new monthSelectPlugin({ shorthand: false, dateFormat: "Y-m", altFormat: "F Y" })] 
    });


    // --- PART 3: FILTER CONTROLS LOGIC (ทำงานทุกครั้ง) ---
    const contentTypeSelect = document.getElementById('content_type_select');
    const reportTypeSelect = document.getElementById('report_type_select');
    const filters = { 
        daily: document.getElementById('daily_filter'), 
        date_range: document.getElementById('range_filter'), 
        monthly: document.getElementById('monthly_filter'), 
        yearly: document.getElementById('yearly_filter') 
    };

    function toggleFilters() { 
        const selectedType = reportTypeSelect.value; 
        Object.values(filters).forEach(filter => { 
            if (filter) filter.style.display = 'none'; 
        }); 
        if (filters[selectedType]) { 
            filters[selectedType].style.display = 'flex'; 
        } 
    }
    
    if (reportTypeSelect) { 
        reportTypeSelect.addEventListener('change', toggleFilters); 
    }
    toggleFilters();
});
</script>

</body>
</html>