<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../data/db_connect.php');

$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'owner' && $role !== 'admin') {
    echo "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการสิทธิ์ผู้ใช้งาน - ร้านโรตีลีลา</title>
    <link rel="stylesheet" href="../style/employee.css?v=239">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include('../layout/navbar.php'); ?>
<?php include('../layout/sidebar.php'); ?>

<div class="main-content">
    <h2>จัดการสิทธิ์ผู้ใช้งาน</h2>

    <table class="employee-table">
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>ชื่อ - นามสกุล</th>
                <th>Username</th>
                <th>สิทธิ์ปัจจุบัน</th>
                <th>แก้ไขสิทธิ์</th>
                <th>บันทึก</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT user_ID, username, full_name, role FROM users";
        $result = mysqli_query($conn, $sql);
        $index = 1;

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<form action='../employee/update.role.php' method='POST'>";

    echo "<td>{$index}</td>";
    echo "<td>{$row['full_name']}</td>";
    echo "<td>{$row['username']}</td>";
    echo "<td>{$row['role']}</td>";

    echo "<td>";
    echo "<input type='hidden' name='user_ID' value='{$row['user_ID']}'>";
    echo "<select name='role' required>";
    echo "<option value='admin'" . ($row['role'] === 'admin' ? ' selected' : '') . ">Admin</option>";
    echo "<option value='owner'" . ($row['role'] === 'owner' ? ' selected' : '') . ">Owner</option>";
    echo "<option value='manager'" . ($row['role'] === 'manager' ? ' selected' : '') . ">Manager</option>";
    echo "<option value='accountant'" . ($row['role'] === 'accountant' ? ' selected' : '') . ">Accountant</option>";
    echo "<option value='sales'" . ($row['role'] === 'sales' ? ' selected' : '') . ">Sales</option>";
    echo "</select>";
    echo "</td>";

    echo "<td><button type='submit' class='btn btn-save'>บันทึก</button></td>";
    
    echo "</form>";
    echo "</tr>";
    $index++;
}
        ?>
        </tbody>
    </table>
</div>

<script src="../js/employee.js?v=900"></script>

</body>
</html>
