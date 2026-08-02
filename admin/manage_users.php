<?php
session_start();

// ตรวจสอบสิทธิ์ล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../config/db_connect.php');
if ($conn) { $conn->set_charset("utf8mb4"); }

$admin_name = $_SESSION['full_name'] ?? "แอดมินคณะเทคโนโลยีสารสนเทศ";

// ดึงข้อมูลผู้ใช้งานทั้งหมด
$sql_users = "SELECT u.*, s.student_id, s.major 
              FROM tb_users u 
              LEFT JOIN tb_students s ON u.user_id = s.user_id 
              ORDER BY u.user_id DESC";
$res_users = mysqli_query($conn, $sql_users);

// นับจำนวนผู้ใช้ทั้งหมด
$total_users = ($res_users) ? mysqli_num_rows($res_users) : 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบัญชีผู้ใช้ - ILOG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #F4F7FE; font-family: 'Sarabun', sans-serif; }
        .sidebar { background-color: #2D3748; min-height: 100vh; color: white; position: fixed; width: 250px; z-index: 1000; }
        .sidebar .nav-link { color: #CBD5E0; border-radius: 10px; margin-bottom: 8px; padding: 12px 18px; font-size: 15px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: #FF6B00; font-weight: bold; }
        
        .logout-item { position: absolute; bottom: 25px; left: 20px; right: 20px; }
        .btn-logout {
            color: #FC8181 !important;
            border: 1px solid rgba(252, 129, 129, 0.3) !important;
            border-radius: 10px;
            background-color: rgba(252, 129, 129, 0.05);
            transition: all 0.3s ease;
            padding: 12px 18px;
        }
        .btn-logout:hover {
            color: white !important;
            background-color: #E53E3E !important;
            border-color: #E53E3E !important;
            box-shadow: 0 4px 12px rgba(229, 62, 62, 0.3);
        }

        .main-content { margin-left: 250px; padding: 30px 40px; }
        .card-custom { border-radius: 18px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        .text-orange { color: #FF6B00; }
        .btn-orange { background-color: #FF6B00; color: white; border: none; }
        .btn-orange:hover { background-color: #E05A00; color: white; }
    </style>
</head>
<body>

<!-- Sidebar เมนูฝั่งซ้าย -->
<div class="sidebar p-3 d-flex flex-column justify-content-between">
    <div>
        <div class="text-center my-4">
            <img src="../assets/images/logo.jpg" alt="Logo" class="img-fluid rounded mb-2" style="max-width: 65px;">
            <h6 class="fw-bold mb-0 text-white">คณะเทคโนโลยีสารสนเทศ</h6>
            <small class="text-secondary">มรภ.เทพสตรี</small>
        </div>
        <ul class="nav flex-column mt-4">
            <li class="nav-item">
                <a href="manage_users.php" class="nav-link active">
                    <i class="fa-solid fa-user-gear me-3"></i> จัดการบัญชีผู้ใช้
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_companies.php" class="nav-link">
                    <i class="fa-solid fa-building me-3"></i> จัดการสถานประกอบการ
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_reports.php" class="nav-link">
                    <i class="fa-solid fa-chart-column me-3"></i> รายงานสถิติภาพรวม
                </a>
            </li>
        </ul>
    </div>

    <!-- ปุ่มออกจากระบบ -->
    <div class="logout-item">
        <a class="nav-link text-center fw-bold btn-logout" href="../logout.php">
            <i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ
        </a>
    </div>
</div>

<!-- เนื้อหาหลักฝั่งขวา -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">ระบบบริหารจัดการหลังบ้าน (ILOG)</h3>
            <p class="text-muted mb-0">ผู้ดูแลระบบ: <strong><?php echo $admin_name; ?></strong></p>
        </div>
        <button class="btn btn-orange px-4 py-2 rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fa-solid fa-user-plus me-2"></i> เพิ่มบัญชีผู้ใช้ใหม่
        </button>
    </div>

    <!-- การ์ดแสดงจำนวนบัญชี -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card card-custom p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold">บัญชีผู้ใช้ทั้งหมดในระบบ</small>
                        <h2 class="fw-bold text-dark mb-0 mt-1"><?php echo $total_users; ?> <span class="fs-6 text-muted fw-normal">บัญชี</span></h2>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 text-warning">
                        <i class="fa-solid fa-users fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ตารางแสดงผู้ใช้งาน -->
    <div class="card card-custom p-4 bg-white">
        <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-users-gear text-orange me-2"></i> รายชื่อผู้ใช้งานและสิทธิ์ในระบบ</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Username</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>อีเมล</th>
                        <th>รายละเอียดสมาชิก</th>
                        <th>สิทธิ์ผู้ใช้</th>
                        <th class="text-center">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_users && mysqli_num_rows($res_users) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($res_users)): ?>
                            <tr>
                                <td class="fw-bold"><?php echo $row['username']; ?></td>
                                <td><?php echo $row['full_name']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td class="small text-muted">
                                    <?php 
                                        if($row['role'] == 'student') {
                                            echo "รหัสนักศึกษา: " . ($row['student_id'] ?? '-') . " | " . ($row['major'] ?? '-');
                                        } elseif($row['role'] == 'advisor') {
                                            echo "อาจารย์นิเทศก์";
                                        } else {
                                            echo "ผู้ดูแลระบบส่วนกลาง";
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        if($row['role'] == 'student') echo '<span class="badge bg-success rounded-pill">นักศึกษา</span>';
                                        elseif($row['role'] == 'advisor') echo '<span class="badge bg-primary rounded-pill">อาจารย์</span>';
                                        else echo '<span class="badge bg-danger rounded-pill">แอดมิน</span>';
                                    ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-secondary rounded-2"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <button class="btn btn-sm btn-outline-danger rounded-2"><i class="fa-solid fa-trash-can"></i></button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">ไม่พบข้อมูลผู้ใช้</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>