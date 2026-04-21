const unitConversionRates = {
    // กลุ่มน้ำหนัก (Base: กรัม)
    'กรัม': {
        'กิโลกรัม': 1000,
        'ขีด': 100,
        'กรัม': 1
    },
    // กลุ่มปริมาตร (Base: มิลลิลิตร)
    'มิลลิลิตร': {
        'ลิตร': 1000,
        'ซีซี': 1,
        'ช้อนโต๊ะ': 15,
        'ช้อนชา': 5,
        'ถ้วยตวง': 240, // ค่าประมาณ
        'มิลลิลิตร': 1
    },
    // กลุ่มนับชิ้น (Base: ชิ้น)
    'ชิ้น': {
        'โหล': 12,
        'ชิ้น': 1
    },
    // กลุ่มนับฟอง (Base: ฟอง)
    'ฟอง': {
        'แผง': 30, // ค่าประมาณ
        'ฟอง': 1
    }
};

function autoCalculateConversion() {
    const unitNameInput = document.getElementById('unitName');
    const baseUnitInput = document.getElementById('baseUnit');
    const factorInput = document.getElementById('conversionFactor');
    const previewBox = document.getElementById('formula-preview');

    if (!unitNameInput || !baseUnitInput || !factorInput) return;

    const fromUnit = unitNameInput.value.trim();
    const toBaseUnit = baseUnitInput.value.trim();

    if (fromUnit && toBaseUnit) {
        // ค้นหาว่า base unit ที่กรอกมีอยู่ใน knowledge base หรือไม่
        if (unitConversionRates[toBaseUnit]) {
            const rates = unitConversionRates[toBaseUnit];
            // ค้นหาว่าหน่วยที่ต้องการแปลงมีอยู่ในกลุ่มนั้นหรือไม่
            if (rates[fromUnit]) {
                const factor = rates[fromUnit];
                factorInput.value = factor;
                previewBox.textContent = `พบสูตร: 1 ${fromUnit} = ${factor.toLocaleString('en-US')} ${toBaseUnit}`;
            } else {
                previewBox.textContent = 'ไม่พบสูตรอัตโนมัติ กรุณากรอกตัวคูณเอง';
            }
        } else {
             previewBox.textContent = 'ไม่พบสูตรอัตโนมัติ กรุณากรอกตัวคูณเอง';
        }
    }
    
    // อัปเดตการแสดงผลครั้งสุดท้าย (กรณีผู้ใช้แก้ตัวเลขเอง)
    updateFormulaPreview();
}

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
        document.body.classList.toggle('sidebar-open', sidebar.classList.contains('active'));
    }
}

function showToast(message, color = '#2ecc71') {
    const toast = document.getElementById('toast');
    if (toast) {
        toast.textContent = message;
        toast.style.backgroundColor = color;
        toast.style.display = 'block';
        toast.style.animation = 'none';
        void toast.offsetWidth;
        toast.style.animation = 'fadeInOut 3s ease-in-out forwards';

    } else {
        // Fallback ในกรณีที่หา HTML ไม่เจอ
        alert(message);
    }
}

function openPopup(id) {
    const popup = document.getElementById(id);
    if (popup) {
        popup.style.display = 'flex';
        if (id === 'addMaterialPopup') {
            resetAddMaterialForm();
        }
        // เรียกใช้ฟังก์ชันปฏิทินหลังจาก popup แสดงผลแล้ว
        if (typeof window.initializeDatepickers === 'function') {
            window.initializeDatepickers();
        }
    }
}

function closePopup(id) {
    const popup = document.getElementById(id);
    if (popup) {
        popup.style.display = 'none';
    }
}


let materialRowIndex = 1;
let categoryRowIndex = 1; // เพิ่มตัวแปรนับแถวสำหรับประเภท

// ==========================================================
// ===== ระบบเพิ่มประเภทหลายรายการ (Multi-Add Category System) =====
// ==========================================================
function addCategoryRow() {
    const container = document.getElementById('category-rows-container');
    if (!container) return;
    const firstRow = container.querySelector('.category-form-row');
    if (!firstRow) return;
    const newRow = firstRow.cloneNode(true);

    // ล้างค่าใน input ของแถวใหม่
    newRow.querySelector('input').value = '';

    // อัปเดต ID ของ input
    newRow.querySelector('[id]').id = `cate_name_${categoryRowIndex}`;

    // แสดงปุ่มลบและเพิ่ม event listener
    const removeBtn = newRow.querySelector('.btn-remove-row');
    if (removeBtn) {
        removeBtn.style.display = 'block';
        removeBtn.addEventListener('click', () => {
            newRow.remove();
        }, { once: true });
    }

    container.appendChild(newRow);
    categoryRowIndex++;
}

function resetCategoryModal() {
    const container = document.getElementById('category-rows-container');
    if (!container) return;
    
    // ลบแถวเสริมทั้งหมด เหลือไว้แค่แถวแรก
    const extraRows = container.querySelectorAll('.category-form-row:not(:first-child)');
    extraRows.forEach(row => row.remove());

    // ล้างค่าในแถวแรกและ hidden input
    const firstRow = container.querySelector('.category-form-row');
    if (firstRow) {
        firstRow.querySelector('input').value = '';
    }
    document.getElementById('cate_id_hidden').value = '';
    
    // รีเซ็ตตัวนับ
    categoryRowIndex = 1;
}

function addMaterialRow() {
    const container = document.getElementById('material-rows-container');
    if (!container) return;
    const firstRow = container.querySelector('.material-form-row');
    if (!firstRow) return;
    const newRow = firstRow.cloneNode(true);

    newRow.querySelectorAll('input, select').forEach(input => {
        if (input.type === 'checkbox' || input.type === 'radio') {
            input.checked = false;
        } else {
            input.value = '';
        }
    });

    newRow.querySelectorAll('[id]').forEach(element => {
        element.id = element.id.replace(/_0$/, `_${materialRowIndex}`);
    });

    newRow.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.name = `mate_category[${materialRowIndex}][]`;
    });

    const removeBtn = newRow.querySelector('.btn-remove-row');
    if (removeBtn) {
        removeBtn.style.display = 'block';
        removeBtn.addEventListener('click', () => {
            newRow.remove();
        }, { once: true });
    }

    container.appendChild(newRow);
    materialRowIndex++;
    initializeDatepickers(); // เรียกใช้ปฏิทินสำหรับแถวใหม่
}

function resetAddMaterialForm() {
    const container = document.getElementById('material-rows-container');
    if (!container) return;
    const extraRows = container.querySelectorAll('.material-form-row:not(:first-child)');
    extraRows.forEach(row => row.remove());
    const firstRow = container.querySelector('.material-form-row');
    if (firstRow) {
        firstRow.querySelectorAll('input, select').forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } else {
                input.value = '';
            }
        });
    }
    materialRowIndex = 1;
}

const rowsPerPage = 10;
let currentPage = 1;
let allMaterials = [];

// ================================================
// ===== จัดการ Event Listeners หลักของหน้า =====
// ================================================
document.addEventListener('DOMContentLoaded', function () {

    fetch('../material/fetch_mate.php')
        .then(response => {
            if (!response.ok) { throw new Error('Network response was not ok'); }
            return response.json();
        })
        .then(responseData => {
            if (Array.isArray(responseData)) {
                allMaterials = responseData;
            } else if (responseData && responseData.data && Array.isArray(responseData.data)) {
                allMaterials = responseData.data;
            } else {
                console.error("Received data is not in a recognized format:", responseData);
                throw new Error("Invalid data format from server.");
            }
            renderMaterialTable();
            setupMaterialPagination();
            if (typeof window.initializeDatepickers === 'function') {
                window.initializeDatepickers();
            }
        })
        .catch(error => {
            console.error('Error fetching materials:', error);
            const tableBody = document.getElementById('im-table-body');
            if(tableBody) tableBody.innerHTML = `<tr><td colspan="8" style="text-align:center; color:red; padding:32px;">ไม่สามารถโหลดข้อมูลวัตถุดิบได้: ${error.message}</td></tr>`;
        });

    const addRowBtn = document.getElementById('add-material-row-btn');
    if (addRowBtn) {
        addRowBtn.addEventListener('click', addMaterialRow);
    }

    const addCategoryRowBtn = document.getElementById('add-category-row-btn');
    if (addCategoryRowBtn) {
        addCategoryRowBtn.addEventListener('click', addCategoryRow);
    }

    const addMaterialForm = document.getElementById('addMaterialForm');
    if (addMaterialForm) {
        addMaterialForm.addEventListener('submit', function (e) {
            e.preventDefault(); 

            const formRows = document.querySelectorAll('#material-rows-container .material-form-row');
            let isFormValid = true;
            let hasAtLeastOneItem = false;

            for (const row of formRows) {
                const materialNameInput = row.querySelector('input[name="material_name[]"]');
                const quantityInput = row.querySelector('input[name="material_quantity[]"]');
                const unitSelect = row.querySelector('select[name="Unit_id[]"]');
                const minStockInput = row.querySelector('input[name="min_stock[]"]');
                const maxStockInput = row.querySelector('input[name="max_stock[]"]');
                const categoryCheckboxes = row.querySelectorAll('input[type="checkbox"]:checked');

                const name = materialNameInput.value.trim();
                const quantity = quantityInput.value;
                const unit = unitSelect.value;
                const minStock = minStockInput.value;
                const maxStock = maxStockInput.value;

                if (name !== '' || quantity !== '' || unit !== '') {
                    hasAtLeastOneItem = true;

                    if (name === '') {
                        showToast('❌ กรุณากรอก "ชื่อวัตถุดิบ"', '#e74c3c');
                        isFormValid = false;
                        break;
                    }
                    if (quantity === '') {
                        showToast('❌ กรุณากรอก "จำนวน"', '#e74c3c');
                        isFormValid = false;
                        break;
                    }
                    if (unit === '') {
                        showToast('❌ กรุณาเลือก "หน่วย"', '#e74c3c');
                        isFormValid = false;
                        break;
                    }
                    
                    if (minStock === '') {
                        showToast('❌ กรุณากรอก "จำนวนขั้นต่ำ"', '#e74c3c');
                        isFormValid = false;
                        break;
                    }
                    if (maxStock === '') {
                        showToast('❌ กรุณากรอก "จำนวนสูงสุด"', '#e74c3c');
                        isFormValid = false;
                        break;
                    }
                    if (categoryCheckboxes.length === 0) {
                        showToast('❌ กรุณาเลือก "ประเภทวัตถุดิบ" อย่างน้อย 1 รายการ', '#e74c3c');
                        isFormValid = false;
                        break;
                    }
                }
            }
            
            if (!hasAtLeastOneItem) {
                showToast('❌ กรุณากรอกข้อมูลวัตถุดิบอย่างน้อย 1 รายการ', '#e74c3c');
                return;
            }

            if (!isFormValid) {
                return;
            }

            const formData = new FormData(this);
            fetch('../material/add_material.php', { method: 'POST', body: formData })
            .then(response => {
                if (!response.ok) { return response.text().then(text => { throw new Error(text || 'Server error') }); }
                return response.text();
            })
            .then(result => {
                if (result.trim() === 'success') {
                    closePopup('addMaterialPopup');
                    showToast('✅ เพิ่มวัตถุดิบทั้งหมดเรียบร้อยแล้ว');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast('❌ มีบางอย่างผิดพลาด: ' + result, '#e74c3c');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast(`❌ เกิดข้อผิดพลาดร้ายแรง: ${error.message}`, '#e74c3c');
            });
        });
    }

    const editForm = document.getElementById('editMaterialForm');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('../material/edit_material.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(response => {
                    if (response.trim() === 'success') {
                        closePopup('editMaterialPopup');
                        showToast('✅ แก้ไขวัตถุดิบเรียบร้อยแล้ว');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        showToast('❌ เกิดข้อผิดพลาด: ' + response, '#e74c3c');
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    }

    const deleteForm = document.getElementById('deleteMaterialForm');
    if(deleteForm) {
        deleteForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('../material/delete_material.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(response => {
                    if (response.trim() === 'success') {
                        closePopup('deleteMaterialPopup');
                        showToast('✅ ลบวัตถุดิบเรียบร้อยแล้ว');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        showToast('❌ เกิดข้อผิดพลาด: ' + response, '#e74c3c');
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    }

    const categoryForm = document.getElementById('categoryForm');
    if (categoryForm) {
        categoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            // ตรวจสอบว่ามี cate_id หรือไม่ ถ้ามีแสดงว่าเป็น edit mode
            // และถ้าเป็น edit mode เราจะใช้ชื่อจาก input ตัวแรกเท่านั้น
            if (formData.get('cate_id')) {
                const firstCateName = formData.get('cate_name[]');
                formData.set('cate_name', firstCateName);
                // ลบ cate_name[] ทั้งหมดออกไปเพื่อไม่ให้ส่งค่าซ้ำซ้อน
                formData.delete('cate_name[]');
            }

            fetch('../material/save_category.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(response => {
                    if (response.trim() === 'success') {
                        closeCategoryModal();
                        fetchCategories();
                        showToast('✅ บันทึกประเภทเรียบร้อยแล้ว');
                    } else {
                        showToast('❌ เกิดข้อผิดพลาด: ' + response, '#e74c3c');
                    }
                });
        });
    }
    
    const searchInput = document.getElementById('im-search-input');
    const categoryFilter = document.getElementById('im-category-filter');
    const stockFilter = document.getElementById('im-stock-filter');
    
    function handleFilterChange() {
        currentPage = 1;
        renderMaterialTable();
        setupMaterialPagination();
    }

    if (searchInput) searchInput.addEventListener('input', handleFilterChange);
    if (categoryFilter) categoryFilter.addEventListener('change', handleFilterChange);
    if (stockFilter) stockFilter.addEventListener('change', handleFilterChange);

    document.getElementById('tab-categories')?.addEventListener('click', function() {
        showTab('categories');
        fetchCategories();
        fetchUnits();
    });

    const unitNameInput = document.getElementById('unitName');
    const baseUnitInput = document.getElementById('baseUnit');
    const conversionFactorInput = document.getElementById('conversionFactor');
    
    if(unitNameInput) unitNameInput.addEventListener('input', autoCalculateConversion);
    if(baseUnitInput) baseUnitInput.addEventListener('input', autoCalculateConversion);
    if(conversionFactorInput) conversionFactorInput.addEventListener('input', updateFormulaPreview);

    const unitForm = document.getElementById('unitForm');
    if (unitForm) {
        unitForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('../material/save_unit.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(response => {
                    if (response.trim() === 'success') {
                        closePopup('unitModal');
                        fetchUnits();
                        showToast('✅ บันทึกหน่วยวัดเรียบร้อยแล้ว');
                    } else {
                        showToast('❌ เกิดข้อผิดพลาด: ' + response, '#e74c3c');
                    }
                });
        });
    }
});

function fetchUnits() {
    fetch('../material/fetch_units.php')
        .then(res => res.json())
        .then(units => renderUnitsGrid(units))
        .catch(error => console.error("Failed to fetch units:", error));
}

function updateFormulaPreview() {
    const unitNameInput = document.getElementById('unitName');
    const baseUnitInput = document.getElementById('baseUnit');
    const factorInput = document.getElementById('conversionFactor');
    const previewBox = document.getElementById('formula-preview');

    if (!unitNameInput || !baseUnitInput || !factorInput || !previewBox) return;

    const unitName = unitNameInput.value.trim() || '[ชื่อหน่วยวัด]';
    const baseUnit = baseUnitInput.value.trim() || '[หน่วยย่อย]';
    const factorValue = parseFloat(factorInput.value);

    if (factorValue > 0) {
         const factor = factorValue.toLocaleString('en-US');
         previewBox.textContent = `ตัวอย่าง: 1 ${unitName} = ${factor} ${baseUnit}`;
    } else if (!unitNameInput.value && !baseUnitInput.value) {
        previewBox.textContent = 'กรอกข้อมูลเพื่อดูตัวอย่างสูตรการแปลง';
    }
}

function deleteUnit(unitId) {
    if (!confirm('คุณต้องการลบหน่วยวัดนี้หรือไม่? หากมีวัตถุดิบใช้อยู่อาจลบไม่ได้')) return;
    
    const formData = new FormData();
    formData.append('unit_id', unitId);

    fetch('../material/delete_unit.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(response => {
        if (response.trim() === 'success') {
            fetchUnits();
            showToast('✅ ลบหน่วยวัดเรียบร้อยแล้ว');
        } else {
            showToast('❌ เกิดข้อผิดพลาด: ' + response, '#e74c3c');
        }
    });
}

// ===================================
// ===== ฟังก์ชันสำหรับแก้ไขวัตถุดิบ =====
// ===================================
function openEditMaterialPopup(materialID) {
    const data = allMaterials.find(m => m.material_id == materialID);
    if (!data) {
        alert('ไม่พบข้อมูลวัตถุดิบ');
        return;
    }

    openPopup('editMaterialPopup');

    document.getElementById('edit_material_id').value = data.material_id;
    document.getElementById('edit_material_name').value = data.material_name;
    document.getElementById('edit_material_quantity').value = data.material_quantity;
    document.getElementById('edit_Unit_id').value = data.Unit_id;
    document.getElementById('edit_min_stock').value = data.min_stock || '';
    document.getElementById('edit_max_stock').value = data.max_stock || '';
    document.getElementById('edit_supplier').value = data.supplier || '';
    
    const dateInput = document.getElementById('edit_expiry_date');
    if (dateInput._flatpickr) {
        dateInput._flatpickr.setDate(data.expiry_date || null, true);
    } else {
        dateInput.value = data.expiry_date || '';
    }

    const selectedCateIDs = data.cate_ids || [];
    const allCheckboxes = document.querySelectorAll('#edit_category_checkboxes input[type="checkbox"]');
    
    allCheckboxes.forEach(cb => {
        const checkboxValue = parseInt(cb.value, 10);
        cb.checked = selectedCateIDs.includes(checkboxValue);
    });
}

function openDeleteMaterialPopup(materialID) {
    const deleteIdInput = document.getElementById('deleteMaterialID');
    if(deleteIdInput) {
        deleteIdInput.value = materialID;
        openPopup('deleteMaterialPopup');
    }
}

function formatThaiDate(datetime) {
    if (!datetime) return '-';
    try {
        const date = new Date(datetime);
        if (isNaN(date.getTime())) return '-';
        const months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
        return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear() + 543}`;
    } catch (e) { return '-'; }
}

function renderMaterialTable() {
    const tableBody = document.getElementById('im-table-body');
    const filtered = filterMaterials();
    
    tableBody.innerHTML = '';

    if (filtered.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="8" style="text-align:center; color:#888; padding:32px;">ไม่พบวัตถุดิบตามเงื่อนไข</td></tr>`;
        return;
    }

    const start = (currentPage - 1) * rowsPerPage;
    const pageMaterials = filtered.slice(start, start + rowsPerPage);

    pageMaterials.forEach((mat, index) => {
        
        // --- START: โค้ดส่วนคำนวณสถานะที่แก้ไขแล้ว ---
        let statusClass = 'normal';
        let statusText = 'ปกติ';
        
        // ใช้ material_quantity ในการคำนวณสถานะ เพื่อให้ตรงกับที่ผู้ใช้เห็นในคอลัมน์ "สินค้าคงเหลือ"
        const quantity = parseFloat(mat.material_quantity);
        const minStock = mat.min_stock ? parseFloat(mat.min_stock) : 0;
        
        if (quantity <= 0) {
            statusClass = 'out';
            statusText = 'หมดสต็อก';
        } else if (minStock > 0 && quantity <= minStock) {
            statusClass = 'low';
            statusText = 'ใกล้หมด';
        }
        
        // ตรวจสอบวันหมดอายุ (แยกเงื่อนไขออกมาเพื่อความชัดเจน)
        // จะทำงานก็ต่อเมื่อสถานะยังไม่เป็น "หมดสต็อก"
        if (statusClass !== 'out' && mat.expiry_date) {
            const expDate = new Date(mat.expiry_date);
            const today = new Date();
            today.setHours(0, 0, 0, 0); // เซ็ตเวลาเป็นเที่ยงคืนเพื่อการเปรียบเทียบที่แม่นยำ

            const timeDiff = expDate.getTime() - today.getTime();
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));

            // ถ้าใกล้หมดอายุ (<= 3 วัน) ให้สถานะนี้มีความสำคัญกว่า "ใกล้หมด" หรือ "ปกติ"
            if (daysDiff >= 0 && daysDiff <= 3) {
                statusClass = 'expiring';
                statusText = 'ใกล้หมดอายุ';
            } else if (daysDiff < 0) {
                // (Optional) กรณีหมดอายุไปแล้ว
                statusClass = 'expired'; // อาจจะต้องเพิ่ม CSS class นี้
                statusText = 'หมดอายุแล้ว';
            }
        }
        // --- END: โค้ดส่วนคำนวณสถานะที่แก้ไขแล้ว ---

        const row = document.createElement('tr');
        row.className = 'im-row';
        const sequenceNumber = start + index + 1;

        row.innerHTML = `
            <td>${sequenceNumber}</td>
            <td>${mat.material_name}</td>
            <td>${parseFloat(mat.material_quantity).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td>${mat.Unit_name || '-'}</td>
            <td>${mat.base_quantity ? parseFloat(mat.base_quantity).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ' + (mat.base_unit || '') : '0.00'}</td>
            <td>${formatThaiDate(mat.expiry_date)}</td>
            <td><span class="im-status im-status-${statusClass}">${statusText}</span></td>
            <td>
                <button class="im-action-btn im-edit" onclick="openEditMaterialPopup(${mat.material_id})"><i class="fas fa-edit"></i></button>
                <button class="im-action-btn im-delete" onclick="openDeleteMaterialPopup(${mat.material_id})"><i class="fas fa-trash"></i></button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}


function setupMaterialPagination() {
    const paginationDiv = document.getElementById("pagination");
    const filtered = filterMaterials();
    const totalPages = Math.ceil(filtered.length / rowsPerPage);
    
    paginationDiv.innerHTML = '';
    if (totalPages <= 1) return;

    if (currentPage > 1) {
        const prevBtn = createPaginationButton('« ก่อนหน้า', () => {
            currentPage--;
            renderMaterialTable();
            setupMaterialPagination();
        });
        paginationDiv.appendChild(prevBtn);
    }

    for (let i = 1; i <= totalPages; i++) {
        const btn = createPaginationButton(i, () => {
            currentPage = i;
            renderMaterialTable();
            setupMaterialPagination();
        });
        if (i === currentPage) btn.classList.add('active');
        paginationDiv.appendChild(btn);
    }

    if (currentPage < totalPages) {
        const nextBtn = createPaginationButton('ถัดไป »', () => {
            currentPage++;
            renderMaterialTable();
            setupMaterialPagination();
        });
        paginationDiv.appendChild(nextBtn);
    }
}

function createPaginationButton(text, onClick) {
    const button = document.createElement('button');
    button.className = 'pagination-btn';
    button.innerText = text;
    button.addEventListener('click', onClick);
    return button;
}

function showTab(tab) {
    document.getElementById('tab-ingredients')?.classList.toggle('active', tab === 'ingredients');
    document.getElementById('tab-categories')?.classList.toggle('active', tab === 'categories');
    
    const ingredientsContent = document.getElementById('tab-ingredients-content');
    const categoriesContent = document.getElementById('tab-categories-content');

    if (ingredientsContent) ingredientsContent.style.display = tab === 'ingredients' ? '' : 'none';
    if (categoriesContent) categoriesContent.style.display = tab === 'categories' ? '' : 'none';
}

function closeCategoryModal() {
    closePopup('categoryModal');
}

function fetchCategories() {
    fetch('../material/fetch_categories.php')
        .then(res => res.json())
        .then(categories => renderCategoriesGrid(categories))
        .catch(error => console.error("Failed to fetch categories:", error));
}

function renderCategoriesGrid(categories) {
    const grid = document.getElementById('categories-grid');
    if (!grid) return;

    const excludeNames = ['เมนูโรตี', 'เมนูยอดฮิต', 'เมนูแนะนำ', 'เครื่องดื่ม', 'เครื่องดื่มร้อน', 'เครื่องดื่มเย็น'];
    const filteredCategories = categories.filter(cat => !excludeNames.includes(cat.cate_name));
    
    grid.innerHTML = '';
    if (filteredCategories.length === 0) {
        grid.innerHTML = '<div style="color:#888; text-align:center; padding:32px;">ไม่มีประเภทวัตถุดิบ</div>';
        return;
    }

    filteredCategories.forEach(cat => {
        const div = document.createElement('div');
        div.className = 'im-category-card';
        // แก้ไขการส่งข้อมูล: ต้องมั่นใจว่า string ที่ส่งไปเป็น JSON ที่ถูกต้อง
        const catData = JSON.stringify(cat).replace(/'/g, "\\'");

        div.innerHTML = `
            <div class="im-category-info">
                <div class="im-category-name">${cat.cate_name}</div>
                <div class="im-category-count">${cat.count || 0} รายการ</div>
            </div>
            <div class="im-category-actions">
                <button class="im-action-btn im-edit" title="แก้ไข" onclick='openCategoryModal(true, ${JSON.stringify(catData)})'><i class="fas fa-edit"></i></button>
                <button class="im-action-btn im-delete" title="ลบ" onclick='deleteCategory(${cat.cate_id})'><i class="fas fa-trash"></i></button>
            </div>
        `;
        grid.appendChild(div);
    });
}

function handleEditUnitClick(buttonElement) {
    // ดึงข้อมูล JSON string จาก data-unit attribute
    const unitDataString = buttonElement.getAttribute('data-unit');
    if (unitDataString) {
        try {
            // แปลง JSON string กลับเป็น object
            const unitObject = JSON.parse(unitDataString);
            // เรียกฟังก์ชันเปิด Popup โดยส่ง "object" เข้าไปตรงๆ
            openUnitModal(true, unitObject);
        } catch (e) {
            console.error("Failed to parse unit data from data-attribute:", e);
            showToast("❌ ไม่สามารถอ่านข้อมูลหน่วยวัดได้", "#e74c3c");
        }
    }
}

function renderUnitsGrid(units) {
    const tableBody = document.getElementById('units-table-body');
    if (!tableBody) return;

    tableBody.innerHTML = '';
    if (units.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 2rem;">ไม่มีข้อมูลหน่วยวัด</td></tr>';
        return;
    }

    units.forEach((unit, index) => {
        const row = document.createElement('tr');
        
        // แปลง object เป็น JSON string และ escape เครื่องหมาย " เพื่อความปลอดภัย
        const unitData = JSON.stringify(unit).replace(/"/g, '&quot;');
        
        const factor = unit.conversion_factor ? parseFloat(unit.conversion_factor).toLocaleString('en-US') : '-';
        const baseUnit = unit.base_unit || '';
        const conversionFormula = `1 ${unit.Unit_name} = ${factor} ${baseUnit}`;

        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${unit.Unit_name}</td>
            <td>${conversionFormula}</td>
            <td style="text-align: center;">
                <!-- *** จุดที่แก้ไขสำคัญ *** -->
                <!-- 1. เก็บข้อมูลไว้ใน data-unit -->
                <!-- 2. onclick เรียกฟังก์ชันใหม่ handleEditUnitClick(this) -->
                <button 
                    class="im-action-btn im-edit" 
                    title="แก้ไข" 
                    data-unit="${unitData}" 
                    onclick="handleEditUnitClick(this)">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="im-action-btn im-delete" title="ลบ" onclick='deleteUnit(${unit.Unit_id})'><i class="fas fa-trash"></i></button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

function deleteCategory(cate_id) {
    if (!confirm('คุณต้องการลบประเภทนี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้')) return;
    fetch('../material/delete_category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'cate_id=' + encodeURIComponent(cate_id)
    })
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


// START: โค้ดที่แก้ไขเรื่องการเรียกค่าเดิม
/**
 * ฟังก์ชันเปิด Popup สำหรับ "เพิ่ม" หรือ "แก้ไข" ประเภท
 * @param {boolean} isEdit - ถ้าเป็น true จะเป็นโหมดแก้ไข
 * @param {string|null} categoryJSON - ข้อมูลของประเภทในรูปแบบ JSON String
 */
function openCategoryModal(isEdit = false, categoryJSON = null) {
    resetCategoryModal(); // รีเซ็ตฟอร์มทุกครั้งที่เปิด
    
    const title = document.getElementById('categoryModalTitle');
    const addBtn = document.getElementById('add-category-row-btn');
    const hiddenIdInput = document.getElementById('cate_id_hidden'); // เพิ่มการอ้างอิง

    if (isEdit && categoryJSON) {
        // โหมดแก้ไข
        try {
            // *** แก้ไขตรงนี้: ใช้ JSON.parse แค่ครั้งเดียว ***
            const category = JSON.parse(categoryJSON.replace(/&quot;/g, '"'));
            
            title.textContent = 'แก้ไขประเภทวัตถุดิบ';
            addBtn.style.display = 'none'; // ซ่อนปุ่ม "เพิ่มอีกรายการ"
            
            // นำข้อมูลเดิมมาใส่ในฟอร์ม
            hiddenIdInput.value = category.cate_id; // ใช้ตัวแปรที่อ้างอิงไว้แล้ว
            document.getElementById('cate_name_0').value = category.cate_name;
        } catch (e) {
            console.error("Could not parse category data:", e, categoryJSON);
            showToast("❌ ไม่สามารถอ่านข้อมูลประเภทได้", "#e74c3c");
            return;
        }
    } else {
        // โหมดเพิ่มใหม่
        title.textContent = 'เพิ่มประเภทวัตถุดิบ';
        addBtn.style.display = 'block'; // แสดงปุ่ม "เพิ่มอีกรายการ"
        hiddenIdInput.value = ''; // เคลียร์ค่า ID ที่ซ่อนไว้
    }
    
    openPopup('categoryModal');
}

/**
 * ฟังก์ชันเปิด Popup สำหรับ "เพิ่ม" หรือ "แก้ไข" หน่วยวัด
 * @param {boolean} isEdit - ถ้าเป็น true จะเป็นโหมดแก้ไข
 * @param {string|null} unitJSON - ข้อมูลของหน่วยวัดในรูปแบบ JSON String
 */
function openUnitModal(isEdit = false, unitJSON = null) {
    const form = document.getElementById('unitForm');
    form.reset(); // รีเซ็ตฟอร์มทุกครั้ง
    updateFormulaPreview(); // อัปเดต preview ให้เป็นค่าเริ่มต้น

    const title = document.getElementById('unitModalTitle');

    if (isEdit && unitJSON) {
        // โหมดแก้ไข
        try {
            // แปลง JSON String กลับมาเป็น Object
            const unit = JSON.parse(unitJSON);
            
            title.textContent = 'แก้ไขหน่วยวัด';

            // นำข้อมูลเดิมมาใส่ในฟอร์ม
            document.getElementById('unitId').value = unit.Unit_id;
            document.getElementById('unitName').value = unit.Unit_name;
            document.getElementById('baseUnit').value = unit.base_unit;
            document.getElementById('conversionFactor').value = unit.conversion_factor;
            
            updateFormulaPreview(); // อัปเดต preview อีกครั้งด้วยข้อมูลใหม่
        } catch (e) {
            console.error("Could not parse unit data:", e, unitJSON);
            showToast("❌ ไม่สามารถอ่านข้อมูลหน่วยวัดได้", "#e74c3c");
            return;
        }
    } else {
        // โหมดเพิ่มใหม่
        title.textContent = 'เพิ่มหน่วยวัดใหม่';
        document.getElementById('unitId').value = ''; // เคลียร์ ID
    }

    openPopup('unitModal');
}
// END: โค้ดที่แก้ไขเรื่องการเรียกค่าเดิม

function updateFormulaPreview() {
    const unitNameInput = document.getElementById('unitName');
    const baseUnitInput = document.getElementById('baseUnit');
    const factorInput = document.getElementById('conversionFactor');
    const previewBox = document.getElementById('formula-preview');

    if (!unitNameInput || !baseUnitInput || !factorInput || !previewBox) return;

    const unitName = unitNameInput.value.trim() || '[ชื่อหน่วยวัด]';
    const baseUnit = baseUnitInput.value.trim() || '[หน่วยย่อย]';
    const factorValue = parseFloat(factorInput.value);

    if (factorValue > 0) {
         const factor = factorValue.toLocaleString('en-US');
         previewBox.textContent = `ตัวอย่าง: 1 ${unitName} = ${factor} ${baseUnit}`;
    } else if (!unitNameInput.value && !baseUnitInput.value) {
        previewBox.textContent = 'กรอกข้อมูลเพื่อดูตัวอย่างสูตรการแปลง';
    }
}

// --- 5. ฟังก์ชันสำหรับบันทึกข้อมูลหน่วยวัด ---
document.addEventListener('DOMContentLoaded', function () {
    const unitNameInput = document.getElementById('unitName');
    const baseUnitInput = document.getElementById('baseUnit');
    const conversionFactorInput = document.getElementById('conversionFactor');
    
    if(unitNameInput) unitNameInput.addEventListener('input', autoCalculateConversion); // <-- เปลี่ยน
    if(baseUnitInput) baseUnitInput.addEventListener('input', autoCalculateConversion); // <-- เปลี่ยน
    if(conversionFactorInput) conversionFactorInput.addEventListener('input', updateFormulaPreview);
    const unitForm = document.getElementById('unitForm');
    if (unitForm) {
        unitForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('../material/save_unit.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(response => {
                    if (response.trim() === 'success') {
                        closePopup('unitModal');
                        fetchUnits(); // โหลดข้อมูลหน่วยวัดใหม่
                        showToast('✅ บันทึกหน่วยวัดเรียบร้อยแล้ว');
                    } else {
                        showToast('❌ เกิดข้อผิดพลาด: ' + response, '#e74c3c');
                    }
                });
        });
    }
    // ... โค้ดเดิมใน DOMContentLoaded ของคุณ ...
});


// --- 6. ฟังก์ชันสำหรับลบหน่วยวัด ---
function deleteUnit(unitId) {
    if (!confirm('คุณต้องการลบหน่วยวัดนี้หรือไม่? หากมีวัตถุดิบใช้อยู่อาจลบไม่ได้')) return;
    
    const formData = new FormData();
    formData.append('unit_id', unitId);

    fetch('../material/delete_unit.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(response => {
        if (response.trim() === 'success') {
            fetchUnits(); // โหลดข้อมูลหน่วยวัดใหม่
            showToast('✅ ลบหน่วยวัดเรียบร้อยแล้ว');
        } else {
            showToast('❌ เกิดข้อผิดพลาด: ' + response, '#e74c3c');
        }
    });
}

// ===================================
// ===== ฟังก์ชันสำหรับแก้ไขวัตถุดิบ =====
// ===================================
function openEditMaterialPopup(materialID) {
    // 1. ค้นหาข้อมูลวัตถุดิบ (เหมือนเดิม)
    const data = allMaterials.find(m => m.material_id == materialID);
    if (!data) {
        alert('ไม่พบข้อมูลวัตถุดิบ');
        return;
    }

    console.log("ข้อมูลทั้งหมดของวัตถุดิบที่เลือก:", data);
    console.log("ประเภทของ cate_ids ที่ได้รับ:", typeof data.cate_ids);
    console.log("ค่าของ cate_ids ที่ได้รับ:", data.cate_ids);

    // 2. (สำคัญ) เปิด Popup ขึ้นมาก่อน เพื่อให้ HTML และ Flatpickr ถูกสร้างและเตรียมพร้อม
    openPopup('editMaterialPopup');

    // 3. หลังจาก Popup เปิดแล้ว ค่อยเติมข้อมูลลงไปในฟอร์ม
    document.getElementById('edit_material_id').value = data.material_id;
    document.getElementById('edit_material_name').value = data.material_name;
    document.getElementById('edit_material_quantity').value = data.material_quantity;
    document.getElementById('edit_Unit_id').value = data.Unit_id;
    
    document.getElementById('edit_min_stock').value = data.min_stock || '';
    document.getElementById('edit_max_stock').value = data.max_stock || '';
    document.getElementById('edit_supplier').value = data.supplier || '';
    
    // ตั้งค่าวันที่ (ตอนนี้ dateInput._flatpickr ควรจะพร้อมใช้งานแล้ว)
    const dateInput = document.getElementById('edit_expiry_date');
    if (dateInput._flatpickr) {
        dateInput._flatpickr.setDate(data.expiry_date || null, true);
    } else {
        dateInput.value = data.expiry_date || '';
    }

    // จัดการ Checkbox ของประเภทวัตถุดิบ
    const selectedCateIDs = data.cate_ids || [];
    const allCheckboxes = document.querySelectorAll('#edit_category_checkboxes input[type="checkbox"]');
    
    allCheckboxes.forEach(cb => {
        const checkboxValue = parseInt(cb.value, 10);
        cb.checked = selectedCateIDs.includes(checkboxValue);
    });
}

// ===================================
// ===== ฟังก์ชันสำหรับลบวัตถุดิบ ======
// ===================================
function openDeleteMaterialPopup(materialID) {
    const deleteIdInput = document.getElementById('deleteMaterialID');
    if(deleteIdInput) {
        deleteIdInput.value = materialID;
        openPopup('deleteMaterialPopup');
    }
}

function formatThaiDate(datetime) {
    if (!datetime) return '-';
    try {
        const date = new Date(datetime);
        if (isNaN(date.getTime())) return '-';
        const months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
        return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear() + 543}`;
    } catch (e) { return '-'; }
}

// --- START: แก้ไขฟังก์ชัน filter และ render ตาราง ---
function filterMaterials() {
    const search = document.getElementById('im-search-input').value.trim().toLowerCase();
    const category = document.getElementById('im-category-filter').value;
    const statusFilter = document.getElementById('im-stock-filter').value;
    
    if (!allMaterials) return [];

    return allMaterials.filter(mat => {
        const nameMatch = mat.material_name.toLowerCase().includes(search);
        const categoryMatch = (category === 'all') || (mat.cate_ids && mat.cate_ids.includes(parseInt(category.replace('cate', ''))));

        let status = 'normal';
        // ใช้ base_quantity ในการคำนวณสถานะเพื่อการกรองที่แม่นยำ
        const stock = parseFloat(mat.base_quantity);
        const min = parseFloat(mat.min_stock) || 0;
        
        if (stock <= 0) {
            status = 'out';
        } else if (min > 0 && stock <= min) {
            status = 'low';
        }

        if (mat.expiry_date) {
            const expDate = new Date(mat.expiry_date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const timeDiff = expDate.getTime() - today.getTime();
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
            if (daysDiff >= 0 && daysDiff <= 3) {
                status = 'expiring';
            }
        }
        const statusMatch = (statusFilter === 'all') || (status === statusFilter);

        return nameMatch && categoryMatch && statusMatch;
    });
}

function createPaginationButton(text, onClick) {
    const button = document.createElement('button');
    button.className = 'pagination-btn';
    button.innerText = text;
    button.addEventListener('click', onClick);
    return button;
}

function showTab(tab) {
    document.getElementById('tab-ingredients')?.classList.toggle('active', tab === 'ingredients');
    document.getElementById('tab-categories')?.classList.toggle('active', tab === 'categories');
    
    const ingredientsContent = document.getElementById('tab-ingredients-content');
    const categoriesContent = document.getElementById('tab-categories-content');

    if (ingredientsContent) ingredientsContent.style.display = tab === 'ingredients' ? '' : 'none';
    if (categoriesContent) categoriesContent.style.display = tab === 'categories' ? '' : 'none';
}

function openCategoryModal() {
    // ไม่มีการแก้ไขข้อมูลทีละหลายรายการ จะเป็นการเพิ่มใหม่อย่างเดียว
    document.getElementById('categoryModalTitle').textContent = 'เพิ่มประเภทวัตถุดิบ';
    
    // รีเซ็ตฟอร์มให้เหลือแถวเดียวและว่างเปล่า
    resetCategoryModal();

    // เปิด Popup
    openPopup('categoryModal');
}

function closeCategoryModal() {
    closePopup('categoryModal');
}

function fetchCategories() {
    fetch('../material/fetch_categories.php')
        .then(res => res.json())
        .then(categories => renderCategoriesGrid(categories))
        .catch(error => console.error("Failed to fetch categories:", error));
}

function renderCategoriesGrid(categories) {
    const grid = document.getElementById('categories-grid');
    if (!grid) return;

    const excludeNames = ['เมนูโรตี', 'เมนูยอดฮิต', 'เมนูแนะนำ', 'เครื่องดื่ม', 'เครื่องดื่มร้อน', 'เครื่องดื่มเย็น'];
    const filteredCategories = categories.filter(cat => !excludeNames.includes(cat.cate_name));
    
    grid.innerHTML = '';
    if (filteredCategories.length === 0) {
        grid.innerHTML = '<div style="color:#888; text-align:center; padding:32px;">ไม่มีประเภทวัตถุดิบ</div>';
        return;
    }

    filteredCategories.forEach(cat => {
        const div = document.createElement('div');
        div.className = 'im-category-card';
        // *** แก้ไขตรงนี้: แปลง object เป็น JSON string แค่ครั้งเดียว และใช้ &quot; เพื่อความปลอดภัย ***
        const catData = JSON.stringify(cat).replace(/"/g, '&quot;');

        div.innerHTML = `
            <div class="im-category-info">
                <div class="im-category-name">${cat.cate_name}</div>
                <div class="im-category-count">${cat.count || 0} รายการ</div>
            </div>
            <div class="im-category-actions">
                <button class="im-action-btn im-edit" title="แก้ไข" onclick="openCategoryModal(true, '${catData}')"><i class="fas fa-edit"></i></button>
                <button class="im-action-btn im-delete" title="ลบ" onclick='deleteCategory(${cat.cate_id})'><i class="fas fa-trash"></i></button>
            </div>
        `;
        grid.appendChild(div);
    });
}

function renderUnitsGrid(units) {
    const tableBody = document.getElementById('units-table-body');
    if (!tableBody) return;

    tableBody.innerHTML = '';
    if (units.length === 0) {
        // แก้ไข colspan ให้เป็น 4 ตามจำนวนคอลัมน์ใหม่
        tableBody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 2rem;">ไม่มีข้อมูลหน่วยวัด</td></tr>';
        return;
    }

    units.forEach((unit, index) => {
    const row = document.createElement('tr');
    // แก้ไขการส่งข้อมูลให้ปลอดภัยยิ่งขึ้นโดยการ escape single quote
    const unitData = JSON.stringify(unit).replace(/'/g, "\\'");
    
    // สร้างข้อความสูตรการแปลง (จากโค้ดเดิมที่ถูกต้อง)
    const factor = unit.conversion_factor ? parseFloat(unit.conversion_factor).toLocaleString('en-US') : '-';
    const baseUnit = unit.base_unit || '';
    const conversionFormula = `1 ${unit.Unit_name} = ${factor} ${baseUnit}`;

    row.innerHTML = `
        <td>${index + 1}</td>
        <td>${unit.Unit_name}</td>
        
        <td style="text-align: center;">
            <!-- แก้ไขบรรทัดนี้: ใช้ "" ครอบ onclick และ '' ครอบ ${unitData} -->
            <button class="im-action-btn im-edit" title="แก้ไข" onclick="openUnitModal(true, '${unitData}')"><i class="fas fa-edit"></i></button>
            <button class="im-action-btn im-delete" title="ลบ" onclick='deleteUnit(${unit.Unit_id})'><i class="fas fa-trash"></i></button>
        </td>
    `;
    tableBody.appendChild(row);
});
}
{/* <td>${conversionFormula}</td> */}

function deleteCategory(cate_id) {
    if (!confirm('คุณต้องการลบประเภทนี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้')) return;
    fetch('../material/delete_category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'cate_id=' + encodeURIComponent(cate_id)
    })
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