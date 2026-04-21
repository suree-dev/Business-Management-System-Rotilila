// ฟังก์ชันสำหรับสลับการแสดงผลรหัสผ่าน
function togglePasswordVisibility(fieldId) {
    const input = document.getElementById(fieldId);
    const icon = input.nextElementSibling.nextElementSibling.nextElementSibling.querySelector('i'); // หาก HTML เปลี่ยนต้องแก้ตรงนี้

    const isHidden = input.type === "password";
    input.type = isHidden ? "text" : "password";

    icon.classList.toggle("fa-eye", isHidden);
    icon.classList.toggle("fa-eye-slash", !isHidden);
}


document.getElementById('resetForm').addEventListener('submit', function(event) {
    event.preventDefault(); // หยุดการ submit ปกติ

    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const errorDiv = document.getElementById('password-error');
    
    errorDiv.style.display = 'none';
    errorDiv.innerHTML = '';

    // 1. ตรวจสอบว่ารหัสผ่านตรงกันหรือไม่
    if (newPassword !== confirmPassword) {
        errorDiv.innerHTML = '<i class="fas fa-times-circle"></i> รหัสผ่านทั้งสองช่องไม่ตรงกัน';
        errorDiv.style.display = 'block';
        return;
    }

    // 2. ตรวจสอบความยาวขั้นต่ำ 8 ตัวอักษร
    if (newPassword.length < 8) {
        errorDiv.innerHTML = '<i class="fas fa-times-circle"></i> รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
        errorDiv.style.display = 'block';
        return;
    }

    // 3. ตรวจสอบว่ามีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว
    if (!/[A-Z]/.test(newPassword)) {
        errorDiv.innerHTML = '<i class="fas fa-times-circle"></i> รหัสผ่านต้องมีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว';
        errorDiv.style.display = 'block';
        return;
    }
    
    // ถ้าผ่านทุกเงื่อนไข ให้ submit ฟอร์ม
    this.submit();
});