function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const content = document.querySelector('.main-content');
  sidebar.classList.toggle('active');
  content.classList.toggle('shifted');
}

document.addEventListener('DOMContentLoaded', function () {
  const categoryLinks = document.querySelectorAll('#sidebar-category a');
  const sidebar = document.getElementById('sidebar');
  const content = document.querySelector('.main-content');

  categoryLinks.forEach(link => {
    link.addEventListener('click', () => {
      sidebar.classList.remove('active');
      content.classList.remove('shifted');
    });
  });

  // ปิด popup เมื่อคลิกพื้นที่ว่าง addPopup
  document.getElementById('addPopup').addEventListener('click', function (e) {
    if (e.target === this) {
      closePopup('addPopup');
      resetAddCategoryForm();
    }
  });
});

function openPopup(id) {
  document.getElementById(id).style.display = 'flex';
  if (id === 'addPopup') resetAddCategoryForm();
}

function closePopup(id) {
  document.getElementById(id).style.display = 'none';
  if (id === 'addPopup') resetAddCategoryForm();
}

function showToast(message, color = '#2ecc71') {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.style.backgroundColor = color;
  toast.style.display = 'block';

  toast.style.animation = 'none';
  void toast.offsetWidth;
  toast.style.animation = 'fadeInOut 3s ease-in-out forwards';

  setTimeout(() => {
    toast.style.display = 'none';
  }, 3000);
}


/* เพิ่มประเภทอาหาร */
document.getElementById('addCategoryForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch('../menu/add_cate.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(response => {
    if (response.trim() === 'success') {
      closePopup('addPopup');
      showToast('✅ เพิ่มประเภทอาหารเรียบร้อยแล้ว');
      setTimeout(() => location.reload(), 500);
    } else {
      showToast('❌ เกิดข้อผิดพลาด: ' + response, '#e74c3c');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('เกิดข้อผิดพลาด');
  });
});

/* ลบเมนู */
document.getElementById('deleteCategoryForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch('../menu/delete_cate.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(response => {
    if (response.trim() === 'success') {
      closePopup('deletePopup');
      showToast('✅ ลบประเภทอาหารเรียบร้อยแล้ว');
      setTimeout(() => location.reload(), 500);
    } else {
      showToast('❌ เกิดข้อผิดพลาด: ' + response, '#e74c3c');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('เกิดข้อผิดพลาด');
  });
});

function openDeletePopup(categoryID) {
  document.getElementById('deleteCategoryID').value = categoryID;
  openPopup('deletePopup');
}

function resetAddCategoryForm() {
  const form = document.getElementById('addCategoryForm');
  if (form) form.reset();
}
