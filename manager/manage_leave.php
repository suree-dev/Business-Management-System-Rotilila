<?php
session_start();
require '../data/db_connect.php';

// 1. ตรวจสอบการล็อกอินและสิทธิ์การเข้าถึง
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// อนุญาตให้เฉพาะ 'manager' หรือ 'owner' เข้าถึงหน้านี้
if ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'owner') {
    // หากไม่มีสิทธิ์ ให้แสดงข้อความและหยุดการทำงาน
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

// 2. ดึงข้อมูลคำขอลาที่รอการอนุมัติ (pending)
// เราจะ JOIN ตาราง users เพื่อดึงชื่อเต็มของพนักงานมาแสดงด้วย
$sql = "SELECT lr.*, u.full_name 
        FROM leave_requests lr
        JOIN users u ON lr.user_id = u.user_id
        WHERE lr.status = 'pending'
        ORDER BY lr.start_date ASC";

$result = $conn->query($sql);
$requests = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำขอลา - Roti Leela</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../style/leave.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../style/navnsidebar.css?v=<?= time() ?>">
</head>
<body>

    <?php include('../layout/navbar.php'); ?>
    <?php include('../layout/sidebar.php'); ?>

    <div class="container">
        <h1>รายการคำขอลาที่รอการอนุมัติ</h1>

        <?php
        // แสดงข้อความแจ้งเตือน (ถ้ามี)
        if (isset($_SESSION['message'])) {
            echo '<div class="message ' . $_SESSION['message_type'] . '">' . $_SESSION['message'] . '</div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ชื่อพนักงาน</th>
                        <th>ประเภทการลา</th>
                        <th>วันที่เริ่มลา</th>
                        <th>วันที่สิ้นสุดลา</th>
                        <th>เหตุผล</th>
                        <th class="action-col">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                <td><?php 
                                    // แปลงประเภทการลาเป็นภาษาไทยเพื่อให้อ่านง่าย
                                    $leave_types = ['sick' => 'ลาป่วย', 'personal' => 'ลากิจ', 'vacation' => 'ลาพักร้อน', 'other' => 'ลาอื่นๆ'];
                                    echo $leave_types[$request['leave_type']]; 
                                ?></td>
                                <td><?php echo date("d/m/Y", strtotime($request['start_date'])); ?></td>
                                <td><?php echo date("d/m/Y", strtotime($request['end_date'])); ?></td>
                                <td><?php echo htmlspecialchars($request['reason'] ? $request['reason'] : '-'); ?></td>
                                <td class="action-col">
                                    <form class="action-form" action="../pages/process_leave.php" method="POST">
                                        <input type="hidden" name="leave_id" value="<?php echo $request['leave_id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn-approve">อนุมัติ</button>
                                        <button type="submit" name="action" value="reject" class="btn-reject">ปฏิเสธ</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-requests">ไม่มีคำขอลาที่รอการอนุมัติ</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../js/leave.js"></script>
</body>
</html>