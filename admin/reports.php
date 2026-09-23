<?php
session_start();

// ตรวจสอบสิทธิ์ (ต้องเป็นแอดมินเท่านั้น)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include('../config/db_connect.php');
if ($conn) { $conn->set_charset("utf8mb4"); }

// ==========================================
// 📌 1. ดึงข้อมูลสถิติภาพรวม (Overview Stats)
// ==========================================
// สถิติผู้ใช้งานแบ่งตามสิทธิ์
$sql_users = "SELECT role, COUNT(*) as count FROM tb_users GROUP BY role";
$res_users = mysqli_query($conn, $sql_users);
$user_stats = ['student' => 0, 'mentor' => 0, 'advisor' => 0, 'admin' => 0];
while ($row = mysqli_fetch_assoc($res_users)) {
    $user_stats[$row['role']] = $row['count'];
}
$total_users = array_sum($user_stats);

// สถิติสถานประกอบการ
$res_comp = mysqli_query($conn, "SELECT COUNT(*) as count FROM tb_companies");
$comp_count = mysqli_fetch_assoc($res_comp)['count'];

// สถิติการบันทึกงานรายวันทั้งหมด
$res_logs = mysqli_query($conn, "SELECT COUNT(*) as count FROM tb_daily_logs");
$log_count = mysqli_fetch_assoc($res_logs)['count'];

// ==========================================
// 📌 2. ดึงข้อมูลความคืบหน้านักศึกษา (Student Progress)
// ==========================================
$sql_progress = "SELECT 
                    s.student_id, 
                    u.full_name, 
                    c.company_name,
                    COALESCE(SUM(
                        CASE WHEN d.status = 'approved' AND d.log_type = 'work' 
                        THEN ROUND(TIME_TO_SEC(TIMEDIFF(d.time_out, d.time_in)) / 3600, 1) ELSE 0 END
                    ), 0) AS total_hours
                 FROM tb_students s
                 JOIN tb_users u ON s.user_id = u.user_id
                 LEFT JOIN tb_companies c ON s.company_id = c.company_id
                 LEFT JOIN tb_daily_logs d ON s.student_id = d.student_id
                 GROUP BY s.student_id
                 ORDER BY total_hours DESC";
$res_progress = mysqli_query($conn, $sql_progress);
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
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: #FD5D00; font-weight: bold; }
        .logout-item { position: absolute; bottom: 25px; left: 20px; right: 20px; }
        .btn-logout { color: #FC8181 !important; border: 1px solid rgba(252, 129, 129, 0.3) !important; border-radius: 10px; background-color: rgba(252, 129, 129, 0.05); }
        .btn-logout:hover { color: white !important; background-color: #E53E3E !important; }
        .main-content { margin-left: 250px; padding: 30px 40px; }
        .card-custom { border-radius: 15px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        .text-orange { color: #FD5D00; }
        .bg-orange { background-color: #FD5D00; color: white; }
        
        /* ตั้งค่าสำหรับการ Print */
        @media print {
            .sidebar, .btn-print, .logout-item { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
            body { background-color: white !important; }
            .card-custom { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
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
            <li class="nav-item"><a href="manage_users.php" class="nav-link"><i class="fa-solid fa-user-gear me-2"></i> จัดการบัญชีผู้ใช้</a></li>
            <li class="nav-item"><a href="manage_companies.php" class="nav-link"><i class="fa-solid fa-building me-2"></i> จัดการสถานประกอบการ</a></li>
            <li class="nav-item"><a href="reports.php" class="nav-link active"><i class="fa-solid fa-chart-line me-2"></i> รายงานสถิติภาพรวม</a></li>
        </ul>
    </div>
    <div class="logout-item">
        <a href="../logout.php" class="nav-link text-center btn-logout fw-bold"><i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ</a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">รายงานสถิติภาพรวม (Dashboard)</h3>
            <p class="text-muted m-0">ข้อมูลอัปเดตล่าสุด: <?php echo date('d/m/Y H:i'); ?></p>
        </div>
        <!-- ปุ่ม Print (จะถูกซ่อนตอนสั่งปริ้น) -->
        <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-bold btn-print">
            <i class="fa-solid fa-print me-2"></i> พิมพ์รายงาน
        </button>
    </div>

    <!-- การ์ดสรุปสถิติ 4 ช่อง -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-custom p-3 bg-white border-start border-4 border-primary">
                <p class="text-muted fw-bold mb-1 small">นักศึกษาฝึกงานทั้งหมด</p>
                <h3 class="fw-bold text-dark m-0"><?php echo $user_stats['student']; ?> <span class="fs-6 text-muted fw-normal">คน</span></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-custom p-3 bg-white border-start border-4 border-success">
                <p class="text-muted fw-bold mb-1 small">พี่เลี้ยงสถานประกอบการ</p>
                <h3 class="fw-bold text-dark m-0"><?php echo $user_stats['mentor']; ?> <span class="fs-6 text-muted fw-normal">คน</span></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-custom p-3 bg-white border-start border-4 border-warning">
                <p class="text-muted fw-bold mb-1 small">สถานประกอบการ</p>
                <h3 class="fw-bold text-dark m-0"><?php echo $comp_count; ?> <span class="fs-6 text-muted fw-normal">แห่ง</span></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-custom p-3 bg-white border-start border-4 border-danger">
                <p class="text-muted fw-bold mb-1 small">รายการบันทึกงานทั้งหมด</p>
                <h3 class="fw-bold text-dark m-0"><?php echo $log_count; ?> <span class="fs-6 text-muted fw-normal">รายการ</span></h3>
            </div>
        </div>
    </div>

    <!-- ตารางความคืบหน้านักศึกษา -->
    <div class="card card-custom p-4 bg-white">
        <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-list-check text-orange me-2"></i> ความคืบหน้าชั่วโมงฝึกงาน (สะสม)</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light text-center">
                    <tr>
                        <th style="width: 15%;">รหัสนักศึกษา</th>
                        <th style="width: 25%;">ชื่อ-นามสกุล</th>
                        <th style="width: 30%;">สถานประกอบการ</th>
                        <th style="width: 15%;">ชั่วโมงอนุมัติแล้ว</th>
                        <th style="width: 15%;">ความคืบหน้า (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res_progress) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($res_progress)): 
                            $percent = min(($row['total_hours'] / 480) * 100, 100);
                        ?>
                        <tr>
                            <td class="text-center fw-bold"><?php echo $row['student_id']; ?></td>
                            <td><?php echo $row['full_name']; ?></td>
                            <td class="small"><?php echo $row['company_name'] ?? '<span class="text-muted">- ยังไม่มีบริษัท -</span>'; ?></td>
                            <td class="text-center text-success fw-bold"><?php echo $row['total_hours']; ?> ชม.</td>
                            <td>
                                <div class="progress" style="height: 15px; border-radius: 10px;">
                                    <div class="progress-bar <?php echo ($percent >= 100) ? 'bg-success' : 'bg-orange'; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $percent; ?>%; font-size: 0.7rem;">
                                        <?php echo number_format($percent, 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อมูลนักศึกษา</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>