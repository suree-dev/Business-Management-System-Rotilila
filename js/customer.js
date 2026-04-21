document.addEventListener('DOMContentLoaded', () => {

    // --- State & Constants (MODIFIED for Sub-Orders) ---
    const tableNumber = document.getElementById('table-name')?.textContent.replace('โต๊ะ ', '').trim() || 'ไม่ระบุ';
    const cartKey = `cart_table_${tableNumber}`;
    // Key สำหรับเก็บ "อาร์เรย์" ของ Order IDs ที่ผู้ใช้คนนี้ส่ง
    const myOrderIdsKey = `my_order_ids_table_${tableNumber}`;

    let cart = JSON.parse(localStorage.getItem(cartKey)) || {};
    // ดึงรายการออเดอร์ของ user คนนี้มาเป็น Array
    let myOrderIds = JSON.parse(localStorage.getItem(myOrderIdsKey)) || [];
    let statusInterval = null;

    // --- DOM Elements ---
    const menuView = document.getElementById('menu-view');
    const cartView = document.getElementById('cart-view');
    const openCartBtn = document.getElementById('open-cart-btn');
    const closeCartBtn = document.getElementById('header-back-btn');
    const cartBottomBar = document.getElementById('cart-bottom-bar');
    const headerContent = document.getElementById('header-content');
    const headerTitle = document.getElementById('header-title');
    const confirmOrderBtn = document.getElementById('confirm-order-btn');
    // const cancelOrderBtn = document.getElementById('cancel-order-btn'); // การยกเลิกซับซ้อนขึ้น อาจต้องเอาออกไปก่อน
    const searchIcon = document.getElementById('search-icon');
    const searchContainer = document.getElementById('search-bar-container');
    const searchInput = document.getElementById('searchInput');
    const closeSearchBtn = document.getElementById('close-search-btn');
    // **Element ใหม่สำหรับแสดงสถานะออเดอร์ย่อย**
    const myOrdersStatusContainer = document.getElementById('my-orders-status-list');


    // --- View Switching ---
    const showCartView = () => {
        menuView.style.display = 'none';
        cartView.style.display = 'flex';
        cartBottomBar.style.display = 'none';
        headerContent.style.display = 'none';
        searchContainer.style.display = 'none';
        headerTitle.style.display = 'block';
        closeCartBtn.style.display = 'block';
        updateOrderStatus(); // อัปเดตสถานะทุกครั้งที่เปิดหน้านี้
    };

    const showMenuView = () => {
        menuView.style.display = 'block';
        cartView.style.display = 'none';
        updateCartUI();
        headerContent.style.display = 'flex';
        headerTitle.style.display = 'none';
        closeCartBtn.style.display = 'none';
    };

    // --- Event Listeners ---
    openCartBtn.addEventListener('click', () => { if (Object.keys(cart).length > 0 || myOrderIds.length > 0) showCartView(); });
    closeCartBtn.addEventListener('click', showMenuView);
    cartBottomBar.addEventListener('click', showCartView);
    confirmOrderBtn.addEventListener('click', confirmOrder);
    // cancelOrderBtn.addEventListener('click', cancelOrder);
    searchIcon.addEventListener('click', () => {
        headerContent.style.display = 'none';
        searchContainer.style.display = 'flex';
        searchInput.focus();
    });
    closeSearchBtn.addEventListener('click', () => {
        headerContent.style.display = 'flex';
        searchContainer.style.display = 'none';
        searchInput.value = '';
        filterMenu();
    });
    searchInput.addEventListener('input', filterMenu);

    // --- Search Function ---
    function filterMenu() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const menuItems = document.querySelectorAll('.menu-item');
        const categories = document.querySelectorAll('.menu-category-section');
        menuItems.forEach(item => {
            const itemName = item.querySelector('.menu-item-name').textContent.toLowerCase();
            item.style.display = itemName.includes(searchTerm) ? 'flex' : 'none';
        });
        categories.forEach(cat => {
            const visibleItems = cat.querySelectorAll('.menu-item[style*="display: flex"]');
            cat.style.display = visibleItems.length === 0 ? 'none' : 'block';
        });
    }

    // --- Order Logic ---
    function confirmOrder() {
        if (Object.keys(cart).length === 0) {
            Swal.fire({ icon: 'warning', title: 'ตะกร้าว่าง', text: 'กรุณาเลือกรายการอาหารก่อน' });
            return;
        }
        Swal.fire({
            title: 'ยืนยันการสั่งอาหาร?',
            text: "รายการอาหารจะถูกส่งไปที่ห้องครัว",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                sendOrderToServer();
            }
        });
    }

    function sendOrderToServer() {
        console.log('ค่า tableNumber ที่กำลังจะส่งไปให้ PHP คือ:', tableNumber, 'ประเภท:', typeof tableNumber);
        Swal.fire({ title: 'กำลังส่งรายการ...', text: 'กรุณารอสักครู่', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        const orderData = {
            table_number: tableNumber,
            items: Object.values(cart)
        };
        fetch('../sale/submit_customer_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.order_id) {
                Swal.close();
                // -- MODIFIED: เพิ่ม order_id ใหม่ลงในอาร์เรย์ของฉัน --
                myOrderIds.push(data.order_id);
                localStorage.setItem(myOrderIdsKey, JSON.stringify(myOrderIds));

                // ล้างตะกร้าหลังส่งสำเร็จ
                cart = {};
                localStorage.removeItem(cartKey);
                updateCartUI(); // อัปเดต UI ตะกร้าให้ว่าง
                updateOrderStatus(); // อัปเดตรายการสถานะทันที
                startStatusCheck();
            } else {
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message || 'ไม่สามารถส่งออเดอร์ได้' });
            }
        })
        .catch(error => { console.error('Submit Order Error:', error); Swal.fire({ icon: 'error', title: 'การเชื่อมต่อผิดพลาด', text: 'ไม่สามารถส่งรายการอาหารได้' }); });
    }

    // --- Status Update Logic (MAJOR MODIFICATION) ---
    function startStatusCheck() {
        if (statusInterval) clearInterval(statusInterval);
        // ทำงานเมื่อมีออเดอร์ที่ต้องติดตามสถานะ
        if (myOrderIds.length > 0) {
            statusInterval = setInterval(updateOrderStatus, 5000); // เช็คทุก 5 วินาที
        }
    }

    function updateOrderStatus() {
        const confirmBtn = confirmOrderBtn;

        // ถ้าไม่มีออเดอร์ของฉัน และตะกร้าว่าง -> กลับสู่สถานะเริ่มต้น
        if (myOrderIds.length === 0 && Object.keys(cart).length === 0) {
            clearInterval(statusInterval);
            if (myOrdersStatusContainer) myOrdersStatusContainer.innerHTML = ''; // ล้างสถานะเก่า
            // ปุ่มยืนยันจะกดไม่ได้เมื่อไม่มีของในตะกร้า
            confirmBtn.disabled = true;
            return;
        }

        // ปุ่มยืนยันจะกดได้เมื่อมีของในตะกร้าเท่านั้น
        confirmBtn.disabled = Object.keys(cart).length === 0;

        if (myOrderIds.length === 0) {
            if (myOrdersStatusContainer) myOrdersStatusContainer.innerHTML = '';
            return;
        }

        // ดึงสถานะของทุกออเดอร์พร้อมกัน
        const statusPromises = myOrderIds.map(id =>
            fetch(`../sale/get_status.php?order_id=${id}`).then(res => res.json())
        );

        Promise.all(statusPromises)
            .then(results => {
                if(myOrdersStatusContainer) {
                    myOrdersStatusContainer.innerHTML = '<h4>รายการที่สั่งไปแล้ว:</h4>';
                }
                
                let stillActiveOrders = [];

                results.forEach((data, index) => {
                    const orderId = myOrderIds[index];
                    let statusText = `ออเดอร์ #${orderId}: ไม่ทราบสถานะ`;
                    let isOrderActive = false;

                    if (data.success) {
                        const status = data.status;
                        // ออเดอร์ที่ยังไม่เสร็จสิ้น (ยังไม่ done, paid หรือ cancelled) ถือว่า active
                        if (status !== 'paid' && status !== 'cancelled' && status !== 'done') {
                            stillActiveOrders.push(orderId);
                            isOrderActive = true;
                        }

                        switch (status) {
                            case 'pending': statusText = `ออเดอร์ #${orderId}: <span class="status-pending">รอยืนยัน...</span>`; break;
                            case 'processing': statusText = `ออเดอร์ #${orderId}: <span class="status-processing">กำลังปรุง...</span>`; break;
                            case 'done': statusText = `ออเดอร์ #${orderId}: <span class="status-done">พร้อมเสิร์ฟแล้ว</span>`; break;
                            case 'paid': statusText = `ออเดอร์ #${orderId}: <span class="status-paid">ชำระเงินแล้ว</span>`; break;
                            case 'cancelled': statusText = `ออเดอร์ #${orderId}: <span class="status-cancelled">ยกเลิกแล้ว</span>`; break;
                        }
                    }
                    if(myOrdersStatusContainer) {
                        myOrdersStatusContainer.innerHTML += `<div class="order-status-item">${statusText}</div>`;
                    }
                });

                // อัปเดตรายการออเดอร์ให้เหลือเฉพาะที่ยัง active อยู่
                // เพื่อป้องกันไม่ให้ localStorage บวม และไม่ต้องเช็คสถานะออเดอร์ที่จบไปแล้วซ้ำๆ
                if (JSON.stringify(myOrderIds) !== JSON.stringify(stillActiveOrders)) {
                    myOrderIds = stillActiveOrders;
                    localStorage.setItem(myOrderIdsKey, JSON.stringify(myOrderIds));
                }

                if (myOrderIds.length === 0) {
                    clearInterval(statusInterval);
                }
            })
            .catch(err => console.error("Error fetching statuses:", err));
    }

    // --- Cart Logic ---
    window.addItemToCart = (element, id, name, price, image) => {
        // สามารถเพิ่มของลงตะกร้าได้ตลอดเวลา
        if (cart[id]) {
            cart[id].quantity++;
        } else {
            cart[id] = { id, name: name, price: parseFloat(price), quantity: 1, image: image };
        }
        updateCartUI();
    };

    window.changeCartQuantity = (id, change) => {
        if (cart[id]) {
            cart[id].quantity += change;
            if (cart[id].quantity <= 0) {
                delete cart[id];
            }
        }
        updateCartUI();
    };

    window.deleteCartItem = (id) => {
        Swal.fire({
            title: 'ลบรายการนี้?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                delete cart[id];
                updateCartUI();
                if (Object.keys(cart).length === 0 && myOrderIds.length === 0) {
                    showMenuView();
                }
            }
        });
    };

    const updateCartUI = () => {
        if (Object.keys(cart).length > 0) {
            localStorage.setItem(cartKey, JSON.stringify(cart));
        } else {
            localStorage.removeItem(cartKey);
        }
        const totalItems = Object.values(cart).reduce((sum, item) => sum + item.quantity, 0);
        const totalPrice = Object.values(cart).reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const cartIconIndicator = document.getElementById('cart-count-indicator');
        cartIconIndicator.textContent = totalItems;
        cartIconIndicator.style.display = totalItems > 0 ? 'flex' : 'none';
        
        if (totalItems > 0 && menuView.style.display !== 'none') {
            cartBottomBar.style.display = 'flex';
            document.getElementById('bottom-bar-item-count').textContent = `${totalItems} รายการ`;
            document.getElementById('bottom-bar-total').textContent = `${totalPrice.toFixed(2)} บาท`;
        } else {
            cartBottomBar.style.display = 'none';
        }

        document.querySelectorAll('.menu-item .quantity-in-cart').forEach(el => el.style.display = 'none');
        for (const id in cart) {
            const menuItem = document.querySelector(`.menu-item[data-id='${id}']`);
            if (menuItem) {
                const indicator = menuItem.querySelector('.quantity-in-cart');
                indicator.textContent = cart[id].quantity;
                indicator.style.display = 'flex';
            }
        }
        renderCartPage();
        // อัปเดตสถานะปุ่มยืนยันทุกครั้งที่ตะกร้าเปลี่ยน
        confirmOrderBtn.disabled = totalItems === 0;
    };

    function renderCartPage() {
        const cartItemsList = document.getElementById('cart-items-list');
        const cartPageTotal = document.getElementById('cart-page-total');
        if (!cartItemsList || !cartPageTotal) return;
        cartItemsList.innerHTML = '';

        // ปุ่มในตะกร้าจะไม่ถูกล็อคแล้ว เพราะตะกร้าเป็นของใหม่เสมอ
        const isCartLocked = false; 

        if (Object.keys(cart).length === 0) {
            cartItemsList.innerHTML = '<div class="empty-cart-message"><h3>ไม่มีสินค้าในตะกร้า</h3><p>เลือกเมนูที่ต้องการได้เลย!</p></div>';
            document.querySelector('.cart-page-footer').style.display = 'none';
            // ถ้าไม่มีออเดอร์ค้างอยู่ด้วย ให้กลับไปหน้าเมนู
            if (cartView.style.display !== 'none' && myOrderIds.length === 0) {
                setTimeout(() => showMenuView(), 1500);
            }
            cartPageTotal.textContent = '0.00 บาท';
            return;
        }

        document.querySelector('.cart-page-footer').style.display = 'block';
        let totalPrice = 0;
        for (const id in cart) {
            const item = cart[id];
            totalPrice += item.price * item.quantity;
            const itemElement = document.createElement('div');
            itemElement.className = 'cart-page-item';
            itemElement.innerHTML = `
                <img src="${item.image}" alt="${item.name}" class="cart-item-image">
                <div class="cart-item-details">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">${item.price.toFixed(2)} บาท</div>
                </div>
                <div class="cart-item-actions">
                    <button class="qty-btn" ${isCartLocked ? 'disabled' : ''} onclick="changeCartQuantity('${id}', -1)">-</button>
                    <span class="qty-text">${item.quantity}</span>
                    <button class="qty-btn" ${isCartLocked ? 'disabled' : ''} onclick="changeCartQuantity('${id}', 1)">+</button>
                </div>
                <button class="cart-item-delete" ${isCartLocked ? 'disabled' : ''} onclick="deleteCartItem('${id}')">
                    <i class="bi bi-trash3-fill"></i>
                </button>
            `;
            cartItemsList.appendChild(itemElement);
        }
        cartPageTotal.textContent = `${totalPrice.toFixed(2)} บาท`;
    }

    // --- Scrollspy for Category Nav ---
    const navLinks = document.querySelectorAll('.menu-categories a');
    const sections = document.querySelectorAll('.menu-category-section');
    const scrollSpyHandler = () => {
        let current = '';
        sections.forEach(section => {
            if (section.style.display !== 'none') {
                const sectionTop = section.offsetTop;
                if (window.pageYOffset >= sectionTop - 150) {
                    current = section.getAttribute('id');
                }
            }
        });
        navLinks.forEach(a => {
            a.classList.remove('active');
            if (a.getAttribute('href').includes(current)) {
                a.classList.add('active');
            }
        });
    };
    window.onscroll = scrollSpyHandler;

    // --- Initial Load ---
    updateCartUI();
    updateOrderStatus();
    startStatusCheck();
});