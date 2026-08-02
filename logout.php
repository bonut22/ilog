<?php
// logout.php
session_start();
session_destroy(); // เคลียร์ค่าทิ้งทั้งหมดเพื่อความปลอดภัย
header("Location: login.php"); // ส่งกลับไปหน้าล็อกอินเริ่มต้น
exit();
?>