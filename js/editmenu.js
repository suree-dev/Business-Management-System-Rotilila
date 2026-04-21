// ===================================
// ===== ฟังก์ชันทั่วไป & Helpers =====
// ===================================
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
        document.body.classList.toggle('sidebar-open', sidebar.classList.contains('active'));
    }
}

function openPopup(id) {
    const popup = document.getElementById(id);
    if (popup) {
        popup.style.display = 'flex';
        if (id === 'addPopup') {
            document.getElementById('addMenuForm')?.reset();
        }
    }
}

function closePopup(id) {
    const popup = document.getElementById(id);
    if (popup) popup.style.display = 'none';
}

function showToast(message, color = '#2ecc71') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.style.backgroundColor = color;
    toast.style.display = 'block';
    toast.style.animation = 'none';
    void toast.offsetWidth;
    toast.style.animation = 'fadeInOut 3s ease-in-out forwards';
}

// ===================================
// ===== ฟังก์ชันจัดการแท็บ =====
// ===================================
function showTab(tabName) {
    document.getElementById('tab-menus')?.classList.toggle('active', tabName === 'menus');
    document.getElementById('tab-categories')?.classList.toggle('active', tabName === 'categories');
    
    const menusContent = document.getElementById('tab-menus-content');
    const categoriesContent = document.getElementById('tab-categories-content');

    if (menusContent) menusContent.style.display = tabName === 'menus' ? '' : 'none';
    if (categoriesContent) categoriesContent.style.display = tabName === 'categories' ? '' : 'none';

    if (tabName === 'categories') {
        fetchCategories();
    }
}

// ===================================
// ===== ตัวแปรและข้อมูลส่วนกลาง =====
// ===================================
const rowsPerPage = 10;
let currentPage = 1;
let allMenus = [];

// ===================================
// ===== DOMContentLoaded (จุดเริ่มต้น) =====
// ===================================
document.addEventListener('DOMContentLoaded', function () {
    fetchAndRenderMenus();
    setupFormListeners();
    setupFilterListeners();
});

// ===================================
// ===== ส่วนจัดการเมนู =====
// ===================================
function fetchAndRenderMenus() {
    fetch('../menu/fetch_menu.php')
        .then(res => res.json())
        .then(data => {
            allMenus = data;
            renderMenuTable();
            setupPagination();
        })
        .catch(error => {
            console.error('Error fetching menus:', error);
            const tableBody = document.querySelector(".im-table tbody");
            if(tableBody) tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:red; padding:32px;">ไม่สามารถโหลดข้อมูลเมนูได้</td></tr>`;
        });
}

function renderMenuTable() {
    const tableBody = document.querySelector(".im-table tbody");
    if (!tableBody) return;
    
    const filteredMenus = filterMenus();
    const start = (currentPage - 1) * rowsPerPage;
    const pageMenus = filteredMenus.slice(start, start + rowsPerPage);
    
    tableBody.innerHTML = '';

    if (filteredMenus.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#888; padding:32px;">ไม่พบเมนูตามเงื่อนไข</td></tr>`;
        return;
    }

    pageMenus.forEach((menu, index) => {
        const row = document.createElement('tr');
        const visibilityClass = menu.visible == 1 ? 'mm-status-visible' : 'mm-status-hidden';
        const visibilityText = menu.visible == 1 ? 'แสดง' : 'ซ่อน';

        // --- เปลี่ยนไอคอนตรงนี้ ---
        const iconClass = menu.visible == 1 ? 'fa-toggle-on' : 'fa-toggle-off';

        row.innerHTML = `
            <td>${start + index + 1}</td>
            <td>${menu.menu_name || '-'}</td>
            <td>${parseFloat(menu.menu_price).toFixed(2)}</td>
            <td>${(menu.cate_names || []).join(', ') || '-'}</td>
            <td><span class="mm-status ${visibilityClass}">${visibilityText}</span></td>
            <td>
                <button class='im-action-btn' onclick="toggleVisibility(this, ${menu.menu_ID}, ${menu.visible})">
                    <i class='fas ${iconClass}'></i>
                </button>
                <button class='im-action-btn im-edit' onclick="openEditPopup(${menu.menu_ID})">
                    <i class='fas fa-edit'></i>
                </button>
                <button class='im-action-btn im-delete' onclick="openDeletePopup(${menu.menu_ID})">
                    <i class='fas fa-trash'></i>
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

function setupFilterListeners() {
    document.getElementById('categoryFilter')?.addEventListener('change', handleFilterChange);
    document.getElementById('menu-search-input')?.addEventListener('input', handleFilterChange);
}

function handleFilterChange() {
    currentPage = 1;
    renderMenuTable();
    setupPagination();
}

function filterMenus() {
    const category = document.getElementById('categoryFilter')?.value || 'all';
    const search = document.getElementById('menu-search-input')?.value.trim().toLowerCase() || '';
    
    if (!allMenus) return [];

    return allMenus.filter(menu => {
        const categoryMatch = (category === 'all') || (menu.cate_ids && menu.cate_ids.includes(parseInt(category.replace('cate', ''))));
        const nameMatch = menu.menu_name.toLowerCase().includes(search);
        return categoryMatch && nameMatch;
    });
}

function setupPagination() {
    const paginationDiv = document.getElementById("pagination");
    if (!paginationDiv) return;
    
    const filtered = filterMenus();
    const totalPages = Math.ceil(filtered.length / rowsPerPage);
    
    paginationDiv.innerHTML = '';
    if (totalPages <= 1) return;

    if (currentPage > 1) {
        paginationDiv.appendChild(createPaginationButton('« ก่อนหน้า', () => { currentPage--; renderMenuTable(); setupPagination(); }));
    }
    for (let i = 1; i <= totalPages; i++) {
        const btn = createPaginationButton(i, () => { currentPage = i; renderMenuTable(); setupPagination(); });
        if (i === currentPage) btn.classList.add('active');
        paginationDiv.appendChild(btn);
    }
    if (currentPage < totalPages) {
        paginationDiv.appendChild(createPaginationButton('ถัดไป »', () => { currentPage++; renderMenuTable(); setupPagination(); }));
    }
}

function createPaginationButton(text, onClick) {
    const button = document.createElement('button');
    button.className = 'pagination-btn';
    button.innerText = text;
    button.addEventListener('click', onClick);
    return button;
}

// ในไฟล์ editmenu.js

// ค้นหาฟังก์ชัน setupFormListeners() และแก้ไข event listener ของ addMenuForm

function setupFormListeners() {
    // START: แก้ไขส่วนนี้
    document.getElementById('addMenuForm')?.addEventListener('submit', function (e) {
        e.preventDefault(); // หยุดการส่งฟอร์มแบบปกติ

        // --- 1. ดึงค่าจากฟอร์มมาตรวจสอบ ---
        const menuName = document.getElementById('menu_name').value.trim();
        const menuPrice = document.getElementById('menu_price').value;
        const selectedCategories = document.querySelectorAll('#addMenuForm input[name="food_category[]"]:checked');

        // --- 2. ทำการตรวจสอบ (Validation) ---
        if (menuName === '') {
            showToast('❌ กรุณากรอกชื่อเมนู', '#e74c3c');
            return; // หยุดการทำงานทันที
        }

        if (menuPrice === '') {
            showToast('❌ กรุณากรอกราคา', '#e74c3c');
            return; // หยุดการทำงานทันที
        }

        if (selectedCategories.length === 0) {
            showToast('❌ กรุณาเลือกประเภทอย่างน้อย 1 รายการ', '#e74c3c');
            return; // หยุดการทำงานทันที
        }

        // --- 3. ถ้าผ่านการตรวจสอบทั้งหมด ให้ส่งข้อมูล ---
        submitFormData(this, '../menu/add_menu.php', 'เพิ่มเมนูเรียบร้อยแล้ว');
    });
    // END: แก้ไขส่วนนี้

    document.getElementById('editMenuForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        // การแก้ไขก็ควรมีการตรวจสอบเช่นกัน แต่จะขอยกตัวอย่างเฉพาะการเพิ่มตามคำขอ
        submitFormData(this, '../menu/edit_menu.php', 'แก้ไขเมนูเรียบร้อยแล้ว');
    });
    document.getElementById('deleteMenuForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        submitFormData(this, '../menu/delete_menu.php', 'ลบเมนูเรียบร้อยแล้ว');
    });
    document.getElementById('categoryForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        submitFormData(this, '../menu/save_menu_cate.php', 'บันทึกประเภทเรียบร้อยแล้ว');
    });
}

// ฟังก์ชันนี้ควรเป็นเวอร์ชั่นที่รองรับ JSON response
function submitFormData(formElement, url, successMessage) {
    const formData = new FormData(formElement);
    fetch(url, { method: 'POST', body: formData })
        .then(res => res.text()) // เปลี่ยนกลับเป็น text เพื่อความง่ายก่อน
        .then(response => {
            if (response.trim() === 'success') {
                const popupId = formElement.closest('.popup-overlay')?.id;
                if(popupId) closePopup(popupId);
                
                showToast(`✅ ${successMessage}`);
                if (formElement.id.includes('Menu')) {
                   fetchAndRenderMenus();
                } else if (formElement.id.includes('Category')) {
                   fetchCategories();
                }
            } else {
                showToast(`❌ เกิดข้อผิดพลาด: ${response}`, '#e74c3c');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('❌ เกิดข้อผิดพลาดร้ายแรง', '#e74c3c');
        });
}

function openEditPopup(menuID) {
    fetch(`../menu/edit_menu.php?menu_ID=${menuID}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) { showToast(`❌ ${data.error}`, '#e74c3c'); return; }
            document.getElementById('editMenuID').value = data.menu_ID;
            document.getElementById('editMenuName').value = data.menu_name;
            document.getElementById('editMenuPrice').value = data.menu_price;
            const currentImg = document.getElementById('currentMenuImage');
            const currentFileName = document.getElementById('currentImageFileName');
            if (data.menu_image && data.menu_image.trim() !== '') {
                // --- บรรทัดที่แก้ไขแล้ว ---
                // ใช้ '../' เพื่อถอยออกจากโฟลเดอร์ 'menu' 1 ขั้น แล้วชี้ไปยัง path ที่ได้จาก database
                currentImg.src = '../' + data.menu_image;
                currentImg.style.display = 'block';
                currentFileName.textContent = 'ไฟล์เดิม: ' + data.menu_image.split('/').pop();
            } else {
                currentImg.style.display = 'none';
                currentFileName.textContent = 'ไม่มีรูปภาพ';
            }
            const selectedCateIDs = data.cate_ids || [];
            document.querySelectorAll('#editCategoryCheckboxes input[type="checkbox"]').forEach(cb => {
                cb.checked = selectedCateIDs.includes(parseInt(cb.value));
            });
            openPopup('editPopup');
        });
}

function openDeletePopup(menuID) {
    document.getElementById('deleteMenuID').value = menuID;
    openPopup('deletePopup');
}

// ===============================================
// ===== ฟังก์ชัน toggleVisibility (ฉบับแก้ไข) =====
// ===============================================
function toggleVisibility(buttonElement, menuID, currentVisibility) {
    const newVisible = currentVisibility == 1 ? 0 : 1;
    const formData = new FormData();
    formData.append('menu_ID', menuID);
    formData.append('visible', newVisible);

    fetch('../menu/toggle_visibility.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(response => {
            if (response.trim() === 'success') {
                showToast(newVisible == 1 ? '✅ แสดงเมนูแล้ว' : '✅ ซ่อนเมนูแล้ว');

                const menuIndex = allMenus.findIndex(menu => menu.menu_ID == menuID);
                if (menuIndex > -1) {
                    allMenus[menuIndex].visible = newVisible;
                }

                const row = buttonElement.closest('tr');
                if (!row) return;

                const statusSpan = row.querySelector('.mm-status');
                const icon = buttonElement.querySelector('i.fas');

                if (statusSpan && icon) {
                    // --- เปลี่ยนการอัปเดตไอคอนตรงนี้ ---
                    if (newVisible == 1) {
                        statusSpan.textContent = 'แสดง';
                        statusSpan.classList.remove('mm-status-hidden');
                        statusSpan.classList.add('mm-status-visible');
                        icon.classList.remove('fa-toggle-off');
                        icon.classList.add('fa-toggle-on');
                    } else {
                        statusSpan.textContent = 'ซ่อน';
                        statusSpan.classList.remove('mm-status-visible');
                        statusSpan.classList.add('mm-status-hidden');
                        icon.classList.remove('fa-toggle-on');
                        icon.classList.add('fa-toggle-off');
                    }
                }

                buttonElement.setAttribute('onclick', `toggleVisibility(this, ${menuID}, ${newVisible})`);

            } else {
                showToast(`❌ ไม่สามารถอัปเดตสถานะได้: ${response}`, '#e74c3c');
            }
        })
        .catch(error => {
            console.error('Visibility toggle error:', error);
            showToast('❌ เกิดข้อผิดพลาดในการเชื่อมต่อ', '#e74c3c');
        });
}

// ===================================
// ===== ส่วนจัดการประเภท =====
// ===================================
function fetchCategories() {
    fetch('../menu/fetch_menu_cate.php') 
        .then(res => res.json())
        .then(categories => renderCategoriesGrid(categories))
        .catch(error => console.error("Failed to fetch menu categories:", error));
}

function renderCategoriesGrid(categories) {
    const grid = document.getElementById('categories-grid');
    if (!grid) return;
    grid.innerHTML = '';
    
    if (categories.length === 0) {
        grid.innerHTML = '<div style="color:#888; text-align:center; padding:32px;">ไม่มีประเภทเมนู</div>';
        return;
    }

    categories.forEach(cat => {
        const div = document.createElement('div');
        div.className = 'im-category-card';
        const catData = JSON.stringify(cat).replace(/"/g, '&quot;');
        
        // ลบส่วนแสดงผลของ Icon ออกไป
        div.innerHTML = `
            <div class="im-category-info">
                <div class="im-category-name">${cat.cate_name}</div>
                <div class="im-category-count">${cat.menu_count || 0} รายการ</div>
            </div>
            <div class="im-category-actions">
                <button class="im-action-btn im-edit" title="แก้ไข" onclick='openCategoryModal(true, ${catData})'><i class="fas fa-edit"></i></button>
                <button class="im-action-btn im-delete" title="ลบ" onclick='deleteCategory(${cat.cate_id})'><i class="fas fa-trash"></i></button>
            </div>
        `;
        grid.appendChild(div);
    });
}

function openCategoryModal(isEdit = false, cate = null) {
    document.getElementById('categoryForm').reset(); // เคลียร์ฟอร์มก่อน
    document.getElementById('categoryModalTitle').textContent = isEdit ? 'แก้ไขประเภทเมนู' : 'เพิ่มประเภทเมนู';
    document.getElementById('cateId').value = cate ? cate.cate_id : '';
    document.getElementById('cateName').value = cate ? cate.cate_name : '';
   
    openPopup('categoryModal');
}

function closeCategoryModal() {
    closePopup('categoryModal');
}

function deleteCategory(cate_id) {
    if (!confirm('คุณต้องการลบประเภทนี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้')) return;
    
    const formData = new FormData();
    formData.append('cate_id', cate_id);

    fetch('../menu/delete_menu_cate.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(response => {
            if (response.trim() === 'success') {
                fetchCategories();
                showToast('✅ ลบประเภทเรียบร้อยแล้ว');
            } else {
                showToast('❌ เกิดข้อผิดพลาด: ' + response, '#e74c3c');
            }
        });
}