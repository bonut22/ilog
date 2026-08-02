<?php
// config/db_connect.php

// 1. ตั้งค่าพารามิเตอร์สำหรับเชื่อมต่อฐานข้อมูลของ AppServ
$servername = "localhost";
$username   = "root";      // ค่าเริ่มต้นของ AppServ
$password   = "12345678";  // ใส่รหัสผ่าน MySQL ตอนที่คุณติดตั้ง AppServ (ถ้าไม่มีให้ปล่อยว่าง "")
$dbname     = "ilog_db";   // ชื่อฐานข้อมูลที่เราจะไปสร้างใน phpMyAdmin

// 2. สร้างการเชื่อมต่อด้วยฟังก์ชัน mysqli_connect
$conn = mysqli_connect($servername, $username, $password, $dbname);

// 3. ตรวจสอบการเชื่อมต่อว่าสำเร็จหรือไม่
if (!$conn) {
    // ถ้าเชื่อมต่อล้มเหลว ให้แสดงข้อความ Error และหยุดการทำงานทันที
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . mysqli_connect_error());
}

// 4. ตั้งค่าให้ระบบรองรับภาษาไทย (UTF-8) เพื่อป้องกันปัญหาตัวอักษรกลายเป็นเครื่องหมายคำถาม (???)
mysqli_set_charset($conn, "utf8");

// หมายเหตุ: เราจะไม่ใส่ แท็กปิด ปิดท้ายไฟล์ เพื่อป้องกันปัญหาช่องว่างอักขระ (Whitespace) หลุดไปในระบบ Session ค่ะ