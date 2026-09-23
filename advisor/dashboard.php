<?php
session_start();

// ตรวจสอบสิทธิ์การเข้าใช้งาน
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../config/db_connect.php');
if ($conn) { $conn->set_charset("utf8mb4"); }

$advisor_name = $_SESSION['full_name'] ?? "อาจารย์นิเทศก์";

// 📌 1. ดึงสถิติภาพรวม (จำนวนนักศึกษาทั้งหมด และ งานที่รอพี่เลี้ยงตรวจ)
$sql_stats = "SELECT 
                COUNT(DISTINCT s.student_id) AS total_students,
                COUNT(CASE WHEN d.status = 'pending' THEN 1 END) AS total_pending
              FROM tb_students s
              LEFT JOIN tb_daily_logs d ON s.student_id = d.student_id";
$res_stats = mysqli_query($conn, $sql_stats);
$stats = mysqli_fetch_assoc($res_stats);
$total_students = $stats['total_students'] ?? 0;
$total_pending = $stats['total_pending'] ?? 0;

// 📌 2. ดึงรายชื่อนักศึกษา ข้อมูลบริษัท และคำนวณชั่วโมงฝึกงานจริง
$sql_students = "SELECT 
                    s.student_id, 
                    u.full_name, 
                    c.company_name,
                    COALESCE(SUM(
                        CASE WHEN d.status = 'approved' AND d.log_type = 'work' 
                        THEN ROUND(TIME_TO_SEC(TIMEDIFF(d.time_out, d.time_in)) / 3600, 1) ELSE 0 END
                    ), 0) AS total_hours,
                    COUNT(CASE WHEN d.status = 'pending' THEN 1 END) AS pending_count
                 FROM tb_students s
                 JOIN tb_users u ON s.user_id = u.user_id
                 LEFT JOIN tb_companies c ON s.company_id = c.company_id
                 LEFT JOIN tb_daily_logs d ON s.student_id = d.student_id
                 GROUP BY s.student_id
                 ORDER BY s.student_id ASC";
$res_students = mysqli_query($conn, $sql_students);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลักอาจารย์นิเทศ - ILOG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #F4F7FE; font-family: 'Sarabun', sans-serif; }
        .sidebar { background-color: #2D3748; min-height: 100vh; color: white; position: fixed; width: 250px; z-index: 1000; }
        .sidebar .nav-link { color: #CBD5E0; border-radius: 10px; margin-bottom: 8px; padding: 12px 18px; font-size: 15px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: #FD5D00; font-weight: bold; }
        
        .logout-item { position: absolute; bottom: 25px; left: 20px; right: 20px; }
        .btn-logout {
            color: #FC8181 !important; border: 1px solid rgba(252, 129, 129, 0.3) !important;
            border-radius: 10px; background-color: rgba(252, 129, 129, 0.05); transition: all 0.3s ease;
        }
        .btn-logout:hover { color: white !important; background-color: #E53E3E !important; }

        .main-content { margin-left: 250px; padding: 30px 40px; }
        .card-custom { border-radius: 15px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        .text-orange { color: #FD5D00; }
        .bg-orange { background-color: #FD5D00 !important; color: white; }
    </style>
</head>
<body>

<div class="sidebar p-3 d-flex flex-column justify-content-between">
    <div>
        <div class="text-center my-4">
            <img src="../assets/images/logo.jpg" alt="Logo" class="img-fluid rounded mb-2" style="max-width: 60px;">
            <h6 class="m-0 small fw-bold text-white">คณะเทคโนโลยีสารสนเทศ</h6>
            <p class="small text-secondary mb-0">มรภ.เทพสตรี</p>
        </div>
        <hr class="text-secondary">
        <ul class="nav flex-column mt-4">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fa-solid fa-chart-pie me-2"></i> หน้าหลักอาจารย์
                </a>
            </li>
            </li>
            <li class="nav-item">
                <a href="advisor_students.php" class="nav-link">
                    <i class="fa-solid fa-users me-2"></i> นักศึกษาในความดูแล
                </a>
            </li>
            <li class="nav-item">
                <a href="advisor_supervision.php" class="nav-link">
                    <i class="fa-solid fa-clipboard-check me-2"></i> บันทึกการนิเทศงาน
                </a>
            </li>
        </ul>
    </div>
    <div class="logout-item">
        <a href="../logout.php" class="nav-link text-center btn-logout fw-bold">
            <i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ
        </a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">ระบบติดตามการฝึกงานออนไลน์ (ILOG)</h3>
            <p class="text-muted m-0">อาจารย์นิเทศก์: <?php echo $advisor_name; ?></p>
        </div>
        <span class="badge bg-primary px-3 py-2 rounded-pill fw-bold fs-6">สิทธิ์อาจารย์นิเทศก์</span>
    </div>

    <!-- การ์ดสรุปข้อมูล -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card card-custom p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted fw-bold mb-1 small">นักศึกษาในความดูแลทั้งหมด</p>
                        <h2 class="fw-bold text-dark m-0"><?php echo $total_students; ?> <span class="fs-6 text-muted fw-normal">คน</span></h2>
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
                        <p class="text-muted fw-bold mb-1 small">รายงานรอพี่เลี้ยงตรวจสอบ</p>
                        <h2 class="fw-bold text-orange m-0"><?php echo $total_pending; ?> <span class="fs-6 text-muted fw-normal">รายการ</span></h2>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 text-warning">
                        <i class="fa-solid fa-bell fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ตารางรายชื่อนักศึกษา -->
    <div class="card card-custom p-4 bg-white">
        <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-list-ul text-orange me-2"></i> ตารางติดตามความคืบหน้านักศึกษาฝึกงาน</h5>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%;">รหัสนักศึกษา</th>
                        <th style="width: 20%;">ชื่อ-นามสกุล</th>
                        <th style="width: 25%;">สถานที่ฝึกงาน</th>
                        <th style="width: 20%;">ความคืบหน้า (เกณฑ์ 480 ชม.)</th>
                        <th style="width: 10%; text-align: center;">สถานะ</th>
                        <th style="width: 10%; text-align: center;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_students && mysqli_num_rows($res_students) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($res_students)): ?>
                            <?php 
                                $percent = min(($row['total_hours'] / 480) * 100, 100); 
                                
                                // เช็คสถานะแจ้งเตือน
                                if ($row['pending_count'] > 0) {
                                    $status_badge = '<span class="badge bg-warning text-dark rounded-pill px-3">รอตรวจงาน</span>';
                                } elseif ($row['total_hours'] < 100) {
                                    $status_badge = '<span class="badge bg-danger rounded-pill px-3">ชั่วโมงน้อย</span>';
                                } else {
                                    $status_badge = '<span class="badge bg-success rounded-pill px-3">ปกติ</span>';
                                }
                            ?>
                            <tr>
                                <td class="fw-bold"><?php echo $row['student_id']; ?></td>
                                <td><?php echo $row['full_name']; ?></td>
                                <td class="small text-muted"><?php echo $row['company_name'] ?? '- ยังไม่มีบริษัท -'; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <small class="fw-bold text-orange me-2" style="width: 45px;"><?php echo $row['total_hours']; ?> ชม.</small>
                                        <div class="progress flex-grow-1" style="height: 6px; border-radius: 10px;">
                                            <div class="progress-bar bg-orange" style="width: <?php echo $percent; ?>%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center"><?php echo $status_badge; ?></td>
                                <td class="text-center">
                                    <a href="advisor_view_log.php?student_id=<?php echo $row['student_id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                        <i class="fa-solid fa-magnifying-glass me-1"></i> ดูรายละเอียด
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">ไม่พบข้อมูลนักศึกษาในความดูแล</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>