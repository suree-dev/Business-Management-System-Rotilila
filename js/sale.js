// Toggle Sidebar
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

function showReceipt() {
    if (!orderData) return;
    
    // Populate receipt data
    document.getElementById('receiptOrderId').textContent = `#${orderData.order_id}`;
    document.getElementById('receiptCustomerName').textContent = orderData.service_type === 'dine-in' ? 
        `โต๊ะ ${orderData.table_num}` : orderData.customer_name;
    document.getElementById('receiptTable').textContent = orderData.service_type === 'dine-in' ? 
        orderData.table_num : '-';
    document.getElementById('receiptTime').textContent = formatTime(orderData.Order_date);
    document.getElementById('receiptTotal').textContent = `${formatNumber(orderData.total_amount)} บาท`;
    
    // Set payment method
    const methodNames = {
        'cash': 'เงินสด',
        'qr': 'QR Code'
    };
    document.getElementById('receiptPaymentMethod').textContent = methodNames[selectedPaymentMethod];
    
    // Populate items
    const receiptItems = document.getElementById('receiptItems');
    receiptItems.innerHTML = '';
    
    if (orderData.items && orderData.items.length > 0) {
        orderData.items.forEach(item => {
            let itemDiv = document.createElement('div');
            itemDiv.className = 'receipt-item-row';
            itemDiv.innerHTML = `
                <span>${item.menu_name} x${item.quantity}</span>
                <span>${formatNumber(item.menu_price * item.quantity)} บาท</span>
            `;
            receiptItems.appendChild(itemDiv);
        });
    }
    
    // Show cash info if applicable
    if (selectedPaymentMethod === 'cash') {
        const receivedAmount = parseFloat(document.getElementById('receivedAmount').value) || 0;
        const totalAmount = orderData.total_amount;
        const change = receivedAmount - totalAmount;
        
        document.getElementById('receiptReceivedAmount').textContent = `${receivedAmount.toFixed(2)} บาท`;
        document.getElementById('receiptChange').textContent = `${change.toFixed(2)} บาท`;
        document.getElementById('receiptCashInfo').style.display = 'block';
    } else {
        document.getElementById('receiptCashInfo').style.display = 'none';
    }
    
    document.getElementById('receiptModal').style.display = 'flex';
}


// Update material quantities based on order items
