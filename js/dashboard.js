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


// ปิด sidebar เมื่อคลิกเมนูใดเมนูหนึ่ง
document.addEventListener('DOMContentLoaded', function () {
  const menuLinks = document.querySelectorAll('#sidebar-menu a');
  const sidebar = document.getElementById('sidebar');
  const content = document.querySelector('.main-content');

  menuLinks.forEach(link => {
    link.addEventListener('click', () => {
      sidebar.classList.remove('active');
      content.classList.remove('shifted');
    });
  });
});

document.addEventListener('DOMContentLoaded', function () {
    const chartFont = 'Sarabun, sans-serif';

    // --- START: โค้ดสำหรับปฏิทิน พ.ศ. (แก้ไขใหม่) ---
    flatpickr.localize(flatpickr.l10ns.th);

    // ฟังก์ชันสำหรับแปลงปีในปฏิทินและในช่อง Input
    function updateToThaiYear(instance) {
        // แปลงปีในช่องแสดงผล (altInput)
        if (instance.altInput && instance.selectedDates.length > 0) {
            const selectedDate = instance.selectedDates[0];
            const gregorianYear = selectedDate.getFullYear();
            const thaiYear = gregorianYear + 543;
            const displayValue = instance.formatDate(selectedDate, "d/m/Y");
            instance.altInput.value = displayValue.replace(gregorianYear.toString(), thaiYear.toString());
        }

        // แปลงปีที่แสดงบนหัวปฏิทิน
        const yearElement = instance.calendarContainer.querySelector('.flatpickr-current-month .numInput.cur-year');
        if (yearElement) {
            const currentYear = parseInt(yearElement.value, 10);
            if (!isNaN(currentYear)) {
                 yearElement.value = currentYear + 543;
            }
        }
    }
    
    const datePickerConfig = {
        altInput: true,
        altFormat: "d/m/Y",       // รูปแบบที่แสดงให้ผู้ใช้เห็น
        dateFormat: "Y-m-d",      // รูปแบบที่ส่งไปให้ server
        locale: "th",
        // ดักจับ Event ต่างๆ เพื่อเรียกใช้ฟังก์ชันแปลงปี
        onReady: (selectedDates, dateStr, instance) => updateToThaiYear(instance),
        onChange: (selectedDates, dateStr, instance) => updateToThaiYear(instance),
        onMonthChange: (selectedDates, dateStr, instance) => {
            setTimeout(() => updateToThaiYear(instance), 100);
        },
        onYearChange: (selectedDates, dateStr, instance) => {
             setTimeout(() => updateToThaiYear(instance), 100);
        },
        onOpen: (selectedDates, dateStr, instance) => {
             setTimeout(() => updateToThaiYear(instance), 100);
        }
    };
    
    flatpickr("#start_date", { ...datePickerConfig, defaultDate: "<?= htmlspecialchars($start_date_input) ?>" });
    flatpickr("#end_date", { ...datePickerConfig, defaultDate: "<?= htmlspecialchars($end_date_input) ?>" });
    // --- END: โค้ดสำหรับปฏิทิน ---
});

