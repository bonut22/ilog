<?php
session_start();

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../config/db_connect.php');
if ($conn) { $conn->set_charset("utf8mb4"); }

$advisor_name = $_SESSION['full_name'] ?? "อาจารย์นิเทศก์";
$student_id = mysqli_real_escape_string($conn, $_GET['student_id'] ?? '');

// 📌 1. ดึงข้อมูลส่วนตัวนักศึกษา ข้อมูลบริษัท และคำนวณชั่วโมงสะสมที่อนุมัติแล้ว
$sql_student = "SELECT 
                    s.student_id, 
                    u.full_name, 
                    s.major, 
                    c.company_name,
                    COALESCE(SUM(
                        CASE WHEN d.status = 'approved' AND d.log_type = 'work' 
                        THEN ROUND(TIME_TO_SEC(TIMEDIFF(d.time_out, d.time_in)) / 3600, 1) ELSE 0 END
                    ), 0) AS total_hours
                FROM tb_students s
                JOIN tb_users u ON s.user_id = u.user_id
                LEFT JOIN tb_companies c ON s.company_id = c.company_id
                LEFT JOIN tb_daily_logs d ON s.student_id = d.student_id
                WHERE s.student_id = '$student_id'
                GROUP BY s.student_id";
$res_student = mysqli_query($conn, $sql_student);
$current_student = mysqli_fetch_assoc($res_student);

// 📌 2. ดึงประวัติการบันทึกงานรายวันของนักศึกษาคนนี้
$sql_logs = "SELECT *, 
                ROUND(TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600, 1) AS calc_hours
             FROM tb_daily_logs 
             WHERE student_id = '$student_id' 
             ORDER BY log_date DESC";
$res_logs = mysqli_query($conn, $sql_logs);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดการฝึกงาน - ILOG</title>
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
        .text-orange { color: #FD5D00; }
        .card-custom { border-radius: 15px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        .btn-orange { background-color: #FD5D00; color: white; border: none; }
        .btn-orange:hover { background-color: #E05A00; color: white; }
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
                <a href="dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie me-2"></i> หน้าหลักอาจารย์</a>
            </li>
            <li class="nav-item">
                <a href="advisor_supervision.php" class="nav-link"><i class="fa-solid fa-clipboard-check me-2"></i> บันทึกการนิเทศงาน</a>
            </li>
        </ul>
    </div>
    <div class="logout-item">
        <a href="../logout.php" class="nav-link text-center btn-logout fw-bold"><i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ</a>
    </div>
</div>

<div class="main-content">
    <!-- ปุ่มย้อนกลับ -->
    <div class="mb-3">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> ย้อนกลับหน้าหลัก
        </a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0"><i class="fa-solid fa-magnifying-glass text-orange me-2"></i> รายละเอียดข้อมูลการฝึกงาน</h4>
            <p class="text-muted m-0">อาจารย์นิเทศก์: <?php echo $advisor_name; ?></p>
        </div>
    </div>

    <?php if ($current_student): ?>
    <!-- ข้อมูลส่วนตัวนักศึกษา -->
    <div class="card card-custom p-4 bg-white mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="fw-bold text-dark mb-1">
                    <?php echo $current_student['full_name']; ?> 
                    <span class="text-orange fs-6">(<?php echo $current_student['student_id']; ?>)</span>
                </h5>
                <p class="text-muted small mb-0">
                    <i class="fa-solid fa-graduation-cap me-1"></i> สาขา: <?php echo $current_student['major'] ?? 'เทคโนโลยีสารสนเทศ'; ?> | 
                    <i class="fa-solid fa-building me-1"></i> สถานประกอบการ: <?php echo $current_student['company_name'] ?? '- ยังไม่ได้ระบุ -'; ?>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div class="d-inline-block text-start bg-light p-3 rounded-3 border">
                    <p class="small text-muted mb-1 fw-bold">ชั่วโมงรับรองสะสม</p>
                    <h3 class="fw-bold text-success m-0"><?php echo $current_student['total_hours']; ?> <span class="fs-6 text-muted">/ 480 ชม.</span></h3>
                </div>
            </div>
        </div>
    </div>

   <!-- ตารางบันทึกงาน -->
   <div class="card card-custom p-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold text-dark m-0"><i class="fa-solid fa-list-check text-orange me-2"></i> ประวัติการบันทึกงานประจำวัน</h5>
            <a href="advisor_supervision.php?student_id=<?php echo $student_id; ?>" class="btn btn-orange btn-sm rounded-pill px-3">
                <i class="fa-solid fa-pen-to-square me-1"></i> ไปหน้าบันทึกนิเทศงาน
            </a>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 12%;">วันที่</th>
                        <th style="width: 10%;">ประเภท</th>
                        <th style="width: 15%;">เวลาเข้า-เลิกงาน</th>
                        <th style="width: 8%; text-align: center;">ชั่วโมง</th>
                        <th style="width: 30%;">รายละเอียดงาน / เหตุผล</th>
                        <th style="width: 10%; text-align: center;">หลักฐาน</th>
                        <th style="width: 15%; text-align: center;">สถานะ (จากพี่เลี้ยง)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_logs && mysqli_num_rows($res_logs) > 0): ?>
                        <?php while($log = mysqli_fetch_assoc($res_logs)): ?>
                        <tr>
                            <td class="fw-bold"><?php echo date('d/m/Y', strtotime($log['log_date'])); ?></td>
                            <td>
                                <?php echo ($log['log_type'] == 'work') 
                                    ? '<span class="badge bg-primary rounded-pill">มาทำงาน</span>' 
                                    : '<span class="badge bg-secondary rounded-pill">ลางาน</span>'; ?>
                            </td>
                            <td class="small text-muted">
                                <?php echo ($log['log_type'] == 'work') ? date('H:i', strtotime($log['time_in'])) . ' - ' . date('H:i', strtotime($log['time_out'])) : '-'; ?>
                            </td>
                            <td class="text-center fw-bold text-orange">
                                <?php echo ($log['log_type'] == 'work') ? $log['calc_hours'] . ' ชม.' : '-'; ?>
                            </td>
                            <td class="small">
                                <?php echo nl2br(htmlspecialchars($log['work_detail'])); ?>
                                <?php if (!empty($log['mentor_remark'])): ?>
                                    <div class="mt-1 text-danger small">
                                        <strong>หมายเหตุพี่เลี้ยง:</strong> <?php echo htmlspecialchars($log['mentor_remark']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            
                            <!-- 📷 คอลัมน์ปุ่มดูรูปภาพหลักฐาน -->
                            <td class="text-center">
                                <?php if (!empty($log['log_image'])): ?>
                                    <button class="btn btn-sm btn-outline-info rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#imgModal<?php echo $log['log_id']; ?>">
                                        <i class="fa-solid fa-image"></i>
                                    </button>

                                    <!-- Modal แสดงรูปภาพหลักฐาน -->
                                    <div class="modal fade" id="imgModal<?php echo $log['log_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0">
                                                <div class="modal-header border-0">
                                                    <h6 class="modal-title fw-bold">หลักฐานวันที่ <?php echo date('d/m/Y', strtotime($log['log_date'])); ?></h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-center p-3">
                                                    <!-- สังเกต Path ตรงนี้ ใส่ ../ เพื่อถอยออกจากโฟลเดอร์ advisor ไปยัง assets -->
                                                    <img src="../assets/images/uploads/<?php echo $log['log_image']; ?>" class="img-fluid rounded-3 shadow-sm" alt="หลักฐาน">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">- ไม่มี -</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <?php 
                                    if($log['status'] == 'approved') echo '<span class="badge bg-success rounded-pill px-3">อนุมัติแล้ว</span>';
                                    elseif($log['status'] == 'pending') echo '<span class="badge bg-warning text-dark rounded-pill px-3">รอตรวจงาน</span>';
                                    else echo '<span class="badge bg-danger rounded-pill px-3">ไม่อนุมัติ</span>';
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">ยังไม่มีประวัติการบันทึกงาน</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-danger">ไม่พบข้อมูลนักศึกษา รหัสนักศึกษาไม่ถูกต้อง</div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>