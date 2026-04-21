 function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const content = document.querySelector('.container') || document.querySelector('.main-content');

      sidebar.classList.toggle('active');
      if (content) content.classList.toggle('shifted');
  }

  document.addEventListener('DOMContentLoaded', function() {
    
    // --- จัดการ Sidebar ---
    const sidebar = document.getElementById('sidebar');
    const content = document.querySelector('.container') || document.querySelector('.main-content');
    const toggleBtn = document.getElementById('toggleSidebar');

    if (toggleBtn && sidebar && content) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
            content.classList.toggle('shifted');
        });
    }
      });
    document.addEventListener('click', function (e) {
        if (sidebar && !sidebar.contains(e.target) && toggleBtn && !toggleBtn.contains(e.target)) {
            if (sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                content.classList.remove('shifted');
            }
        }
    });

    filterByStatus('all');

    const menuLinks = document.querySelectorAll('#sidebar a');
  menuLinks.forEach(link => {
    link.addEventListener('click', () => {
      sidebar.classList.remove('active');
      if (content) content.classList.remove('shifted');
    });
  });

function openPopup(id) {
  document.getElementById(id).style.display = 'flex';
  if (id === 'addRecipePopup') resetAddRecipeForm();
}

function closePopup(id) {
  document.getElementById(id).style.display = 'none';
  if (id === 'addRecipePopup') resetAddRecipeForm();
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

/* เพิ่มวัตถุดิบ */
function openPopup(id) {
  document.getElementById(id).style.display = 'flex';
  if (id === 'addRecipePopup') resetAddRecipeForm();
}

function closePopup(id) {
  document.getElementById(id).style.display = 'none';
  if (id === 'addRecipePopup') resetAddRecipeForm();
}

document.getElementById('addRecipeForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch('../recipe/add_recipe.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(response => {
    if (response.trim() === 'success') {
    closePopup('addRecipePopup');
    showToast('✅ เพิ่มสูตรเรียบร้อยแล้ว');
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

function updateAddRecipeUnitIDStyle() {
  const select = document.getElementById('addUnitID');
  if (select.value === "") {
    select.classList.add('placeholder');
    select.classList.remove('select-active');
  } else {
    select.classList.remove('placeholder');
    select.classList.add('select-active');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  updateAddRecipeUnitIDStyle();
  document.getElementById('addUnitID').addEventListener('change', updateAddRecipeUnitIDStyle);
});


/* แก้ไขวัตถุดิบ */
document.getElementById('editRecipeForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch('../recipe/edit_recipe.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(response => {
    if (response.trim() === 'success') {
    closePopup('editRecipePopup');
    showToast('✅ แก้ไขสูตรเรียบร้อยแล้ว');
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

function openEditRecipePopup(ingredientID) {
  fetch(`../recipe/edit_recipe.php?ingredient_id=${ingredientID}`)
    .then(res => res.json())
    .then(data => {
      if (data.error) {
        alert(data.error);
        return;
      }

      document.getElementById('editRecipeID').value = data.ingredient_id;  // ต้องใส่ให้ถูก key ตามที่ส่งกลับ
      document.getElementById('editRecipeName').value = data.Recipes_name;
      document.getElementById('editMaterialName').value = data.material_name;
      document.getElementById('editIngredientQuantity').value = data.ingr_quantity;
      document.getElementById('editUnitID').value = data.unit_id;
      document.getElementById('editRecipePopup').style.display = 'flex';
    })
    .catch(error => {
      console.error('Error fetching recipe data:', error);
      alert('ไม่สามารถโหลดข้อมูลวัตถุดิบได้');
    });
}


/* ลบวัตถุดิบ */
document.getElementById('deleteRecipeForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch('../recipe/delete_recipe.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(response => {
    if (response.trim() === 'success') {
      closePopup('deleteRecipePopup');
      showToast('✅ ลบสูตรเรียบร้อยแล้ว');
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

function openDeleteRecipePopup(recipeID) {
  document.getElementById('deleteRecipeID').value = recipeID;
  openPopup('deleteRecipePopup');
}

function resetAddRecipeForm() {
  const form = document.getElementById('addRecipeForm');
  if (form) form.reset();
}

function addIngredientRow() {
  const container = document.getElementById('ingredients-container');
  const row = document.createElement('div');
  row.className = 'ingredient-row';
  row.innerHTML = `
    <input type="text" name="material_name[]" placeholder="ชื่อวัตถุดิบ" required>
    <input type="number" name="ingr_quantity[]" placeholder="จำนวน" required>
    <select name="unit_id[]" required>
      <option value="" disabled selected hidden>-- เลือกหน่วย --</option>
      <option value="1">กิโลกรัม</option>
      <option value="2">ช้อนโต๊ะ</option>
      <option value="3">ถ้วย</option>
      <option value="4">ลิตร</option>
      <option value="5">ขีด</option>
      <option value="6">กรัม</option>
      <option value="7">ฟอง</option>
      <option value="8">ลูก</option>
      <option value="9">ทัพพี</option>
      <option value="10">ช้อนชา</option>
      <option value="11">มิลลิลิตร</option>
    </select>
    <button type="button" class="remove-ingredient-btn" onclick="removeIngredientRow(this)">ลบ</button>
  `;
  container.appendChild(row);
}

function removeIngredientRow(btn) {
  btn.parentElement.remove();
}

function addEditIngredientRow(material = '', quantity = '', unit = '', ingredientID = '') {
  const container = document.getElementById('edit-ingredients-container');
  const row = document.createElement('div');
  row.className = 'ingredient-row';
  row.innerHTML = `
    <input type="hidden" name="ingredient_id[]" value="${ingredientID}">
    <input type="text" name="material_name[]" value="${material}" placeholder="ชื่อวัตถุดิบ" required>
    <input type="number" step="0.01" name="ingr_quantity[]" value="${quantity}" placeholder="จำนวน" required>
    <select name="unit_id[]" required>
      <option value="" disabled ${unit === '' ? 'selected' : ''} hidden>-- เลือกหน่วย --</option>
      <option value="1" ${unit == 1 ? 'selected' : ''}>กิโลกรัม</option>
      <option value="2" ${unit == 2 ? 'selected' : ''}>ช้อนโต๊ะ</option>
      <option value="3" ${unit == 3 ? 'selected' : ''}>ถ้วย</option>
      <option value="4" ${unit == 4 ? 'selected' : ''}>ลิตร</option>
      <option value="5" ${unit == 5 ? 'selected' : ''}>ขีด</option>
      <option value="6" ${unit == 6 ? 'selected' : ''}>กรัม</option>
      <option value="7" ${unit == 7 ? 'selected' : ''}>ฟอง</option>
      <option value="8" ${unit == 8 ? 'selected' : ''}>ลูก</option>
      <option value="9" ${unit == 9 ? 'selected' : ''}>ทัพพี</option>
      <option value="10" ${unit == 10 ? 'selected' : ''}>ช้อนชา</option>
      <option value="11" ${unit == 11 ? 'selected' : ''}>มิลลิลิตร</option>
    </select>
    <button type="button" class="remove-ingredient-btn" onclick="removeIngredientRow(this)">ลบ</button>
  `;
  container.appendChild(row);
}



// เวลาคลิก "แก้ไข" ให้โหลดข้อมูลวัตถุดิบทั้งหมดของสูตรนั้นมาแสดง
function openEditRecipePopup(recipeID) {
  fetch(`../recipe/get_recipe_detail.php?Recipes_id=${recipeID}`)
    .then(res => res.json())
    .then(data => {
      document.getElementById('editRecipeID').value = data.Recipes_id;
      document.getElementById('editRecipeName').value = data.Recipes_name;
      const container = document.getElementById('edit-ingredients-container');
      container.innerHTML = '';
      data.ingredients.forEach(ing => {
        // ส่ง ingredient_id ไปด้วย
        addEditIngredientRow(ing.material_name, ing.ingr_quantity, ing.unit_id, ing.ingredient_id);
      });
      document.getElementById('editRecipePopup').style.display = 'flex';
    });
}

