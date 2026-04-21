<?php
session_start();
session_destroy();
header("Location: login.php"); // หรือเปลี่ยนเป็นหน้า login ของหนู
exit();
?>
