<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = strtolower($_SESSION['role'] ?? '');
?>

<nav class="navbar">
  <!-- ปุ่ม ☰ เปิด sidebar -->
   <?php if ($role === 'owner' || $role === 'accountant'|| $role === 'sales'|| $role === 'manager'): ?>
  <span class="toggle-sidebar" onclick="toggleSidebar()"><i class="fas fa-bars"></i></span>
  <?php endif; ?>

  <!-- ชื่อระบบ -->
  <h1>ระบบจัดการร้านโรตีลีลา</h1>

  <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $username = $_SESSION['username'] ?? 'ไม่ทราบชื่อ';
    $role = $_SESSION['role'] ?? 'ไม่ทราบตำแหน่ง';

    // ดึงตัวอักษรแรกของชื่อ (รองรับไทย/Unicode)
    $firstChar = mb_substr($username, 0, 1, 'UTF-8');

    // แปลง role เป็นภาษาไทย ถ้าต้องการ
    $roleMap = [
        'admin' => 'แอดมิน',
        'sales' => 'พนักงานขาย',
        'manager' => 'ผู้จัดการ',
        'owner' => 'เจ้าของร้าน',
        'accountant' => 'ผู้จัดการบัญชี',
    ];
    $roleDisplay = $roleMap[$role] ?? $role;
  ?>

 <!-- โปรไฟล์ผู้ใช้ -->
<div class="admin-profile" onclick="toggleDropdown()">
  <div class="profile-char"><?= htmlspecialchars($firstChar) ?></div>
  <div class="user-info">
    <div class="username"><?= htmlspecialchars($username) ?></div>
    <div class="role"><?= htmlspecialchars($roleDisplay) ?></div>
  </div>

  <!-- dropdown menu -->
  <div id="profile-dropdown" class="dropdown-menu" style="display: none;">
    <a href="../auth/change_password.php">เปลี่ยนรหัสผ่าน</a>
    <a href="../auth/logout.php">ออกจากระบบ</a>
  </div>
</div>
</nav>

<script>
function toggleDropdown() {
  const dropdown = document.getElementById("profile-dropdown");
  const currentDisplay = window.getComputedStyle(dropdown).display;
  console.log('toggleDropdown called', dropdown.style.display);
  dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
}

// ปิด dropdown ถ้าคลิกข้างนอก
document.addEventListener('click', function(event) {
  const profile = document.querySelector('.admin-profile');
  const dropdown = document.getElementById("profile-dropdown");
  if (!profile || !dropdown) return;
  if (!profile.contains(event.target)) {
    dropdown.style.display = "none";
  }
});

<?php if ($role === 'sales'): ?>
document.querySelector('.admin-profile').addEventListener('click', function(event) {
  event.stopPropagation(); // ป้องกันไม่ให้คลิกนี้ไปปิด dropdown
  toggleDropdown();
});
<?php endif; ?>

</script>