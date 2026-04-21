// Global variables
let selectedPaymentMethod = '';
let currentOrderId = 0;
let orderData = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get order ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    currentOrderId = parseInt(urlParams.get('order_id')) || 0;

    // Get order data from PHP variables (will be set by PHP)
    if (typeof window.orderData !== 'undefined') {
        orderData = window.orderData;
    }

    // Auto-select cash method for convenience if order is selected
    if (currentOrderId > 0) {
        selectPaymentMethod('cash');
    }
});

function selectOrder(orderId) {
    window.location.href = `pm.sale.php?order_id=${orderId}`;
}

function selectPaymentMethod(method) { // [แก้ไข] ไม่ต้องรับ event แล้ว
    selectedPaymentMethod = method;

    // --- [แก้ไข] ตรรกะการอัปเดตสไตล์ปุ่ม ---
    // 1. วนลูปเพื่อลบคลาส 'selected' ออกจากทุกปุ่มก่อน
    document.querySelectorAll('.payment-method-btn').forEach(btn => {
        btn.classList.remove('selected');
    });

    // 2. หาปุ่มที่ถูกคลิกโดยใช้ค่า method และเพิ่มคลาส 'selected'
    const selectedBtn = document.querySelector(`.payment-method-btn[onclick="selectPaymentMethod('${method}')"]`);
    if (selectedBtn) {
        selectedBtn.classList.add('selected');
    }
    // --- จบส่วนที่แก้ไข ---

    // Show/hide cash input (ส่วนนี้ทำงานถูกต้องแล้ว)
    const cashSection = document.getElementById('cash-input-section');
    if (method === 'cash') {
        cashSection.style.display = 'block';
    } else {
        cashSection.style.display = 'none';
        document.getElementById('receivedAmount').value = '';
        document.getElementById('change-display').style.display = 'none';
    }

    updateProcessButton();
}

// --- EDITED FUNCTION ---
function calculateChange() {
    const receivedAmount = parseFloat(document.getElementById('receivedAmount').value) || 0;
    const totalAmount = orderData ? orderData.total_amount : 0;
    const change = receivedAmount - totalAmount;

    const changeDisplay = document.getElementById('change-display');
    const changeAmount = document.getElementById('changeAmount');

    if (receivedAmount >= totalAmount) {
        changeDisplay.style.display = 'block';
        // ใช้ toLocaleString เพื่อเพิ่มลูกน้ำคั่นหลักพัน
        changeAmount.textContent = `${change.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} บาท`;
        changeAmount.style.color = change >= 0 ? '#27ae60' : '#e74c3c';
    } else {
        changeDisplay.style.display = 'none';
    }

    updateProcessButton();
}


function updateProcessButton() {
    const processBtn = document.getElementById('processPaymentBtn');
    const receivedAmount = parseFloat(document.getElementById('receivedAmount').value) || 0;
    const totalAmount = orderData ? orderData.total_amount : 0;

    if (selectedPaymentMethod === 'cash') {
        processBtn.disabled = receivedAmount < totalAmount;
    } else {
        processBtn.disabled = !selectedPaymentMethod;
    }
}

function processPayment() {
    if (!selectedPaymentMethod) {
        alert('กรุณาเลือกวิธีการชำระเงิน');
        return;
    }
    
    const orderId = window.orderData.order_id;
    const paymentMethod = selectedPaymentMethod;
    const totalAmount = parseFloat(window.orderData.total_amount);

    let receivedAmount = 0;
    let changeAmount = 0;

    if (paymentMethod === 'cash') {
        const receivedInput = document.getElementById('receivedAmount');
        receivedAmount = parseFloat(receivedInput.value) || 0;

        if (receivedAmount < totalAmount) {
            alert('จำนวนเงินที่รับมาน้อยกว่ายอดที่ต้องชำระ');
            return;
        }

        changeAmount = receivedAmount - totalAmount;
    }

    // สร้างข้อมูลที่จะส่ง
    const bodyData = `order_id=${orderId}` +
                     `&payment_method=${paymentMethod}` +
                     `&received_amount=${receivedAmount}` +
                     `&change_amount=${changeAmount}` +
                     `&action=mark_as_paid`; // <-- เพิ่ม action ที่นี่

    // แก้ไข URL ใน fetch ให้ชี้ไปที่ update_order_status.php โดยตรง
    fetch('../payment/update_order_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: bodyData
    })
    .then(res => {
        // เพิ่มการตรวจสอบสถานะของ response ก่อนแปลงเป็น JSON
        if (!res.ok) {
            throw new Error('Network response was not ok');
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            // เรียกใช้ฟังก์ชัน showReceipt เพื่อแสดงใบเสร็จ
            showReceipt(); // ไม่ต้องส่งค่าไปแล้ว เพราะฟังก์ชันดึงจากตัวแปร global
        } else {
            alert('เกิดข้อผิดพลาด: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ หรือการประมวลผลฝั่งเซิร์ฟเวอร์');
    });
}

// --- EDITED FUNCTION ---
function showReceipt() {
    if (!orderData) return;

    // Populate common receipt data
    document.getElementById('receiptOrderId').textContent = `#${orderData.order_id}`;
    document.getElementById('receiptTime').textContent = formatTime(orderData.Order_date);
    document.getElementById('receiptTotal').textContent = `${formatNumber(orderData.total_amount)} บาท`;

    // Handle dine-in vs takeaway specific fields
    const tableRow = document.getElementById('receipt-table-row');
    const customerNameSpan = document.getElementById('receiptCustomerName');
    
    if (orderData.service_type === 'dine-in') {
        customerNameSpan.textContent = '-'; // For dine-in, customer name is not primary
        document.getElementById('receiptTable').textContent = orderData.table_num;
        tableRow.style.display = 'flex'; // Show table row
    } else {
        customerNameSpan.textContent = orderData.customer_name;
        tableRow.style.display = 'none'; // Hide table row
    }

    // Set payment method
    const methodNames = {
        'cash': 'เงินสด',
        'qr': 'QR Code'
    };
    document.getElementById('receiptPaymentMethod').textContent = methodNames[selectedPaymentMethod];

    // Populate items
    const receiptItems = document.getElementById('receiptItems');
    receiptItems.innerHTML = ''; // Clear previous items

    if (orderData.items && orderData.items.length > 0) {
        orderData.items.forEach(item => {
            let itemDiv = document.createElement('div');
            itemDiv.className = 'receipt-item-row';
            itemDiv.innerHTML = `
                <span>${item.menu_name} x${item.quantity}</span>
                <span>${formatNumber(item.menu_price * item.quantity)}</span>
            `;
            receiptItems.appendChild(itemDiv);
        });
    }

    // Show cash info if applicable
    if (selectedPaymentMethod === 'cash') {
        const receivedAmount = parseFloat(document.getElementById('receivedAmount').value) || 0;
        const totalAmount = orderData.total_amount;
        const change = receivedAmount - totalAmount;

        document.getElementById('receiptReceivedAmount').textContent = `${formatNumber(receivedAmount)} บาท`;
        document.getElementById('receiptChange').textContent = `${change.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} บาท`;
        document.getElementById('receiptCashInfo').style.display = 'block';
    } else {
        document.getElementById('receiptCashInfo').style.display = 'none';
    }

    document.getElementById('receiptModal').style.display = 'flex';
}

function closeReceipt() {
    document.getElementById('receiptModal').style.display = 'none';
    // Refresh the page to update order status
    window.location.reload();
}

function printReceipt() {
    window.print();
}

function filterOrders() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const orderCards = document.querySelectorAll('.order-card');

    orderCards.forEach(card => {
        const orderId = card.querySelector('h3').textContent.toLowerCase();
        const customerName = card.querySelector('.customer-name').textContent.toLowerCase();
        const tableInfo = card.querySelector('.order-time').textContent.toLowerCase();

        if (orderId.includes(searchTerm) || customerName.includes(searchTerm) || tableInfo.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Utility functions
function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('th-TH', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    });
}

// --- EDITED FUNCTION ---
function formatNumber(number) {
    // แปลงเป็น float และใช้ toLocaleString เพื่อจัดรูปแบบให้มีลูกน้ำและทศนิยม 2 ตำแหน่ง
    return parseFloat(number).toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}


// Set order data from PHP
function setOrderData(data) {
    orderData = data;
    currentOrderId = data ? data.order_id : 0;
}

// Sidebar toggle function
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const content = document.querySelector('.main-content');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
    if (content) {
        content.classList.toggle('shifted');
    }
}

// Initialize sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
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
    document.addEventListener('click', function (e) {
        if (sidebar && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
            sidebar.classList.remove('active');
            if (content) content.classList.remove('shifted');
        }
    });

    // ปิด sidebar เมื่อคลิกเมนู
    const menuLinks = document.querySelectorAll('#sidebar a');
    menuLinks.forEach(link => {
        link.addEventListener('click', () => {
            sidebar.classList.remove('active');
            if (content) content.classList.remove('shifted');
        });
    });

    // Logout confirmation
    const logoutBtn = document.querySelector('.logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            if (!confirm('คุณต้องการออกจากระบบใช่หรือไม่?')) {
                e.preventDefault();
            }
        });
    }

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
