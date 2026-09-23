<?php
// login.php
session_start();
include('config/db_connect.php'); // เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // คิวรีเช็คข้อมูลจากตาราง tb_users
    $sql = "SELECT * FROM tb_users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // ฝังค่ารหัสและสิทธิ์ลงใน Session
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role']; 

        // ตรวจสอบสิทธิ์ (Role) และทำการ Redirect
        if ($user['role'] == 'student') {
            // 🔥 ดึงรหัสนักศึกษาเพิ่มเติมมาเก็บใน Session (โค้ดดั้งเดิมของคุณ)
            $std_sql = "SELECT student_id FROM tb_students WHERE user_id = " . $user['user_id'];
            $std_res = mysqli_query($conn, $std_sql);
            if ($std_res && mysqli_num_rows($std_res) > 0) {
                $std_data = mysqli_fetch_assoc($std_res);
                $_SESSION['student_id'] = $std_data['student_id'];
            }

            header("Location: student_dashboard.php");
            exit();
        } elseif ($user['role'] == 'mentor') {
            // 🔥 แนบตัวแปรเสริมของพี่เลี้ยง (โค้ดดั้งเดิมของคุณ)
            $_SESSION['role'] = $user['role']; 
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
        body { 
            background: linear-gradient(135deg, #2D3748 0%, #1A202C 100%); 
            min-height: 100vh; 
            font-family: 'Sarabun', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            position: relative;
            overflow: hidden;
        }
        /* เอฟเฟกต์แสงพื้นหลัง */
        .bg-circle-1 {
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(253, 93, 0, 0.15);
            border-radius: 50%;
            top: -100px;
            left: -100px;
            filter: blur(40px);
        }
        .bg-circle-2 {
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(66, 153, 225, 0.15);
            border-radius: 50%;
            bottom: -50px;
            right: -50px;
            filter: blur(40px);
        }
        .card-login { 
            border: none; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.3); 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 10;
        }
        .btn-orange { 
            background: linear-gradient(to right, #FD5D00, #FF7B29); 
            color: white; 
            border: none; 
            transition: 0.3s; 
        }
        .btn-orange:hover { 
            background: linear-gradient(to right, #E05A00, #FD5D00); 
            color: white; 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(253, 93, 0, 0.3);
        }
        .input-group-text { background-color: transparent; border-right: none; color: #A0AEC0; }
        .form-control { border-left: none; padding: 12px 15px; }
        .form-control:focus { box-shadow: none; border-color: #dee2e6; }
        .input-group { border: 1px solid #E2E8F0; border-radius: 10px; overflow: hidden; transition: all 0.3s ease; }
        .input-group:focus-within { border-color: #FD5D00; box-shadow: 0 0 0 0.25rem rgba(253, 93, 0, 0.1); }
        .input-group:focus-within .input-group-text { color: #FD5D00; }
    </style>
</head>
<body>

<div class="bg-circle-1"></div>
<div class="bg-circle-2"></div>

<div class="container" style="max-width: 430px;">
    <div class="card card-login p-4">
        <div class="text-center mb-4">
            <img src="assets/images/logo.jpg" alt="Logo" class="img-fluid rounded mb-2 shadow-sm" style="max-width: 75px; height: 75px; object-fit: cover;">
            <h4 class="fw-bold text-dark mt-2 mb-1">ระบบฝึกงานออนไลน์ ILOG</h4>
            <p class="text-muted small mb-0">คณะเทคโนโลยีสารสนเทศ มรภ.เทพสตรี</p>
        </div>

        <?php if($error_msg != ""): ?>
            <div class="alert alert-danger text-center py-2 small rounded-3 shadow-sm" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small text-secondary">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                    <input type="text" class="form-control" name="username" placeholder="กรอกชื่อผู้ใช้งาน" required autocomplete="off">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold small text-secondary">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" class="form-control" name="password" placeholder="กรอกรหัสผ่าน" required>
                </div>
            </div>
            <button type="submit" class="btn btn-orange btn-lg w-100 fw-bold shadow-sm rounded-3 py-2">
                <i class="fa-solid fa-right-to-bracket me-2"></i> เข้าสู่ระบบ
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>