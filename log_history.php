<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}
include('config/db_connect.php');
$student_id = $_SESSION['student_id'] ?? '';

// ดึงรายการบันทึกงานย้อนหลังทั้งหมด
$sql = "SELECT * FROM tb_daily_logs WHERE student_id = '$student_id' ORDER BY log_date DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประวัติย้อนหลัง - ILOG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #F4F7FE; font-family: 'Sarabun', sans-serif; }
        .sidebar { background-color: #2D3748; min-height: 100vh; color: white; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #CBD5E0; border-radius: 10px; margin-bottom: 8px; padding: 12px 18px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: #FF6B00; font-weight: bold; }
        .logout-item { position: absolute; bottom: 25px; left: 20px; right: 20px; }
        .btn-logout { color: #FC8181 !important; border: 1px solid rgba(252, 129, 129, 0.3) !important; border-radius: 10px; }
        .main-content { margin-left: 250px; padding: 30px 40px; }
        .card-custom { border-radius: 18px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
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
            <li class="nav-item"><a class="nav-link" href="weekly_submit.php"><i class="fa-solid fa-paper-plane me-3"></i> ส่งอนุมัติรายสัปดาห์</a></li>
            <li class="nav-item"><a class="nav-link active" href="log_history.php"><i class="fa-solid fa-clock-rotate-left me-3"></i> ประวัติย้อนหลัง</a></li>
        </ul>
    </div>
    <div class="logout-item"><a class="nav-link text-center fw-bold btn-logout" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ</a></div>
</div>

<div class="main-content">
    <h3 class="fw-bold text-dark mb-4"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i> ประวัติการบันทึกงานย้อนหลัง</h3>

    <div class="card card-custom p-4 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>วันที่</th>
                        <th>ประเภท</th>
                        <th>เวลาเข้า-ออก</th>
                        <th>รายละเอียด</th>
                        <th class="text-center">สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="fw-bold"><?php echo date('d/m/Y', strtotime($row['log_date'])); ?></td>
                                <td><?php echo $row['log_type'] == 'work' ? '<span class="badge bg-primary">ทำงาน</span>' : '<span class="badge bg-secondary">ลางาน</span>'; ?></td>
                                <td><?php echo $row['log_type'] == 'work' ? $row['time_in'].' - '.$row['time_out'] : '-'; ?></td>
                                <td><?php echo $row['work_detail']; ?></td>
                                <td class="text-center">
                                    <?php 
                                        if($row['status'] == 'approved') echo '<span class="badge bg-success">อนุมัติแล้ว</span>';
                                        elseif($row['status'] == 'rejected') echo '<span class="badge bg-danger">ปฏิเสธ</span>';
                                        else echo '<span class="badge bg-warning text-dark">รอตรวจสอบ</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">ไม่พบประวัติการบันทึกงาน</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>