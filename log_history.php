<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}

include('config/db_connect.php');
if ($conn) { $conn->set_charset("utf8mb4"); }

// ดึงรหัสนักศึกษา
$student_id = $_SESSION['student_id'] ?? '';
if (empty($student_id)) {
    $user_id = $_SESSION['user_id'];
    $res_std = mysqli_query($conn, "SELECT student_id FROM tb_students WHERE user_id = '$user_id'");
    $student_id = mysqli_fetch_assoc($res_std)['student_id'];
    $_SESSION['student_id'] = $student_id;
}

// ดึงประวัติการบันทึกงาน
$sql_logs = "SELECT *, ROUND(TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600, 1) AS calc_hours 
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
    <title>ประวัติย้อนหลัง - ILOG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #F4F7FE; font-family: 'Sarabun', sans-serif; }
        .sidebar { background-color: #2D3748; min-height: 100vh; color: white; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #CBD5E0; border-radius: 10px; margin-bottom: 8px; padding: 12px 18px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: #FF6B00; font-weight: bold; }
        .logout-item { position: absolute; bottom: 25px; left: 20px; right: 20px; }
        .btn-logout { color: #FC8181 !important; border: 1px solid rgba(252, 129, 129, 0.3) !important; border-radius: 10px; }
        .btn-logout:hover { background-color: #E53E3E !important; color: white !important; }
        .main-content { margin-left: 250px; padding: 30px 40px; }
        .card-custom { border-radius: 18px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        .text-orange { color: #FF6B00; }
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
            <li class="nav-item"><a class="nav-link" href="student_dashboard.php"><i class="fa-solid fa-house me-3"></i> หน้าหลัก</a></li>
            <li class="nav-item"><a class="nav-link" href="daily_log.php"><i class="fa-solid fa-pen-to-square me-3"></i> บันทึกงานรายวัน</a></li>
            <li class="nav-item"><a class="nav-link active" href="log_history.php"><i class="fa-solid fa-clock-rotate-left me-3"></i> ประวัติย้อนหลัง</a></li>
        </ul>
    </div>
    <div class="logout-item"><a class="nav-link text-center fw-bold btn-logout" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ</a></div>
</div>

<div class="main-content">
    <h3 class="fw-bold text-dark mb-4"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i> ประวัติการบันทึกงานประจำวัน</h3>

    <div class="card card-custom p-4 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 12%;">วันที่</th>
                        <th style="width: 10%;">ประเภท</th>
                        <th style="width: 15%;">เวลาเข้า-เลิกงาน</th>
                        <th style="width: 10%; text-align: center;">ชั่วโมง</th>
                        <th style="width: 38%;">รายละเอียดงาน / หมายเหตุพี่เลี้ยง</th>
                        <th style="width: 15%; text-align: center;">สถานะการตรวจ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res_logs) > 0): ?>
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
                                <strong>รายละเอียด:</strong> <?php echo nl2br(htmlspecialchars($log['work_detail'])); ?>
                                <?php if (!empty($log['mentor_remark'])): ?>
                                    <div class="mt-2 text-danger small p-2 bg-danger bg-opacity-10 rounded border border-danger">
                                        <strong><i class="fa-solid fa-comment-dots"></i> หมายเหตุจากพี่เลี้ยง:</strong> <?php echo htmlspecialchars($log['mentor_remark']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php 
                                    if($log['status'] == 'approved') {
                                        echo '<span class="badge bg-success rounded-pill px-3 py-2"><i class="fa-solid fa-check me-1"></i> ผ่านแล้ว</span>';
                                    } elseif($log['status'] == 'pending') {
                                        echo '<span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="fa-solid fa-hourglass-half me-1"></i> รอตรวจ</span>';
                                    } else {
                                        echo '<span class="badge bg-danger rounded-pill px-3 py-2"><i class="fa-solid fa-xmark me-1"></i> แก้ไขงาน</span>';
                                    }
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">คุณยังไม่มีประวัติการบันทึกงาน</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>