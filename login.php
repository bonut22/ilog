<?php
// login.php
session_start();
include('config/db_connect.php'); // เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูลที่เราทำผ่านมา

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // คิวรีเช็คข้อมูลจากตาราง tb_users ที่เพิ่งสร้าง
    $sql = "SELECT * FROM tb_users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // ฝังค่ารหัสและสิทธิ์ลงใน Session ของผู้ใช้
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role']; // สามารถเอาไว้เช็คสิทธิ์แบบเดิม

        // ตรวจสอบสิทธิ์ (Role) และทำการกระโดด (Redirect) ไปยังหน้าแดชบอร์ดที่ถูกต้อง
        if ($user['role'] == 'student') {
            // ดึงรหัสนักศึกษาเพิ่มเติมมาเก็บใน Session เพื่อเอาไว้ดึงข้อมูลภาระงาน
            $std_sql = "SELECT student_id FROM tb_students WHERE user_id = " . $user['user_id'];
            $std_res = mysqli_query($conn, $std_sql);
            $std_data = mysqli_fetch_assoc($std_res);
            $_SESSION['student_id'] = $std_data['student_id'];

            header("Location: student_dashboard.php");
            exit();
        } elseif ($user['role'] == 'mentor') {
            // 🚀 เพิ่มบล็อกนี้: ถ้าสิทธิ์เป็นพี่เลี้ยง ให้โยนไปที่หน้า mentor_dashboard.php ทันที
            $_SESSION['role'] = $user['role']; // แนบเพิ่มเติมเผื่อไฟล์แดชบอร์ดเรียกใช้ตัวแปรนี้
            header("Location: mentor_dashboard.php");
            exit();
        } elseif ($user['role'] == 'advisor') {
            header("Location: advisor/dashboard.php");
            exit();
        } elseif ($user['role'] == 'admin') {
            header("Location: admin/manage_users.php");
            exit();
        }
    } else {
        $error_msg = "Username หรือ Password ไม่ถูกต้องค่ะ!";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ILOG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #2D3748 0%, #1A202C 100%); min-height: 100vh; font-family: 'Sarabun', sans-serif; }
        .card-login { border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); background-color: rgba(255, 255, 255, 0.95); }
        .btn-orange { background-color: #FF6B00; color: white; border: none; transition: 0.3s; }
        .btn-orange:hover { background-color: #E05A00; color: white; transform: translateY(-2px); }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">

<div class="container" style="max-width: 450px;">
    <div class="card card-login p-4">
        <div class="text-center mb-4">
            <img src="assets/images/logo.jpg" alt="Logo" class="img-fluid rounded mb-2" style="max-width: 70px;">
            <h4 class="fw-bold text-dark mt-2">ระบบฝึกงานออนไลน์ ILOG</h4>
            <p class="text-muted small">คณะเทคโนโลยีสารสนเทศ มรภ.เทพสตรี</p>
        </div>

        <?php if($error_msg != ""): ?>
            <div class="alert alert-danger text-center py-2 small" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small text-secondary">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-user text-muted"></i></span>
                    <input type="text" class="form-control" name="username" placeholder="กรอกชื่อผู้ใช้งาน" required autocomplete="off">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold small text-secondary">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" class="form-control" name="password" placeholder="กรอกรหัสผ่าน" required>
                </div>
            </div>
            <button type="submit" class="btn btn-orange btn-lg w-100 fw-bold shadow-sm rounded-3">
                <i class="fa-solid fa-right-to-bracket me-2"></i> เข้าสู่ระบบ
            </button>
        </form>
    </div>
</div>

</body>
</html>