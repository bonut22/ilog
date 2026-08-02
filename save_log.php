<?php
// save_log.php
session_start();
include('config/db_connect.php'); // เชื่อมต่อฐานข้อมูล

// ตรวจสอบความปลอดภัย: ต้องเป็นนักศึกษาที่ล็อกอินอยู่เท่านั้น
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. รับค่าจากฟอร์มและป้องกัน SQL Injection
    $student_id  = $_SESSION['student_id'];
    $log_date    = mysqli_real_escape_string($conn, $_POST['log_date']);
    $time_in     = mysqli_real_escape_string($conn, $_POST['time_in']);
    $time_out    = mysqli_real_escape_string($conn, $_POST['time_out']);
    $work_detail = mysqli_real_escape_string($conn, $_POST['work_detail']);
    
    // สมมติค่าสัปดาห์ที่ฝึกงาน (สัปดาห์ที่ 1) สำหรับเวอร์ชันเริ่มต้น 
    // (ในอนาคตสามารถเขียนโค้ดคำนวณอัตโนมัติจากวันที่ได้ค่ะ)
    $week_number = 1; 

    $filename_db = null; // ตั้งค่าเริ่มต้นของชื่อไฟล์ในฐานข้อมูลเป็นค่าว่าง

    // 2. ระบบอัปโหลดรูปภาพหลักฐาน (File Upload Handling)
    if (isset($_FILES['log_image']) && $_FILES['log_image']['error'] == 0) {
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        // ดึงนามสกุลไฟล์จริงของรูปภาพออกมา เช่น .jpg หรือ .png
        $file_extension = strtolower(pathinfo($_FILES['log_image']['name'], PATHINFO_EXTENSION));

        // ตรวจสอบว่านามสกุลไฟล์ตรงตามที่อนุญาตไหม
        if (in_array($file_extension, $allowed_extensions)) {
            
            // ตั้งชื่อไฟล์ใหม่เพื่อไม่ให้ซ้ำกันในระบบ: STD_รหัสนักศึกษา_ตามด้วยเวลาปัจจุบัน.นามสกุล
            $new_filename = "STD_" . $student_id . "_" . time() . "." . $file_extension;
            
            // กำหนดพาธปลายทางที่จะเอาไฟล์รูปไปหย่อนไว้
            $upload_target = "assets/images/uploads/" . $new_filename;

            // ย้ายไฟล์จากโฟลเดอร์ชั่วคราวของเซิร์ฟเวอร์ เข้าสู่โฟลเดอร์โปรเจกต์จริง
            if (move_uploaded_file($_FILES['log_image']['tmp_name'], $upload_target)) {
                $filename_db = $new_filename; // บันทึกชื่อไฟล์ใหม่เตรียมเอาไปลง Database

            // 🔥 เติมบรรทัดนี้เพิ่มเข้าไปค่ะ: สั่งให้ PHP ปลดล็อกสิทธิ์ไฟล์นี้เป็น 0777 (Everyone) ทันที
                chmod($upload_target, 0777);
            }
        }
    }

    // 3. คำสั่ง SQL ยิงข้อมูลเข้าตาราง tb_daily_logs ที่เราสร้างไว้ใน phpMyAdmin
    $sql = "INSERT INTO `tb_daily_logs` (`student_id`, `log_date`, `time_in`, `time_out`, `work_detail`, `log_image`, `week_number`) 
            VALUES ('$student_id', '$log_date', '$time_in', '$time_out', '$work_detail', '$filename_db', '$week_number')";

    if (mysqli_query($conn, $sql)) {
        // บันทึกสำเร็จ: แสดง Alert สวย ๆ แล้วพากระโดดกลับหน้า Dashboard
        echo "<script>
                alert('บันทึกข้อมูลการปฏิบัติงานประจำวันสำเร็จแล้วค่ะ!');
                window.location.href='student_dashboard.php';
              </script>";
    } else {
        // บันทึกล้มเหลว
        echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . mysqli_error($conn);
    }
}
?>