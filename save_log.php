<?php
// save_log.php
session_start();
include('config/db_connect.php'); // เชื่อมต่อฐานข้อมูล
if ($conn) {$conn->set_charset("utf8mb4"); }

// ตรวจสอบความปลอดภัย: ต้องเป็นนักศึกษาที่ล็อกอินอยู่เท่านั้น
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. รับค่าจากฟอร์มและป้องกัน SQL Injection
    $student_id  = $_SESSION['student_id'] ?? $_SESSION['user_id']; 
    
    // หาก $_SESSION['student_id'] ไม่มีค่า ให้ดึงจากฐานข้อมูล
    if (empty($_SESSION['student_id'])) {$user_id = $_SESSION['user_id'];$sql_std = "SELECT student_id FROM tb_students WHERE user_id = '$user_id'";
        $res_std = mysqli_query($conn,$sql_std);
        $row_std = mysqli_fetch_assoc($res_std);
        $student_id =$row_std['student_id'];
    }

    $log_type    = mysqli_real_escape_string($conn, $_POST['log_type']);$log_date    = mysqli_real_escape_string($conn,$_POST['log_date']);
    
    // ถ้าเลือกลางาน ให้เวลาเข้า-ออกเป็นค่าว่าง
    $time_in     = ($log_type == 'work') ? mysqli_real_escape_string($conn,$_POST['time_in']) : NULL;
    $time_out    = ($log_type == 'work') ? mysqli_real_escape_string($conn,$_POST['time_out']) : NULL;
    
    $work_detail = mysqli_real_escape_string($conn, $_POST['work_detail']);$week_number = 1; 
    $filename_db = NULL; 

    // 2. ระบบอัปโหลดรูปภาพหลักฐาน (File Upload Handling)
    if (isset($_FILES['log_image']) && $_FILES['log_image']['error'] == 0) {$allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        $file_extension = strtolower(pathinfo($_FILES['log_image']['name'], PATHINFO_EXTENSION));

        if (in_array($file_extension, $allowed_extensions)) {$new_filename = "STD_" . $student_id . "_" . time() . "." . $file_extension;
            
            // 🔥 สร้างโฟลเดอร์อัตโนมัติหากยังไม่มี (แก้ปัญหาอัปโหลดไม่เข้า)
            $upload_dir = "assets/images/uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $upload_target = $upload_dir .$new_filename;

            if (move_uploaded_file($_FILES['log_image']['tmp_name'],$upload_target)) {
                $filename_db =$new_filename; 
                chmod($upload_target, 0777);
            }
        }
    }

    // 3. คำสั่ง SQL ยิงข้อมูลเข้าตาราง (เพิ่ม log_type และกำหนด status = pending)
    $sql = "INSERT INTO `tb_daily_logs` (`student_id`, `log_date`, `log_type`, `time_in`, `time_out`, `work_detail`, `log_image`, `week_number`, `status`) 
            VALUES ('$student_id', '$log_date', '$log_type', '$time_in', '$time_out', '$work_detail', '$filename_db', '$week_number', 'pending')";

    if (mysqli_query($conn,$sql)) {
        echo "<script>
                alert('บันทึกข้อมูลการปฏิบัติงานประจำวันสำเร็จแล้วค่ะ!');
                window.location.href='log_history.php';
              </script>";
    } else {
        echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . mysqli_error($conn);
    }
}
?>