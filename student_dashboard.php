<?php
session_start();

// 1. ตรวจสอบสิทธิ์การเข้าใช้งาน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// 2. เชื่อมต่อฐานข้อมูล
include('config/db_connect.php');
if ($conn) { $conn->set_charset("utf8mb4"); }

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'นักศึกษา';

// 3. ดึงรหัสนักศึกษา (student_id)
$student_id = '';
$sql_std = "SELECT student_id FROM tb_students WHERE user_id = '$user_id'";
$res_std = mysqli_query($conn, $sql_std);
if ($res_std && $row_std = mysqli_fetch_assoc($res_std)) {
    $student_id = $row_std['student_id'];
    $_SESSION['student_id'] = $student_id;
}

// 4. คำนวณสถิติต่างๆ (ชั่วโมง, วันลา, รอตรวจ)
$approved_hours = 0;
$max_hours = 480;
$leave_days = 0;
$pending_tasks = 0;

if (!empty($student_id)) {
    // คำนวณชั่วโมง
    $sql_hours = "SELECT COALESCE(SUM(
                    CASE WHEN status = 'approved' AND log_type = 'work' 
                    THEN ROUND(TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600, 1) ELSE 0 END
                 ), 0) AS total_hours 
                 FROM tb_daily_logs WHERE student_id = '$student_id'";
    $res_hours = mysqli_query($conn, $sql_hours);
    if ($res_hours && $row_h = mysqli_fetch_assoc($res_hours)) {
        $approved_hours = $row_h['total_hours'];
    }

    // นับจำนวนวันลา (อนุมัติแล้ว)
    $sql_leave = "SELECT COUNT(*) AS leave_count FROM tb_daily_logs WHERE student_id = '$student_id' AND log_type = 'leave' AND status = 'approved'";
    $res_leave = mysqli_query($conn, $sql_leave);
    if ($res_leave) $leave_days = mysqli_fetch_assoc($res_leave)['leave_count'];

    // นับงานที่รอตรวจ
    $sql_pending = "SELECT COUNT(*) AS pending_count FROM tb_daily_logs WHERE student_id = '$student_id' AND status = 'pending'";
    $res_pending = mysqli_query($conn, $sql_pending);
    if ($res_pending) $pending_tasks = mysqli_fetch_assoc($res_pending)['pending_count'];
}

$percent = min(($approved_hours / $max_hours) * 100, 100);

// 5. ดึงรายการบันทึกงานล่าสุด 5 รายการ
$sql_logs = "SELECT * FROM tb_daily_logs WHERE student_id = '$student_id' ORDER BY log_date DESC LIMIT 5";
$res_logs = mysqli_query($conn, $sql_logs);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หน้าหลัก - ILOG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #F4F7FE; font-family: 'Sarabun', sans-serif; }
        .sidebar { background-color: #2D3748; min-height: 100vh; color: white; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #CBD5E0; border-radius: 10px; margin-bottom: 8px; padding: 12px 18px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: #FF6B00; font-weight: bold; }
        .logout-item { position: absolute; bottom: 25px; left: 20px; right: 20px; }
        .btn-logout { border: 1px solid rgba(252, 129, 129, 0.3); color: #FC8181; }
        .btn-logout:hover { background-color: #E53E3E; color: white !important; }
        .main-content { margin-left: 250px; padding: 30px 40px; }
        .card-custom { border-radius: 15px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        .text-orange { color: #FF6B00; }
        .bg-orange { background-color: #FF6B00; color: white; }
    </style>
</head>
<body>

<div class="sidebar p-3 d-flex flex-column justify-content-between">
    <div>
        <div class="text-center my-4">
            <img src="assets/images/logo.jpg" alt="Logo" class="img-fluid rounded mb-2" style="max-width: 65px;">
            <h6 class="fw-bold mb-0 text-white">คณะเทคโนโลยีสารสนเทศ</h6>
            <small class="text-secondary">มรภ.เทพสตรี</small>
        </div>
        <ul class="nav flex-column mt-4">
            <li class="nav-item"><a class="nav-link active" href="student_dashboard.php"><i class="fa-solid fa-house me-3"></i> หน้าหลัก</a></li>
            <li class="nav-item"><a class="nav-link" href="daily_log.php"><i class="fa-solid fa-pen-to-square me-3"></i> บันทึกงานรายวัน</a></li>
            <li class="nav-item"><a class="nav-link" href="log_history.php"><i class="fa-solid fa-clock-rotate-left me-3"></i> ประวัติย้อนหลัง</a></li>
        </ul>
    </div>
    <div class="logout-item">
        <a class="nav-link text-center fw-bold btn-logout" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ</a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">ระบบบันทึกการฝึกงานออนไลน์ (ILOG)</h3>
            <p class="text-muted mb-0">ยินดีต้อนรับ: <strong><?php echo $full_name; ?></strong> | รหัสนักศึกษา: <strong><?php echo !empty($student_id) ? $student_id : '-'; ?></strong></p>
        </div>
        <span class="badge bg-orange px-3 py-2 rounded-pill">ภาคเรียนที่ 1/2569</span>
    </div>

    <!-- 🔥 การ์ดสรุป 3 ช่องแบบใหม่ -->
    <div class="row mb-4">
        <!-- ช่องที่ 1: ชั่วโมงสะสม -->
        <div class="col-md-4">
            <div class="card card-custom p-4 bg-white border-start border-4 border-warning h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted fw-bold">ชั่วโมงที่รับรองแล้ว</small>
                    <div class="rounded-circle bg-warning bg-opacity-10 p-2 text-warning"><i class="fa-solid fa-user-clock fs-5"></i></div>
                </div>
                <h2 class="fw-bold text-orange mb-2"><?php echo $approved_hours; ?> <span class="fs-5 text-dark">/ <?php echo $max_hours; ?></span> <small class="fs-6 text-muted">ชม.</small></h2>
                <div class="progress" style="height: 6px; border-radius: 10px;">
                    <div class="progress-bar bg-orange" style="width: <?php echo $percent; ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- ช่องที่ 2: งานรอตรวจ -->
        <div class="col-md-4">
            <div class="card card-custom p-4 bg-white border-start border-4 border-primary h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted fw-bold">บันทึกงานที่รอตรวจ</small>
                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary"><i class="fa-solid fa-hourglass-half fs-5"></i></div>
                </div>
                <h2 class="fw-bold text-primary mb-0"><?php echo $pending_tasks; ?> <small class="fs-6 text-muted">รายการ</small></h2>
                <p class="small text-muted mt-2 mb-0">รอพี่เลี้ยงสถานประกอบการอนุมัติ</p>
            </div>
        </div>

        <!-- ช่องที่ 3: ยอดวันลา -->
        <div class="col-md-4">
            <div class="card card-custom p-4 bg-white border-start border-4 border-danger h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted fw-bold">ประวัติการลางาน</small>
                    <div class="rounded-circle bg-danger bg-opacity-10 p-2 text-danger"><i class="fa-solid fa-bed-pulse fs-5"></i></div>
                </div>
                <h2 class="fw-bold text-danger mb-0"><?php echo $leave_days; ?> <small class="fs-6 text-muted">วัน</small></h2>
                <p class="small text-muted mt-2 mb-0">นับเฉพาะวันลาที่ผ่านการอนุมัติ</p>
            </div>
        </div>
    </div>

    <!-- ตารางแสดงการบันทึกล่าสุด -->
    <div class="card card-custom p-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-list-check me-2 text-orange"></i> บันทึกงานล่าสุด</h5>
            <a href="daily_log.php" class="btn btn-sm btn-outline-warning text-dark fw-bold rounded-pill px-3">+ เพิ่มบันทึกงาน</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%;">วันที่</th>
                        <th style="width: 15%;">ประเภท</th>
                        <th style="width: 50%;">รายละเอียดงาน</th>
                        <th style="width: 20%; text-align: center;">สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_logs && mysqli_num_rows($res_logs) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($res_logs)): ?>
                            <tr>
                                <td class="fw-bold text-muted"><?php echo date('d/m/Y', strtotime($row['log_date'])); ?></td>
                                <td><?php echo $row['log_type'] == 'work' ? '<span class="badge bg-light text-dark border">มาทำงานปกติ</span>' : '<span class="badge bg-secondary">ลางาน</span>'; ?></td>
                                <td><?php echo mb_strimwidth($row['work_detail'], 0, 70, '...'); ?></td>
                                <td class="text-center">
                                    <?php 
                                        if($row['status'] == 'approved') echo '<span class="badge bg-success rounded-pill px-3">อนุมัติแล้ว</span>';
                                        elseif($row['status'] == 'rejected') echo '<span class="badge bg-danger rounded-pill px-3">ปฏิเสธ</span>';
                                        else echo '<span class="badge bg-warning text-dark rounded-pill px-3">รอตรวจ</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">ยังไม่มีประวัติการบันทึกงาน</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>