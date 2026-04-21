// employee.js (ฉบับแก้ไขสมบูรณ์)

// --- ส่วนจัดการ Pagination และการแสดงผลข้อมูล ---
let currentPage = 1;
let itemsPerPage = 10;
let searchQuery = '';
let debounceTimer;

async function fetchAndDisplayEmployees(page = 1, limit = 10, search = '') {
    currentPage = page;
    itemsPerPage = limit;
    searchQuery = search;

    const tableBody = document.getElementById('employeeTableBody');
    tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px;">กำลังโหลดข้อมูล...</td></tr>';

    try {
        const response = await fetch(`../employee/get_employee.php?page=${page}&limit=${limit}&search=${encodeURIComponent(search)}`);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const result = await response.json();
        
        renderTable(result.data);
        renderPagination(result.pagination);

    } catch (error) {
        console.error('Failed to fetch employees:', error);
        tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color: red; padding: 20px;">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
    }
}

function renderTable(employees) {
    const tableBody = document.getElementById('employeeTableBody');
    tableBody.innerHTML = '';

    if (employees.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px;">ไม่พบข้อมูลพนักงาน</td></tr>';
        return;
    }

    const startIndex = (currentPage - 1) * itemsPerPage;

    employees.forEach((emp, index) => {
      // --- ส่วนที่แก้ไขให้ถูกต้อง ---
let actionButtonsCell;

// ถ้า emp เป็น owner → แสดงแค่ปุ่มแก้ไข
if (emp.role === 'owner') {
    actionButtonsCell = `
        <td>
            <button class='btn-icon' onclick="openEditPopup(${emp.user_ID}, true)">
                <i class='fas fa-edit'></i>
            </button>
        </td>`;
} 
// ถ้า current user (LOGGED_IN_USER_ROLE) เป็น admin หรือ manager และ emp เป็น owner → สามารถจัดการได้
else if ((LOGGED_IN_USER_ROLE === 'admin' || LOGGED_IN_USER_ROLE === 'manager') && emp.role === 'owner') {
    actionButtonsCell = `
        <td>
            <button class='btn-icon' onclick="openEditPopup(${emp.user_ID}, true)">
                <i class='fas fa-edit'></i>
            </button>
        </td>`;
} 
// กรณีอื่น → แสดงทั้งปุ่มแก้ไข + ลบ
else {
    actionButtonsCell = `
        <td>
            <button class='btn-icon' onclick="openEditPopup(${emp.user_ID}, false)">
                <i class='fas fa-edit'></i>
            </button>
            <button class='btn-icon delete' onclick="openDeletePopup(${emp.user_ID})">
                <i class='fas fa-trash'></i>
            </button>
        </td>`;
}
      
      const row = `
          <tr>
              <td>${startIndex + index + 1}</td>
              <td>${emp.username || '-'}</td>
              <td>${emp.full_name || '-'}</td>
              <td>${emp.role || '-'}</td>
              <td>${emp.phone || '-'}</td>
              <td>${emp.email || '-'}</td>
              ${actionButtonsCell}
          </tr>
      `;
      // --- จบส่วนแก้ไข ---
      
      tableBody.insertAdjacentHTML('beforeend', row);
    });
}

function renderPagination(pagination) {
    const { total_pages, current_page } = pagination;
    const paginationControls = document.getElementById('pagination-controls');
    paginationControls.innerHTML = ''; // เคลียร์ปุ่มเก่าทิ้ง

    // ถ้ามีหน้าเดียวหรือไม่มีเลย ไม่ต้องแสดง pagination
    if (total_pages <= 1) return;

    // --- ฟังก์ชันย่อยสำหรับสร้างปุ่ม (เหมือนใน material.js) ---
    const createPaginationButton = (content, pageOnClick) => {
        const button = document.createElement('button');
        button.innerHTML = content;
        
        // เพิ่ม class 'page-number' สำหรับปุ่มที่เป็นตัวเลข
        if (typeof content === 'number') {
            button.classList.add('page-number');
        }

        button.addEventListener('click', () => {
            fetchAndDisplayEmployees(pageOnClick, itemsPerPage, searchQuery);
        });
        
        return button;
    };

    // 1. สร้างปุ่ม "ก่อนหน้า" (แสดงเมื่อไม่ใช่หน้าแรก)
    if (current_page > 1) {
        const prevButton = createPaginationButton('&laquo; ก่อนหน้า', current_page - 1);
        paginationControls.appendChild(prevButton);
    }

    // 2. สร้างปุ่มตัวเลข
    for (let i = 1; i <= total_pages; i++) {
        const pageButton = createPaginationButton(i, i);
        if (i === current_page) {
            pageButton.classList.add('active'); // ทำให้ปุ่มของหน้าปัจจุบัน active
        }
        paginationControls.appendChild(pageButton);
    }

    // 3. สร้างปุ่ม "ถัดไป" (แสดงเมื่อไม่ใช่หน้าสุดท้าย)
    if (current_page < total_pages) {
        const nextButton = createPaginationButton('ถัดไป &raquo;', current_page + 1);
        paginationControls.appendChild(nextButton);
    }
}

// --- ส่วนจัดการ Event Listeners และฟังก์ชันเดิม ---

document.addEventListener('DOMContentLoaded', () => {
    // โหลดข้อมูลครั้งแรก
    fetchAndDisplayEmployees(currentPage, itemsPerPage, searchQuery);

    // Event listener สำหรับตัวเลือกจำนวนรายการ
    const itemsPerPageSelector = document.getElementById('itemsPerPage');
    itemsPerPageSelector.addEventListener('change', (event) => {
        const newLimit = parseInt(event.target.value, 10);
        fetchAndDisplayEmployees(1, newLimit, searchQuery);
    });

    // Event listener สำหรับการค้นหา
const searchInput = document.getElementById('searchInput');

searchInput.addEventListener('input', () => {
    // 1. เคลียร์ timer เก่าทุกครั้งที่มีการพิมพ์
    clearTimeout(debounceTimer);

    // 2. ตั้ง timer ใหม่
    debounceTimer = setTimeout(() => {
        // 3. ฟังก์ชันนี้จะทำงานหลังจากผู้ใช้หยุดพิมพ์ไป 400 มิลลิวินาที
        const newSearchQuery = searchInput.value.trim();
        fetchAndDisplayEmployees(1, itemsPerPage, newSearchQuery);
    }, 500); // สามารถปรับเวลาได้ตามความเหมาะสม (300-500ms กำลังดี)
});

    // เรียกฟังก์ชัน style ของ select ตอนโหลดหน้า
    updateRoleStyle(); 
    document.getElementById('role').addEventListener('change', updateRoleStyle);
});

// ฟังก์ชันอื่นๆ ที่มีอยู่แล้ว (นำมาวางต่อจากนี้)
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const content = document.querySelector('.main-content');
  sidebar.classList.toggle('active');
  content.classList.toggle('shifted');
}

function togglePassword() {
  const input = document.getElementById("password");
  const icon = document.querySelector("#togglePasswordBtn i");
  const isHidden = input.type === "password";
  input.type = isHidden ? "text" : "password";
  icon.classList.toggle("fa-eye", isHidden);
  icon.classList.toggle("fa-eye-slash", !isHidden);
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

document.getElementById("resetButton").addEventListener("click", () => {
  document.getElementById("addEmployeeForm").reset();
  document.getElementById("generatedUsername").textContent = "ชื่อผู้ใช้";
  document.getElementById("generatedUsername").classList.remove("show");
  document.getElementById("full_name-error").textContent = "";
  document.getElementById("phone-error").textContent = "";
  document.getElementById("email-error").textContent = "";
  document.getElementById("role-error").textContent = "";
  updateRoleStyle();
});

document.getElementById('addEmployeeForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const isRoleValid = validateRole();
  const isFullNameValid = document.getElementById("full_name-error").textContent === "";
  const isPhoneValid = document.getElementById("phone-error").textContent === "";
  const isEmailValid = document.getElementById("email-error").textContent === "";

  if (!isRoleValid || !isFullNameValid || !isPhoneValid || !isEmailValid) {
    showToast('❌ กรุณากรอกข้อมูลให้ถูกต้องครบถ้วน', '#e74c3c');
    return;
  }
  
  if (!document.getElementById('username').value) {
    showToast("กรุณากรอกชื่อ-นามสกุลให้ถูกต้องก่อนบันทึก", '#e74c3c');
    return;
  }

  const formData = new FormData(this);
  fetch('../employee/add_employee.php', { method: 'POST', body: formData })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      closePopup('addPopup');
      showToast('✅ เพิ่มพนักงานเรียบร้อยแล้ว');
      fetchAndDisplayEmployees(currentPage, itemsPerPage, searchQuery); // โหลดข้อมูลใหม่
    } else {
      showToast(`❌ ไม่สามารถเพิ่มข้อมูลได้: ${response.error || 'ไม่ทราบสาเหตุ'}`, '#e74c3c');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', '#e74c3c');
  });
});

function checkFullName() {
    const fullNameInput = document.getElementById('full_name');
    const fullNameValue = fullNameInput.value.trim();
    const errorDiv = document.getElementById("full_name-error");
    const usernameDisplay = document.getElementById('generatedUsername');
    const usernameInput = document.getElementById('username');

    clearTimeout(debounceTimer);
    errorDiv.textContent = "";

    let baseUsername = '';
    const parts = fullNameValue.toLowerCase().split(' ').filter(p => p.length > 0);

    if (parts.length > 0) {
        const firstName = parts[0];
        if (parts.length > 1) {
            const lastName = parts[parts.length - 1];
            const lastNameInitial = lastName.slice(0, 2);
            baseUsername = `${firstName}.${lastNameInitial}`;
        } else {
            baseUsername = firstName;
        }
    }

    usernameDisplay.textContent = baseUsername || 'ชื่อผู้ใช้';
    usernameInput.value = baseUsername;
    usernameDisplay.classList.toggle('show', !!baseUsername);

    if (!fullNameValue) {
        errorDiv.textContent = '❌ กรุณากรอก ชื่อ-นามสกุล';
        return;
    } else if (fullNameValue.length <= 3) {
        errorDiv.textContent = '❌ กรุณากรอกให้ครบถ้วน';
        return;
    }

    debounceTimer = setTimeout(() => {
        fetch(`../employee/check_unique.php?type=full_name&value=${encodeURIComponent(fullNameValue)}`)
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    errorDiv.textContent = "❌ ชื่อ-นามสกุลนี้มีในระบบแล้ว";
                } else {
                    if (baseUsername) {
                        fetch(`../employee/check_unique.php?type=generate_username&value=${encodeURIComponent(baseUsername)}`)
                            .then(res => res.json())
                            .then(usernameData => {
                                usernameDisplay.textContent = usernameData.username;
                                usernameInput.value = usernameData.username;
                            });
                    }
                }
            });
    }, 500);
}

function checkPhone() {
  const phone = document.getElementById("phone").value.trim();
  const errorDiv = document.getElementById("phone-error");
  errorDiv.textContent = "";
  const phoneRegex = /^0(6[0-9]|8[1-9]|9[0-6|8-9])[0-9]{7}$/;
  if (!phone) {
    errorDiv.textContent = '❌ กรุณากรอกเบอร์โทร'; return;
  } else if (!phoneRegex.test(phone)) {
    errorDiv.textContent = "❌ เบอร์โทรไม่ถูกต้อง"; return;
  }
  fetch(`../employee/check_unique.php?type=phone&value=${encodeURIComponent(phone)}`).then(res => res.json()).then(data => { if (data.exists) errorDiv.textContent = "❌ เบอร์นี้มีในระบบแล้ว"; });
}

function checkEmail() {
  const email = document.getElementById("email").value.trim();
  const errorDiv = document.getElementById("email-error");
  errorDiv.textContent = "";
  const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
  if (!email) {
    errorDiv.textContent = '❌ กรุณากรอกอีเมล'; return;
  } else if (!emailRegex.test(email)) {
    errorDiv.textContent = "❌ กรุณากรอกอีเมลให้ถูกต้อง (@gmail.com เท่านั้น)"; return;
  }
  fetch(`../employee/check_unique.php?type=email&value=${encodeURIComponent(email)}`).then(res => res.json()).then(data => { if (data.exists) errorDiv.textContent = "❌ อีเมลนี้มีในระบบแล้ว"; });
}

function generatePassword() {
  const fullName = document.getElementById('full_name').value.trim();
  const phone = document.getElementById('phone').value.trim();
  if (fullName && phone.length >= 4) {
    const initials = fullName.split(' ')[0].toLowerCase();
    const last4Digits = phone.slice(-4);
    document.getElementById('password').value = `${initials}${last4Digits}`;
  }
}

function validateRole() {
    const role = document.getElementById('role').value;
    const roleError = document.getElementById('role-error');
    if (!role) {
        roleError.textContent = '❌ กรุณาเลือกประเภทผู้ใช้'; return false;
    } else {
        roleError.textContent = ''; return true;
    }
}

function updateRoleStyle() {
  const select = document.getElementById('role');
  select.classList.toggle('placeholder', select.value === "");
}

document.getElementById('editEmployeeForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('../employee/edit_employee.php', { method: 'POST', body: formData })
  .then(res => res.text())
  .then(response => {
    closePopup('editPopup');
    showToast('✅ แก้ไขข้อมูลเรียบร้อยแล้ว');
    fetchAndDisplayEmployees(currentPage, itemsPerPage, searchQuery); // โหลดข้อมูลใหม่
  })
  .catch(error => { console.error('Error:', error); showToast('เกิดข้อผิดพลาด', '#e74c3c'); });
});

function openEditPopup(userID) {
  fetch(`../employee/edit_employee.php?user_ID=${userID}`)
    .then(response => response.json())
    .then(data => {
      const form = document.getElementById('editEmployeeForm');
      const saveButton = form.querySelector('.btn-save');
      const editRoleSelect = document.getElementById('editRole');

      // --- ส่วนที่ปรับปรุงใหม่ ---

      // 1. ล้างและเตรียม option 'owner' (เหมือนเดิม)
      const ownerOptionInSelect = editRoleSelect.querySelector('option[value="owner"]');
      if (ownerOptionInSelect) {
        ownerOptionInSelect.remove();
      }
      if (data.role === 'owner') {
        if (!editRoleSelect.querySelector('option[value="owner"]')) {
          const ownerOption = document.createElement('option');
          ownerOption.value = 'owner';
          ownerOption.textContent = 'owner';
          editRoleSelect.appendChild(ownerOption);
        }
      }
      
      // 2. เติมข้อมูลลงในฟอร์ม (เหมือนเดิม)
      document.getElementById('editUserID').value = data.user_ID;
      document.getElementById('editUsername').value = data.username;
      document.getElementById('editFullName').value = data.full_name;
      document.getElementById('editPhone').value = data.phone;
      document.getElementById('editEmail').value = data.email;
      document.getElementById('editRole').value = data.role;

      // 3. รีเซ็ตสถานะ disable ของทุกช่องก่อน
      form.querySelectorAll('input, select').forEach(el => el.disabled = false);
      saveButton.disabled = false;
      document.getElementById('editUsername').readOnly = true; // คงค่า readOnly ไว้
      document.getElementById('editEmail').readOnly = true;    // คงค่า readOnly ไว้

      // 4. ตรวจสอบเงื่อนไขพิเศษ: ถ้า Admin หรือ Manager กำลังแก้ไข Owner
      if ((LOGGED_IN_USER_ROLE === 'admin' || LOGGED_IN_USER_ROLE === 'manager') && data.role === 'owner') {
        // อนุญาตให้แก้ไขแค่ "ชื่อ" และ "เบอร์โทร"
        document.getElementById('editFullName').disabled = false;
        document.getElementById('editPhone').disabled = false;

        // ปิดการใช้งานช่องที่เหลือ
        document.getElementById('editUsername').disabled = true;
        document.getElementById('editEmail').disabled = true;
        document.getElementById('editRole').disabled = true; // ปิดการแก้ไข Role
        
        // ปุ่มบันทึกยังคงใช้งานได้
        saveButton.disabled = false;
        
      }
      // --- จบส่วนปรับปรุง ---

      openPopup('editPopup');
    });
}

function checkEditUsername() {
    const value = document.getElementById('editUsername').value.trim();
    const errorDiv = document.getElementById("edit-username-error");
    errorDiv.textContent = "";
    if (!value) { errorDiv.textContent = '❌ กรุณากรอก username'; return; }
    if (value.length < 4) { errorDiv.textContent = "❌ ต้องมีอย่างน้อย 4 ตัวอักษร"; return; }
    const excludeID = document.getElementById('editUserID').value;
    fetch(`../employee/check_unique.php?type=username&value=${encodeURIComponent(value)}&exclude_id=${excludeID}`).then(res => res.json()).then(data => { if (data.exists) errorDiv.textContent = "❌ Username นี้ถูกใช้แล้ว"; });
}
function checkEditFullName() {
    const value = document.getElementById('editFullName').value.trim();
    const errorDiv = document.getElementById("edit-full_name-error");
    errorDiv.textContent = "";
    if (!value) { errorDiv.textContent = '❌ กรุณากรอก ชื่อ-นามสกุล'; return; }
    if (value.length <= 3) { errorDiv.textContent = '❌ กรุณากรอกให้ครบถ้วน'; return; }
    const excludeID = document.getElementById('editUserID').value;
    fetch(`../employee/check_unique.php?type=full_name&value=${encodeURIComponent(value)}&exclude_id=${excludeID}`).then(res => res.json()).then(data => { if (data.exists) errorDiv.textContent = "❌ ชื่อ-นามสกุลนี้มีในระบบแล้ว"; });
}
function checkEditPhone() {
    const phone = document.getElementById("editPhone").value.trim();
    const errorDiv = document.getElementById("edit-phone-error");
    errorDiv.textContent = "";
    const phoneRegex = /^[0-9]{10}$/;
    if (!phone) { errorDiv.textContent = '❌ กรุณากรอกเบอร์โทร'; return; }
    if (!phoneRegex.test(phone)) { errorDiv.textContent = "❌ เบอร์โทรต้องเป็นตัวเลข 10 หลัก"; return; }
    const excludeID = document.getElementById('editUserID').value;
    fetch(`../employee/check_unique.php?type=phone&value=${encodeURIComponent(phone)}&exclude_id=${excludeID}`).then(res => res.json()).then(data => { if (data.exists) errorDiv.textContent = "❌ เบอร์นี้มีในระบบแล้ว"; });
}
function checkEditEmail() {
    const email = document.getElementById("editEmail").value.trim();
    const errorDiv = document.getElementById("edit-email-error");
    errorDiv.textContent = "";
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email) { errorDiv.textContent = '❌ กรุณากรอกอีเมล'; return; }
    if (!emailRegex.test(email)) { errorDiv.textContent = "❌ รูปแบบอีเมลไม่ถูกต้อง"; return; }
    const excludeID = document.getElementById('editUserID').value;
    fetch(`../employee/check_unique.php?type=email&value=${encodeURIComponent(email)}&exclude_id=${excludeID}`).then(res => res.json()).then(data => { if (data.exists) errorDiv.textContent = "❌ อีเมลนี้มีในระบบแล้ว"; });
}
function validateEditRole() {
    const role = document.getElementById('editRole').value;
    const roleError = document.getElementById('edit-role-error');
    if (!role) { roleError.textContent = '❌ กรุณาเลือกประเภทผู้ใช้'; return false; }
    else { roleError.textContent = ''; return true; }
}

document.getElementById('deleteEmployeeForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('../employee/delete_employee.php', { method: 'POST', body: formData })
  .then(res => res.text())
  .then(response => {
    if (response.trim() === 'success') {
      closePopup('deletePopup');
      showToast('✅ ลบพนักงานเรียบร้อยแล้ว');
      fetchAndDisplayEmployees(1, itemsPerPage, ''); // กลับไปหน้า 1 หลังลบ
    } else {
      showToast('❌ เกิดข้อผิดพลาด: ' + response, '#e74c3c');
    }
  })
  .catch(error => { console.error('Error:', error); showToast('เกิดข้อผิดพลาด', '#e74c3c'); });
});

function openPopup(id) {
  const popup = document.getElementById(id);
  if (popup) popup.style.display = 'flex';
}
function openDeletePopup(userID) {
  document.getElementById('deleteUserID').value = userID;
  openPopup('deletePopup');
}
function closePopup(id) {
  const popup = document.getElementById(id);
  if (popup) popup.style.display = 'none';
}