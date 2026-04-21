function togglePassword() {
  const input = document.getElementById("password");
  const icon = document.querySelector("#togglePasswordBtn i");

  const isHidden = input.type === "password";
  input.type = isHidden ? "text" : "password";

  icon.classList.toggle("fa-eye", isHidden);
  icon.classList.toggle("fa-eye-slash", !isHidden);
}

function checkUsername() {
            const username = document.getElementById('username').value.trim();
            const passwordErrorDiv = document.getElementById('password-error');
            
            passwordErrorDiv.textContent = "";
            passwordErrorDiv.style.display = 'none';

            if (!username) {
                passwordErrorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> กรุณากรอกชื่อผู้ใช้';
                passwordErrorDiv.style.display = 'block';
                return;
            }

            fetch(`../auth/check_user.php?username=${encodeURIComponent(username)}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.exists) {
                        passwordErrorDiv.innerHTML = '<i class="fas fa-times-circle"></i> ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                        passwordErrorDiv.style.display = 'block';
                    }
                })
                .catch(() => {
                    passwordErrorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้';
                    passwordErrorDiv.style.display = 'block';
                });
        }

        function checkPassword() {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const passwordErrorDiv = document.getElementById('password-error');
            
            passwordErrorDiv.textContent = "";
            passwordErrorDiv.style.display = 'none';

            if (!username || !password) {
                passwordErrorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
                passwordErrorDiv.style.display = 'block';
                return;
            }

            fetch('../auth/check_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
            })
            .then(res => res.json())
            .then(data => {
                if (!data.valid) {
                    passwordErrorDiv.innerHTML = '<i class="fas fa-times-circle"></i> ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                    passwordErrorDiv.style.display = 'block';
                }
            })
            .catch(() => {
                passwordErrorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> เกิดข้อผิดพลาดในการตรวจสอบ';
                passwordErrorDiv.style.display = 'block';
            });
        }

async function validateLoginForm() {
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const username = usernameInput.value.trim();
    const password = passwordInput.value.trim();
    const passwordErrorDiv = document.getElementById('password-error');
    const loginButton = document.getElementById('loginButton');
    const loadingDiv = document.getElementById('loading');

    passwordErrorDiv.textContent = "";
    passwordErrorDiv.style.display = 'none';

    // ✅ เพิ่ม: ตรวจสอบช่องว่างแบบละเอียด
    if (!username && !password) {
        passwordErrorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
        passwordErrorDiv.style.display = 'block';
        usernameInput.focus();
        return false;
    }

    if (!username) {
        passwordErrorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> กรุณากรอกชื่อผู้ใช้';
        passwordErrorDiv.style.display = 'block';
        usernameInput.focus();
        return false;
    }

    if (!password) {
        passwordErrorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> กรุณากรอกรหัสผ่าน';
        passwordErrorDiv.style.display = 'block';
        passwordInput.focus();
        return false;
    }

    // Show loading state
    loginButton.classList.add('loading');
    loginButton.textContent = 'กำลังตรวจสอบ...';
    loadingDiv.style.display = 'block';

    try {
        const response = await fetch('../auth/check_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
        });

        const data = await response.json();

        if (!data.valid) {
            passwordErrorDiv.innerHTML = '<i class="fas fa-times-circle"></i> ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
            passwordErrorDiv.style.display = 'block';
            passwordInput.focus();
            return false;
        } else {
            loginButton.textContent = '🎉 เข้าสู่ระบบสำเร็จ!';
            return true;
        }
    } catch (error) {
        passwordErrorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> เกิดข้อผิดพลาดในการตรวจสอบ';
        passwordErrorDiv.style.display = 'block';
        return false;
    } finally {
        setTimeout(() => {
            loginButton.classList.remove('loading');
            loginButton.textContent = '🍽️ เข้าสู่ระบบร้าน';
            loadingDiv.style.display = 'none';
        }, 1500);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const loginButton = document.getElementById("loginButton");
    const loginForm = document.getElementById("loginForm");

    // คลิกปุ่ม
    loginButton.addEventListener("click", async () => {
        const isValid = await validateLoginForm();
        if (isValid) {
            loginForm.submit();
        }
    });

    // ✅ เพิ่มฟีเจอร์: กด Enter แล้ว validate ได้
    loginForm.addEventListener("submit", async (event) => {
        event.preventDefault(); // ดักการส่งปกติ
        const isValid = await validateLoginForm();
        if (isValid) {
            loginForm.submit();
        }
    });

    // เอฟเฟกต์โหลดหน้า
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.8s ease';
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 100);
});