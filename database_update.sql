-- เพิ่มคอลัมน์ total_amount ในตาราง order1
ALTER TABLE order1 ADD COLUMN total_amount DECIMAL(10,2) DEFAULT 0.00 AFTER customer_name;

-- อัปเดตข้อมูลที่มีอยู่แล้วโดยคำนวณจาก order_items
UPDATE order1 o 
SET total_amount = (
    SELECT COALESCE(SUM(oi.quantity * m.menu_price), 0)
    FROM order_items oi
    JOIN menu m ON oi.menu_id = m.menu_ID
    WHERE oi.order_id = o.order_id
); 