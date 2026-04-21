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
  // document.addEventListener('click', function (e) {
  //   if (sidebar && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
  //     sidebar.classList.remove('active');
  //     if (content) content.classList.remove('shifted');
  //   }
  // });

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