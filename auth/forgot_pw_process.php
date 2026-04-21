<?php
// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require '../vendor/autoload.php'; // **แก้ไข path ตามโครงสร้างโปรเจกต์ของคุณ**

// --- การเชื่อมต่อฐานข้อมูล (DB Connection) ---
// **กรุณาแก้ไขส่วนนี้ให้เป็นการเชื่อมต่อ DB ของคุณ**
$servername = "106765033.student.yru.ac.th";
$username = "S106765033_db";
$password = "1959900814134";
$dbname = "S106765033_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// --- จบส่วนการเชื่อมต่อฐานข้อมูล ---

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // 1. ตรวจสอบว่ามีอีเมลนี้ในระบบหรือไม่
    $stmt = $conn->prepare("SELECT user_ID FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['user_ID'];

        // 2. สร้างรหัสผ่านชั่วคราวแบบสุ่ม
        $temp_password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
        
        // 3. Hash รหัสผ่านชั่วคราวก่อนเก็บลง DB
        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
        
        // 4. อัปเดตฐานข้อมูลด้วยรหัสผ่านใหม่ และตั้งค่า force_password_reset = 1
        $update_stmt = $conn->prepare("UPDATE users SET password_hash = ?, force_password_reset = 1 WHERE user_ID = ?");
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($update_stmt->execute()) {
            // 5. ส่งอีเมลพร้อมรหัสผ่านชั่วคราว
            $mail = new PHPMailer(true);

            try {
                // --- การตั้งค่า Server (SMTP) ---
                // **กรุณาแก้ไขให้เป็นข้อมูล SMTP ของคุณ (เช่น Gmail, SendGrid)**
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; // เช่น smtp.gmail.com
                $mail->SMTPAuth   = true;
                $mail->Username   = 'ateez81seonghwa@gmail.com';
                $mail->Password   = 'zcdbpdboggnqwruw';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';

                //ผู้รับ
                $mail->setFrom('no-reply@rotileela.com', 'ระบบจัดการร้านโรตีลีลา');
                $mail->addAddress($email);

                //เนื้อหา
                $mail->isHTML(true);
                $mail->Subject = 'รหัสผ่านใหม่สำหรับระบบจัดการร้านโรตีลีลา';
                $mail->Body    = "สวัสดีครับ,<br><br>คุณได้ทำการขอรหัสผ่านใหม่ รหัสผ่านชั่วคราวของคุณคือ: <b>" . $temp_password . "</b><br><br>กรุณาใช้รหัสผ่านนี้เพื่อเข้าสู่ระบบ และระบบจะบังคับให้คุณตั้งรหัสผ่านใหม่ทันที<br><br>ขอแสดงความนับถือ,<br>ทีมงานโรตีลีลา";
                $mail->AltBody = 'รหัสผ่านชั่วคราวของคุณคือ: ' . $temp_password;

                $mail->send();
                header("Location: ../auth/login.php?success=1");
                exit();

            } catch (Exception $e) {
                header("Location: ../forgot_pw.php?error=ไม่สามารถส่งอีเมลได้. Mailer Error: {$mail->ErrorInfo}");
                exit();
            }
        }
    } else {
        // แม้ไม่เจออีเมล ก็ให้แสดงผลเหมือนส่งสำเร็จ เพื่อความปลอดภัย (ป้องกันการเดาอีเมล)
        header("Location: ../forgot_pw.php?success=1");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>