/* ===== JavaScript สำหรับหน้า menu.php ===== */

// ฟังก์ชันสลับหมวดหมู่เมนู
function showCategory(category) {
    // อัพเดท tab ที่เลือก
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // หาหน้าปัจจุบันและหน้าปลายทาง
    const currentPage = document.querySelector('.menu-page.active');
    const targetPage = document.getElementById(category + '-page');
    
    if (currentPage && targetPage && currentPage !== targetPage) {
        // กำหนดทิศทางการสไลด์
        const currentIndex = getPageIndex(currentPage.id);
        const targetIndex = getPageIndex(targetPage.id);
        const slideDirection = targetIndex > currentIndex ? 'slide-left' : 'slide-right';

        // เตรียม fade out + slide
        currentPage.style.transition = 'opacity 0.4s, transform 0.4s';
        currentPage.style.opacity = '0';
        currentPage.style.transform = slideDirection === 'slide-left' ? 'translateX(-60px)' : 'translateX(60px)';
        currentPage.classList.remove('active');
        
        // เตรียม targetPage fade in + slide
        targetPage.style.display = 'block';
        targetPage.style.opacity = '0';
        targetPage.style.transform = slideDirection === 'slide-left' ? 'translateX(60px)' : 'translateX(-60px)';
        setTimeout(() => {
            targetPage.classList.add('active');
            targetPage.style.transition = 'opacity 0.4s, transform 0.4s';
            targetPage.style.opacity = '1';
            targetPage.style.transform = 'translateX(0)';
        }, 10);

        // หลัง animation จบ ค่อยซ่อน currentPage
        setTimeout(() => {
            currentPage.style.transition = '';
            currentPage.style.opacity = '';
            currentPage.style.transform = '';
            currentPage.style.display = 'none';
            targetPage.style.transition = '';
            targetPage.style.opacity = '';
            targetPage.style.transform = '';
            // รันการค้นหาใหม่เมื่อสลับหน้า
            filterMenu();
        }, 410);
    }
}

// ฟังก์ชันหาดัชนีของหน้า
function getPageIndex(pageId) {
    const pages = ['roti', 'drink'];
    return pages.indexOf(pageId.replace('-page', ''));
}

// ฟังก์ชันค้นหาเมนู
function filterMenu() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    
    // เราจะค้นหาเมนูทั้งหมดในทุกๆ แท็บ ไม่ใช่แค่แท็บที่ active
    const allMenuCards = document.querySelectorAll('.menu-card'); 
    
    let rotiHasResults = false;
    let drinkHasResults = false;

    allMenuCards.forEach(card => {
        const menuName = card.dataset.name.toLowerCase(); // อ่านจาก data-name เพื่อประสิทธิภาพที่ดีกว่า
        
        if (menuName.includes(searchTerm)) {
            card.style.display = 'block'; // ถ้าเจอ ให้แสดงเมนูนี้

            // ตรวจสอบว่าเมนูที่เจออยู่ในแท็บไหน เพื่อใช้แสดง/ซ่อนข้อความ "ไม่พบผลลัพธ์"
            if (card.closest('#roti-page')) {
                rotiHasResults = true;
            }
            if (card.closest('#drink-page')) {
                drinkHasResults = true;
            }
        } else {
            card.style.display = 'none'; // ถ้าไม่เจอ ให้ซ่อนเมนูนี้
        }
    });

    // เรียกใช้ฟังก์ชันแสดงข้อความ "ไม่พบผลลัพธ์" สำหรับแต่ละแท็บ
    showNoResultsMessage(document.getElementById('roti-page'), !rotiHasResults && searchTerm !== '');
    showNoResultsMessage(document.getElementById('drink-page'), !drinkHasResults && searchTerm !== '');
}

// ฟังก์ชันล้างการค้นหา
function clearSearch() {
    document.getElementById('searchInput').value = '';
    filterMenu();
}

// ฟังก์ชันแสดงข้อความเมื่อไม่พบผลลัพธ์
function showNoResultsMessage(page, show) {
    let noResultsDiv = page.querySelector('.no-results-message');
    
    if (show) {
        if (!noResultsDiv) {
            noResultsDiv = document.createElement('div');
            noResultsDiv.className = 'no-results-message';
            noResultsDiv.innerHTML = `
                <div style="text-align: center; padding: 40px 20px; color: #718096;">
                    <div style="font-size: 3em; margin-bottom: 15px;">🔍</div>
                    <h3 style="margin-bottom: 10px; color: #4a5568;">ไม่พบเมนูที่ค้นหา</h3>
                    <p>ลองค้นหาด้วยคำอื่น หรือตรวจสอบการสะกดคำ</p>
                </div>
            `;
            page.appendChild(noResultsDiv);
        }
        noResultsDiv.style.display = 'block';
    } else if (noResultsDiv) {
        noResultsDiv.style.display = 'none';
    }
}

// ฟังก์ชันเปิด popup เมนู
function openPopup(name, price, image) {
    document.getElementById('popup-name').textContent = name;
    document.getElementById('popup-price').textContent = price + 'บาท';
    document.getElementById('popup-image').src = image;
    document.getElementById('popup-qty').textContent = '1';
    document.getElementById('popup-description').value = '';
    
    document.getElementById('menu-popup-overlay').style.display = 'flex';
    document.getElementById('menu-popup').style.display = 'block';
}

// ฟังก์ชันปิด popup
function closePopup() {
    document.getElementById('menu-popup-overlay').style.display = 'none';
    document.getElementById('menu-popup').style.display = 'none';
}

// ฟังก์ชันเปลี่ยนจำนวนใน popup
function changePopupQty(change) {
    const qtyElement = document.getElementById('popup-qty');
    let currentQty = parseInt(qtyElement.textContent);
    currentQty = Math.max(1, currentQty + change);
    qtyElement.textContent = currentQty;
}

// ฟังก์ชันเพิ่มลงตะกร้าจาก popup
function addToCartFromPopup() {
    const name = document.getElementById('popup-name').textContent;
    const price = parseFloat(document.getElementById('popup-price').textContent.replace('', 'บาท'));
    const quantity = parseInt(document.getElementById('popup-qty').textContent);
    const description = document.getElementById('popup-description').value;
    
    const cart = JSON.parse(localStorage.getItem('cart') || '{}');
    const itemId = name + (description ? '_' + description : '');
    
    if (cart[itemId]) {
        cart[itemId].quantity += quantity;
    } else {
        cart[itemId] = {
            id: itemId,
            name: name,
            price: price,
            quantity: quantity,
            description: description
        };
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartDisplay();
    closePopup();
    
    // แสดงข้อความยืนยัน
    showAlert('✅ เพิ่มลงตะกร้าเรียบร้อยแล้ว');
}

// ฟังก์ชันล้างตะกร้า
function clearCart() {
    showConfirmAlert('คุณต้องการล้างออเดอร์ทั้งหมดหรือไม่?', function() {
        localStorage.removeItem('cart');
        updateCartDisplay();
        document.getElementById('customer-name-input').value = '';
        document.getElementById('table-number-input').value = '';
    });
}

// อัพเดทการแสดงผลตะกร้า
function updateCartDisplay() {
    console.log('--- Running updateCartDisplay ---'); // สำหรับ Debug
    
    const cartItemsContainer = document.getElementById('cart-items');
    const cartSummary = document.getElementById('cart-summary');
    const totalElement = document.getElementById('total');
    
    // ตรวจสอบว่า Element ที่จำเป็นทั้งหมดมีอยู่จริงหรือไม่
    if (!cartItemsContainer || !cartSummary || !totalElement) {
        console.error('Error: A required cart element is missing from the page.');
        return; // หยุดการทำงานถ้าหา Element ไม่เจอ
    }

    const cart = JSON.parse(localStorage.getItem('cart') || '{}');
    console.log('Cart data from localStorage:', cart); // สำหรับ Debug: แสดงข้อมูลดิบจากตะกร้า

    const items = Object.values(cart).filter(item => item && typeof item.quantity === 'number' && item.quantity > 0);
    console.log('Items to display:', items); // สำหรับ Debug: แสดงรายการสินค้าที่จะวาดลงหน้าจอ

    if (items.length === 0) {
        cartItemsContainer.innerHTML = `
            <div class="empty-cart">
                <div class="empty-cart-icon">🛍️</div>
                <p>ยังไม่มีรายการสั่งอาหาร</p>
            </div>
        `;
        cartSummary.style.display = 'none';
        totalElement.textContent = '0';
        return;
    }
    
    let total = 0;
    const itemsHtml = items.map(item => {
        // ตรวจสอบข้อมูลก่อนแสดงผล
        const price = parseFloat(item.price) || 0;
        const quantity = parseInt(item.quantity) || 0;
        const name = item.name || 'Unknown Item';
        const id = item.id;

        const itemTotal = price * quantity;
        total += itemTotal;
        
        // ใช้ backticks (`) ครอบ HTML ทั้งหมด
        return `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${name}</div>
                    <div class="cart-item-price">
                        ${price.toLocaleString()} บาท × ${quantity} = ${itemTotal.toLocaleString()} บาท
                    </div>
                </div>
                <div class="quantity-controls">
                    <button class="quantity-btn" onclick="changeQuantity('${id}', -1)">-</button>
                    <span class="quantity">${quantity}</span>
                    <button class="quantity-btn" onclick="changeQuantity('${id}', 1)">+</button>
                </div>
            </div>
        `;
    }).join('');
    
    // อัพเดท DOM
    cartItemsContainer.innerHTML = itemsHtml;
    totalElement.textContent = total.toLocaleString();
    cartSummary.style.display = 'block';

    console.log('Display updated successfully. Total:', total);
}
// เปลี่ยนจำนวนรายการ
function changeQuantity(itemId, change) {
    const cart = JSON.parse(localStorage.getItem('cart') || '{}');
    if (cart[itemId]) {
        cart[itemId].quantity += change;
        if (cart[itemId].quantity <= 0) {
            delete cart[itemId];
        }
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartDisplay();
    }
}

// ฟังก์ชันแสดง alert
function showAlert(message) {
    const alertOverlay = document.getElementById('alert-popup-overlay');
    const alertTitle = document.getElementById('alert-title');
    const alertMessage = document.getElementById('alert-message');
    const confirmBtn = document.getElementById('alert-confirm-btn');
    const cancelBtn = document.getElementById('alert-cancel-btn');

    // ตรวจสอบว่าองค์ประกอบที่จำเป็นสำหรับ popup มีครบหรือไม่
    if (alertOverlay && alertTitle && alertMessage && confirmBtn && cancelBtn) {
        alertTitle.textContent = 'แจ้งเตือน'; // ตั้งชื่อหัวข้อ
        alertMessage.textContent = message;    // ใส่ข้อความ
        alertOverlay.style.display = 'flex';   // แสดง popup

        // *** ส่วนสำคัญที่แก้ไข ***
        // ปรับปุ่มให้เป็นแบบ "ตกลง" ปุ่มเดียว
        confirmBtn.textContent = 'ตกลง';      // เปลี่ยนข้อความปุ่มยืนยันเป็น "ตกลง"
        confirmBtn.style.display = 'inline-block'; // ตรวจสอบให้แน่ใจว่าปุ่มนี้แสดงอยู่
        cancelBtn.style.display = 'none';      // ซ่อนปุ่มยกเลิก

        // กำหนดการทำงานเมื่อกดปุ่ม "ตกลง"
        // ลบ event listener เก่าออกก่อน (สำคัญมาก) เพื่อป้องกันการเรียกซ้ำ
        confirmBtn.onclick = null;
        confirmBtn.onclick = function() {
            alertOverlay.style.display = 'none'; // ซ่อน popup
            // คืนค่าปุ่มยกเลิกให้แสดงผลเหมือนเดิม เผื่อฟังก์ชันอื่นเรียกใช้ต่อ
            cancelBtn.style.display = 'inline-block';
        };

        // ไม่ต้องมีปุ่ม cancel
        cancelBtn.onclick = null;

    } else {
        // Fallback ในกรณีที่หา HTML ไม่เจอ
        alert(message);
    }
}

function showTableAlert(message) {
    const alertOverlay = document.getElementById('alert-popup-overlay');
    const alertTitle = document.getElementById('alert-title');
    const alertMessage = document.getElementById('alert-message');
    const confirmBtn = document.getElementById('alert-confirm-btn');
    const cancelBtn = document.getElementById('alert-cancel-btn');

    if (alertOverlay && alertTitle && alertMessage && confirmBtn && cancelBtn) {
        alertTitle.textContent = 'แจ้งเตือน';
        alertMessage.textContent = message;
        alertOverlay.style.display = 'flex';

        // ปรับปุ่ม
        confirmBtn.textContent = 'ตกลง';
        cancelBtn.style.display = 'none'; // ซ่อนปุ่มยกเลิก

        // ลบ event listener เดิม
        confirmBtn.onclick = null;

        // เมื่อกดตกลง ให้ปิด popup
        confirmBtn.onclick = function() {
            alertOverlay.style.display = 'none';
            cancelBtn.style.display = 'inline-block'; // คืนค่าให้ cancel ปุ่มในครั้งถัดไป
        };
    } else {
        alert(message); // fallback
    }
}

// ฟังก์ชันแสดง confirm alert

function showConfirmAlert(message, onConfirm, onCancel) {
    const alertOverlay = document.getElementById('alert-popup-overlay');
    const alertTitle = document.getElementById('alert-title');
    const alertMessage = document.getElementById('alert-message');
    const confirmBtn = document.getElementById('alert-confirm-btn');
    const cancelBtn = document.getElementById('alert-cancel-btn');

    // ตรวจสอบว่าองค์ประกอบทั้งหมดมีอยู่จริงหรือไม่
    if (alertOverlay && alertTitle && alertMessage && confirmBtn && cancelBtn) {
        alertTitle.textContent = 'ยืนยันการดำเนินการ';
        alertMessage.textContent = message;
        alertOverlay.style.display = 'flex';

        // สร้างฟังก์ชันสำหรับซ่อน Popup เพื่อไม่ให้โค้ดซ้ำซ้อน
        const hideAlert = () => {
            alertOverlay.style.display = 'none';
        };

        // กำหนดการทำงานของปุ่ม "ยืนยัน"
        confirmBtn.onclick = function() {
            hideAlert();
            if (onConfirm) {
                onConfirm();
            }
        };

        // กำหนดการทำงานของปุ่ม "ยกเลิก"
        cancelBtn.onclick = function() {
            hideAlert();
            if (onCancel) {
                onCancel();
            }
        };

    } else {
        // ใช้ confirm ของเบราว์เซอร์เป็นตัวสำรอง
        if (confirm(message)) {
            if (onConfirm) onConfirm();
        } else {
            if (onCancel) onCancel();
        }
    }
}

// ฟังก์ชันส่งออเดอร์
function submitOrder() {
    const cart = JSON.parse(localStorage.getItem('cart') || '{}');
    const items = Object.values(cart).filter(item => item.quantity > 0);
    
    if (items.length === 0) {
        showAlert('กรุณาเลือกเมนูก่อนส่งออเดอร์');
        return;
    }
    
    const serviceType = document.querySelector('input[name="service-type"]:checked').value;
    const customerName = document.getElementById('customer-name-input').value.trim();
    
    // ** แก้ไข: ดึงค่าจาก hidden input แทน **
    const tableNumber = document.getElementById('table-number-input').value.trim();
    
    const orderData = {
        customer_name: serviceType === 'dine-in' ? null : customerName,
        table_num: serviceType === 'dine-in' ? tableNumber : null,
        service_type: serviceType,
        items: items
    };
    
    fetch('proc_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('✅ ส่งออเดอร์เรียบร้อยแล้ว');
            localStorage.removeItem('cart');
            updateCartDisplay();
            // รีเซ็ตฟอร์ม
            document.getElementById('customer-name-input').value = '';
            document.getElementById('table-number-input').value = '';
            document.getElementById('selected-table-display').textContent = 'ยังไม่ได้เลือก';
            document.querySelector('input[name="service-type"][value="dine-in"]').checked = true;
            document.getElementById('dine-in-fields').style.display = 'block';
            document.getElementById('takeaway-fields').style.display = 'none';
        } else {
            showAlert('❌ เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('❌ เกิดข้อผิดพลาดในการส่งออเดอร์');
    });
}

function showOrderSummaryPopup() {
    const cart = JSON.parse(localStorage.getItem('cart') || '{}');
    const items = Object.values(cart).filter(item => item.quantity > 0);
    if (items.length === 0) {
        showAlert('กรุณาเลือกเมนู');
        return;
    }

    const serviceType = document.querySelector('input[name="service-type"]:checked').value;
    const tableNumber = document.getElementById('table-number-input').value.trim();
    const customerName = document.getElementById('customer-name-input').value.trim();

    let total = items.reduce((sum, item) => sum + item.price * item.quantity, 0);

    let summaryHtml = `
        <div style="text-align: left; font-size: 0.95rem;">
            <p><strong>ประเภท:</strong> ${serviceType === 'dine-in' ? 'ทานที่ร้าน' : 'กลับบ้าน'}</p>
            ${serviceType === 'dine-in' ? `<p><strong>โต๊ะ:</strong> ${tableNumber}</p>` : `<p><strong>ชื่อลูกค้า:</strong> ${customerName}</p>`}
            <hr style="margin: 10px 0;">
            ${items.map(item => `
                <div>
                    <span>${item.name} (x${item.quantity})</span>
                    <span style="float: right;">${(item.price * item.quantity).toLocaleString()} บาท</span>
                </div>
            `).join('')}
            <hr style="margin: 10px 0;">
            <div style="font-size: 1.2rem; font-weight: 600;">
                <span>ยอดรวม</span>
                <span style="float: right;">${total.toLocaleString()} บาท</span>
            </div>
        </div>
    `;

    document.getElementById('order-summary-content').innerHTML = summaryHtml;
    const modal = document.getElementById('order-summary-modal');
    modal.style.display = 'flex';

    const confirmBtn = document.getElementById('confirm-order-btn');
    const cancelBtn = document.getElementById('cancel-order-btn');

    confirmBtn.onclick = () => {
        modal.style.display = 'none';
        submitOrder();
    };
    cancelBtn.onclick = () => {
        modal.style.display = 'none';
    };
}

// === โค้ดที่เพิ่มเข้ามาใหม่ หรือมีการแก้ไขสำคัญ ===
document.addEventListener('DOMContentLoaded', function() {
    updateCartDisplay();

    const serviceTypeRadios = document.querySelectorAll('input[name="service-type"]');
    const dineInFields = document.getElementById('dine-in-fields');
    const takeawayFields = document.getElementById('takeaway-fields');
    
    // จัดการการสลับฟอร์ม ทานที่ร้าน/กลับบ้าน
    serviceTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'dine-in') {
                dineInFields.style.display = 'block';
                takeawayFields.style.display = 'none';
            } else {
                dineInFields.style.display = 'none';
                takeawayFields.style.display = 'block';
            }
        });
    });


    // Event Listener สำหรับปุ่มส่งออเดอร์ (ปรับปรุงเงื่อนไขการตรวจสอบ)
    const checkoutBtn = document.getElementById('menu-checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function() {
            const serviceType = document.querySelector('input[name="service-type"]:checked').value;
            const customerName = document.getElementById('customer-name-input').value.trim();
            const tableNumber = document.getElementById('table-number-input').value.trim(); // อ่านจาก hidden input
            
            if (serviceType === 'dine-in' && !tableNumber) {
                showTableAlert('กรุณาเลือกหมายเลขโต๊ะ');
                return;
            }
            if (serviceType === 'takeaway' && !customerName) {
                showTableAlert('กรุณากรอกชื่อผู้รับ');
                document.getElementById('customer-name-input').focus();
                return;
            }

            // ==== สรุปออเดอร์ก่อนส่ง ====
            const cart = JSON.parse(localStorage.getItem('cart') || '{}');
            const items = Object.values(cart).filter(item => item.quantity > 0);
            if (items.length === 0) {
                showTableAlert('กรุณาเลือกเมนูก่อนส่งออเดอร์');
                return;
            }
            const now = new Date();
            const timestamp = now.toLocaleString('th-TH', { hour12: false });
            let subtotal = 0;
            items.forEach(item => {
                subtotal += item.price * item.quantity;
            });
            const tax = Math.round(subtotal * 0.07 * 100) / 100;
            const total = subtotal + tax;
            let orderSummaryHtml = '';
            orderSummaryHtml += `<div class='receipt-slip' style='box-shadow:none; border-radius:0; background:#fff; padding:0rem;'>`;
            orderSummaryHtml += `<div class='receipt-sales-content'>`;
            orderSummaryHtml += `<div class='receipt-big-total' style='margin-left: 105px;margin-top: -2px;margin-bottom: -20px;font-size: xx-large;'>${total.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})} บาท</div>`;
            orderSummaryHtml += `<div class='receipt-total-label' style='margin-right: 204px;font-size: large;font-family: sans-serif;margin-top: -23px;'>รวมทั้งหมด</div>`;
            orderSummaryHtml += `<hr style='border:none; border-top:1px solid #ccc; margin:12px 0;'>`;
            orderSummaryHtml += `<div class='receipt-detail' style='margin-bottom:0;'>`;
            if (serviceType === 'dine-in') {
                orderSummaryHtml += `ลูกค้า: รับในร้าน<br>โต๊ะ: ${tableNumber}<br>`;
            } else {
                orderSummaryHtml += `ลูกค้า: ${customerName}<br>ประเภท: กลับบ้าน<br>`;
            }
            orderSummaryHtml += `</div>`;
            orderSummaryHtml += `<div class='receipt-section-title' style='margin-top:18px;'>${serviceType === 'dine-in' ? 'เสิร์ฟในร้าน' : 'กลับบ้าน'}</div>`;
            orderSummaryHtml += `<div class='receipt-list'>`;
            items.forEach(item => {
                orderSummaryHtml += `<div class='receipt-list-item' style='font-weight:bold;'>
                    <span class='name'>- ${item.name}</span>
                    <span class='sum'>${(item.price * item.quantity).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})} บาท</span>
                </div>`;
                orderSummaryHtml += `<div class='receipt-list-item' style='font-size:0.98rem; color:#888; margin-bottom:0;'>
                    <span></span>
                    <span class='qty'>${item.quantity} x ${item.price.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})} บาท</span>
                    <span></span>
                </div>`;
            });
            orderSummaryHtml += `</div>`;
            orderSummaryHtml += `<hr style='border:none; border-top:1px solid #ccc; margin:12px 0;'>`;
            orderSummaryHtml += `<div class='receipt-list-item' style='font-weight:bold;'><span>ยอดรวม </span><span>${subtotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})} บาท</span></div>`;
            orderSummaryHtml += `<div class='receipt-list-item'><span>ภาษี (7%) </span><span>${tax.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})} บาท</span></div>`;
            orderSummaryHtml += `</div></div>`;
            document.getElementById('order-summary-content').innerHTML = orderSummaryHtml;
            const modal = document.getElementById('order-summary-modal');
            modal.style.display = 'flex';

            // ปุ่มยืนยัน
            const confirmBtn = document.getElementById('confirm-order-btn');
            const cancelBtn = document.getElementById('cancel-order-btn');

            // ลบ event เดิมก่อน (ป้องกันซ้อน)
            confirmBtn.onclick = null;
            cancelBtn.onclick = null;

            confirmBtn.onclick = function() {
                modal.style.display = 'none';
                submitOrder();
            };
            cancelBtn.onclick = function() {
                modal.style.display = 'none';
            };
            showOrderSummaryPopup();
        });
    }

    // เพิ่ม event listener ให้กับ menu-card ทุกตัว
document.querySelectorAll('.menu-card').forEach(card => {
    card.addEventListener('click', function() {
        
        // ✅ เพิ่มส่วนตรวจสอบนี้เข้าไป
        // ถ้าการ์ดที่ถูกคลิกมีคลาส 'disabled' ให้หยุดการทำงานทันที
        if (card.classList.contains('disabled')) {
            return; // ออกจากฟังก์ชัน ไม่ทำอะไรต่อ
        }

        // โค้ดที่เหลือจะทำงานก็ต่อเมื่อเมนูไม่ถูก disable
        const id = card.getAttribute('data-id');
        const name = card.getAttribute('data-name');
        const price = parseFloat(card.getAttribute('data-price'));
        
        // เพิ่มลง cart (quantity +1)
        const cart = JSON.parse(localStorage.getItem('cart') || '{}');
if (cart[id]) {
                cart[id].quantity += 1;
            } else {
                cart[id] = { id, name, price, quantity: 1 };
            }
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartDisplay();
    });
});
    
    // ปิด alert popup เมื่อคลิก overlay
    const alertOverlay = document.getElementById('alert-popup-overlay');
    if (alertOverlay) {
        alertOverlay.addEventListener('click', function(e) {
            if (e.target === this) {
                alertOverlay.style.display = 'none';
            }
        });
    }
    
    // ปิด menu popup เมื่อคลิก overlay
    const menuPopupOverlay = document.getElementById('menu-popup-overlay');
    if (menuPopupOverlay) {
        menuPopupOverlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closePopup();
            }
        });
    }
});

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


    // --- จัดการฟอร์มเลือกประเภทการสั่ง ---
    const serviceTypeRadios = document.querySelectorAll('input[name="service-type"]');
    const dineInFields = document.getElementById('dine-in-fields');
    const takeawayFields = document.getElementById('takeaway-fields');
    
    serviceTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'dine-in') {
                dineInFields.style.display = 'block';
                takeawayFields.style.display = 'none';
            } else {
                dineInFields.style.display = 'none';
                takeawayFields.style.display = 'block';
            }
        });
    });

    // --- จัดการ Modal เลือกโต๊ะ ---
    const selectTableBtn = document.getElementById('select-table-btn');
    const tableModal = document.getElementById('table-select-modal');
    const closeTableModalBtn = document.getElementById('close-table-modal-btn');
    const tableGrid = document.getElementById('table-grid');
    const selectedTableDisplay = document.getElementById('selected-table-display');
    const tableNumberInput = document.getElementById('table-number-input');

    const numberOfTables = 10;
    if (tableGrid) { // ตรวจสอบก่อนว่า element มีจริง
        for (let i = 1; i <= numberOfTables; i++) {
            const tableBtn = document.createElement('button');
            tableBtn.className = 'table-btn';
            tableBtn.textContent = `${i}`;
            tableBtn.dataset.tableNumber = `${i}`;
            tableGrid.appendChild(tableBtn);
            tableBtn.addEventListener('click', function() {
                const selectedTable = this.dataset.tableNumber;
                tableNumberInput.value = selectedTable;
                selectedTableDisplay.textContent = selectedTable;
                document.querySelectorAll('.table-btn').forEach(btn => btn.classList.remove('selected'));
                this.classList.add('selected');
                tableModal.style.display = 'none';
            });
        }
    }
    
    if (selectTableBtn) selectTableBtn.addEventListener('click', () => { tableModal.style.display = 'flex'; });
    if (closeTableModalBtn) closeTableModalBtn.addEventListener('click', () => { tableModal.style.display = 'none'; });
    if (tableModal) tableModal.addEventListener('click', (e) => { if (e.target === tableModal) tableModal.style.display = 'none'; });
});
