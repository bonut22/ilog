<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../config/db_connect.php');
if ($conn) { $conn->set_charset("utf8mb4"); }

$admin_name = $_SESSION['full_name'] ?? "แอดมินคณะเทคโนโลยีสารสนเทศ";
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานสถิติภาพรวม - ILOG</title>
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
        .bg-orange { background-color: #FF6B00; color: white; }
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
                <a href="manage_users.php" class="nav-link">
                    <i class="fa-solid fa-user-gear me-3"></i> จัดการบัญชีผู้ใช้
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_companies.php" class="nav-link">
                    <i class="fa-solid fa-building me-3"></i> จัดการสถานประกอบการ
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_reports.php" class="nav-link active">
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
            <h3 class="fw-bold text-dark mb-1">รายงานสถิติภาพรวมการฝึกงาน</h3>
            <p class="text-muted mb-0">ผู้ดูแลระบบ: <strong><?php echo $admin_name; ?></strong></p>
        </div>
        <span class="badge bg-orange px-3 py-2 rounded-pill">ปีการศึกษา 2569</span>
    </div>

    <!-- การ์ดสรุปสถิติ 3 ช่อง -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-custom p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold mb-1">นักศึกษาฝึกงานทั้งหมด</p>
                        <h2 class="fw-bold text-dark mb-0">15 <span class="fs-6 text-muted fw-normal">คน</span></h2>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary">
                        <i class="fa-solid fa-user-graduate fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold mb-1">สถานประกอบการเครือข่าย</p>
                        <h2 class="fw-bold text-dark mb-0">8 <span class="fs-6 text-muted fw-normal">แห่ง</span></h2>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success">
                        <i class="fa-solid fa-building-circle-check fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold mb-1">ผ่านเกณฑ์แล้ว (480 ชม.)</p>
                        <h2 class="fw-bold text-orange mb-0">5 <span class="fs-6 text-muted fw-normal">คน</span></h2>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 text-warning">
                        <i class="fa-solid fa-award fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- สรุปความก้าวหน้ารายสาขา -->
    <div class="card card-custom p-4 bg-white">
        <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-chart-line text-orange me-2"></i> สรุปความก้าวหน้าการฝึกงานจำแนกตามสาขาวิชา</h5>
        
        <div class="mb-4">
            <div class="d-flex justify-content-between mb-1">
                <span class="fw-bold text-dark">สาขาวิชาเทคโนโลยีสารสนเทศ</span>
                <span class="text-muted small">8 / 10 คน ผ่านเกณฑ์</span>
            </div>
            <div class="progress" style="height: 10px; border-radius: 10px;">
                <div class="progress-bar bg-orange" style="width: 80%;"></div>
            </div>
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between mb-1">
                <span class="fw-bold text-dark">สาขาวิชาวิทยาการคอมพิวเตอร์</span>
                <span class="text-muted small">3 / 5 คน ผ่านเกณฑ์</span>
            </div>
            <div class="progress" style="height: 10px; border-radius: 10px;">
                <div class="progress-bar bg-primary" style="width: 60%;"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>