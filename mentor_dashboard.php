<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('config/db_connect.php');
if ($conn) { $conn->set_charset("utf8mb4"); }

$mentor_name = $_SESSION['full_name'] ?? "สมชาย ใจดี (พี่เลี้ยงฝึกงาน)";
$company_name = $_SESSION['company_name'] ?? "บริษัท เทพสตรีซอฟต์แวร์ จำกัด";
$msg = "";

// รับค่า student_id หากมีการกดเลือกตรวจงานเด็กคนนั้น (ถ้าไม่มีจะถือว่าโชว์หน้ารวม)
$selected_std_id = $_GET['student_id'] ?? '';

// 📌 1. บันทึกผลการตรวจงาน (อนุมัติ / ปฏิเสธ)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_type'])) {
    $log_id = intval($_POST['log_id']);
    $status_action = $_POST['action_type']; // approved หรือ rejected
    $mentor_remark = mysqli_real_escape_string($conn, $_POST['mentor_remark'] ?? '');

    $sql_update = "UPDATE tb_daily_logs 
                   SET status = '$status_action', mentor_remark = '$mentor_remark' 
                   WHERE log_id = '$log_id'";

    if (mysqli_query($conn, $sql_update)) {
        $status_text = ($status_action == 'approved') ? 'อนุมัติเรียบร้อยแล้ว' : 'ปฏิเสธรายการเรียบร้อยแล้ว';
        $msg = "<div class='alert alert-success alert-dismissible fade show rounded-3 mb-4' role='alert'>
                    <i class='fa-solid fa-circle-check me-2'></i> $status_text
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
}

// 📌 2. ดึงข้อมูลนักศึกษาทั้งหมดในความดูแลของพี่เลี้ยงคนนี้
$user_id = $_SESSION['user_id'];
$sql_students = "SELECT s.student_id, u.full_name, s.major,
                 COUNT(CASE WHEN d.status = 'pending' THEN 1 END) AS pending_count,
                 COALESCE(SUM(
                    CASE WHEN d.status = 'approved' AND d.log_type = 'work' 
                    THEN ROUND(TIME_TO_SEC(TIMEDIFF(d.time_out, d.time_in)) / 3600, 1) ELSE 0 END
                 ), 0) AS approved_hours
                 FROM tb_students s
                 JOIN tb_users u ON s.user_id = u.user_id
                 LEFT JOIN tb_daily_logs d ON s.student_id = d.student_id
                 GROUP BY s.student_id";
$res_students = mysqli_query($conn, $sql_students);
$total_students = ($res_students) ? mysqli_num_rows($res_students) : 0;

// 📌 3. กรณีมีการเลือกตรวจงานเด็กเฉพาะคน ดึงข้อมูลบันทึกงานของเด็กคนนั้น
$selected_student_info = null;
$res_logs = null;
if (!empty($selected_std_id)) {
    // ดึงข้อมูลเด็กคนนี้
    $sql_info = "SELECT s.student_id, u.full_name, s.major,
                 COALESCE(SUM(
                    CASE WHEN d.status = 'approved' AND d.log_type = 'work' 
                    THEN ROUND(TIME_TO_SEC(TIMEDIFF(d.time_out, d.time_in)) / 3600, 1) ELSE 0 END
                 ), 0) AS total_hours
                 FROM tb_students s 
                 JOIN tb_users u ON s.user_id = u.user_id 
                 LEFT JOIN tb_daily_logs d ON s.student_id = d.student_id
                 WHERE s.student_id = '$selected_std_id'
                 GROUP BY s.student_id";
    $res_info = mysqli_query($conn, $sql_info);
    if ($res_info) { $selected_student_info = mysqli_fetch_assoc($res_info); }

    // ดึงรายการบันทึกงาน
    $sql_logs = "SELECT *, ROUND(TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600, 1) AS calc_hours 
                 FROM tb_daily_logs 
                 WHERE student_id = '$selected_std_id' 
                 ORDER BY log_date DESC";
    $res_logs = mysqli_query($conn, $sql_logs);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบติดตามการฝึกงาน - ILOG</title>
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
            <img src="assets/images/logo.jpg" alt="Logo" class="img-fluid rounded mb-2" style="max-width: 65px;">
            <h6 class="fw-bold mb-0 text-white">ระบบติดตามการฝึกงาน</h6>
            <small class="text-secondary">มรภ.เทพสตรี</small>
        </div>
        <ul class="nav flex-column mt-4">
            <li class="nav-item">
                <a href="mentor_dashboard.php" class="nav-link active">
                    <i class="fa-solid fa-users-viewfinder me-3"></i> นักศึกษาในความดูแล
                </a>
            </li>
        </ul>
    </div>

    <!-- ปุ่มออกจากระบบ -->
    <div class="logout-item">
        <a class="nav-link text-center fw-bold btn-logout" href="logout.php">
            <i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ
        </a>
    </div>
</div>

<!-- เนื้อหาหลักฝั่งขวา -->
<div class="main-content">

    <!-- Header สรุปสิทธิ์พี่เลี้ยง -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">ระบบบันทึกการฝึกงานออนไลน์ (ILOG)</h3>
            <p class="text-muted mb-0">
                ยินดีต้อนรับพี่เลี้ยง: <strong><?php echo $mentor_name; ?></strong> | สถานประกอบการ: <strong><?php echo $company_name; ?></strong>
            </p>
        </div>
        <span class="badge bg-orange px-3 py-2 rounded-pill fw-bold">สิทธิ์: พี่เลี้ยงฝึกงาน</span>
    </div>

    <?php echo $msg; ?>

    <!-- 📌 MODE 1: หน้าแสดงรายการตรวจงานของนักศึกษาที่เลือก -->
    <?php if (!empty($selected_std_id)): ?>

        <div class="mb-3">
            <a href="mentor_dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> ย้อนกลับหน้ารายชื่อนักศึกษา
            </a>
        </div>

        <div class="card card-custom p-4 bg-white mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-dark mb-0">
                    ตรวจรายการบันทึกงาน: <span class="text-orange"><?php echo $selected_student_info['full_name'] ?? 'นายพัฐธนนท์ ชื่นอารมณ์'; ?></span>
                </h4>
                <span class="badge bg-success px-3 py-2 rounded-pill">
                    ชั่วโมงรับรองแล้ว: <?php echo $selected_student_info['total_hours'] ?? 0; ?> / 480 ชม.
                </span>
            </div>
            <p class="text-muted small mb-0">
                รหัสนักศึกษา: <strong><?php echo $selected_std_id; ?></strong> | สาขาวิชา: <strong><?php echo $selected_student_info['major'] ?? 'เทคโนโลยีสารสนเทศ'; ?></strong>
            </p>
        </div>

        <!-- ตารางแสดงรายการงานที่ส่งมา -->
        <div class="card card-custom p-4 bg-white">
            <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-list-check text-orange me-2"></i> รายการส่งงานของนักศึกษา</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 12%;">วันที่</th>
                            <th style="width: 10%;">ประเภท</th>
                            <th style="width: 15%;">เวลาเข้า-เลิกงาน</th>
                            <th style="width: 10%; text-align: center;">ชั่วโมง</th>
                            <th style="width: 25%;">รายละเอียดงาน / เหตุผล</th>
                            <th style="width: 8%; text-align: center;">หลักฐาน</th>
                            <th style="width: 10%; text-align: center;">สถานะ</th>
                            <th style="width: 10%; text-align: center;">การตรวจงาน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_logs && mysqli_num_rows($res_logs) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($res_logs)): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo date('d/m/Y', strtotime($row['log_date'])); ?></td>
                                    <td>
                                        <?php echo ($row['log_type'] == 'work') 
                                            ? '<span class="badge bg-primary rounded-pill">มาทำงาน</span>' 
                                            : '<span class="badge bg-secondary rounded-pill">ลางาน</span>'; ?>
                                    </td>
                                    <td class="small">
                                        <?php echo ($row['log_type'] == 'work') ? date('H:i', strtotime($row['time_in'])) . ' - ' . date('H:i', strtotime($row['time_out'])) : '-'; ?>
                                    </td>
                                    <td class="text-center fw-bold text-orange">
                                        <?php echo ($row['log_type'] == 'work') ? $row['calc_hours'] . ' ชม.' : '-'; ?>
                                    </td>
                                    <td class="small"><?php echo nl2br(htmlspecialchars($row['work_detail'])); ?></td>
                                    
                                    <!-- หลักฐาน -->
                                    <td class="text-center">
                                        <?php if (!empty($row['log_image'])): ?>
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#imgModal<?php echo $row['log_id']; ?>">
                                                <i class="fa-solid fa-image"></i>
                                            </button>

                                            <div class="modal fade" id="imgModal<?php echo $row['log_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content rounded-4 border-0">
                                                        <div class="modal-header border-0">
                                                            <h6 class="modal-title fw-bold">หลักฐานวันที่ <?php echo date('d/m/Y', strtotime($row['log_date'])); ?></h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body text-center p-3">
                                                            <img src="uploads/<?php echo $row['log_image']; ?>" class="img-fluid rounded-3" alt="หลักฐาน">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">- ไม่มี -</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- สถานะ -->
                                    <td class="text-center">
                                        <?php 
                                            if($row['status'] == 'approved') echo '<span class="badge bg-success rounded-pill px-3">อนุมัติแล้ว</span>';
                                            elseif($row['status'] == 'rejected') echo '<span class="badge bg-danger rounded-pill px-3">ไม่อนุมัติ</span>';
                                            else echo '<span class="badge bg-warning text-dark rounded-pill px-3">รอตรวจงาน</span>';
                                        ?>
                                    </td>

                                    <!-- ปุ่มตรวจงาน -->
                                    <td class="text-center">
                                        <?php if($row['status'] == 'pending'): ?>
                                            <button class="btn btn-sm btn-success rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#checkModal<?php echo $row['log_id']; ?>">
                                                <i class="fa-solid fa-check me-1"></i> ตรวจงาน
                                            </button>

                                            <!-- Modal ให้ข้อคิดเห็น + อนุมัติ/ปฏิเสธ -->
                                            <div class="modal fade text-start" id="checkModal<?php echo $row['log_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content rounded-4 border-0">
                                                        <form action="mentor_dashboard.php?student_id=<?php echo $selected_std_id; ?>" method="POST">
                                                            <input type="hidden" name="log_id" value="<?php echo $row['log_id']; ?>">
                                                            <div class="modal-header border-0">
                                                                <h5 class="modal-title fw-bold text-dark">
                                                                    <i class="fa-solid fa-clipboard-check text-orange me-2"></i> ตรวจบันทึกงาน
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="small text-muted mb-1">วันที่: <strong><?php echo date('d/m/Y', strtotime($row['log_date'])); ?></strong></p>
                                                                <p class="small text-dark mb-3">รายละเอียดงาน: <em><?php echo $row['work_detail']; ?></em></p>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold text-secondary small">ข้อคิดเห็น / คำแนะนำจากพี่เลี้ยง</label>
                                                                    <textarea class="form-control" name="mentor_remark" rows="3" placeholder="ระบุข้อเสนอแนะ..."></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-0">
                                                                <button type="submit" name="action_type" value="rejected" class="btn btn-outline-danger rounded-pill px-3">ปฏิเสธ</button>
                                                                <button type="submit" name="action_type" value="approved" class="btn btn-success rounded-pill px-4 fw-bold">อนุมัติ</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="small text-muted"><i class="fa-solid fa-check-double text-success"></i> ตรวจแล้ว</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">ยังไม่มีประวัติการบันทึกงาน</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- 📌 MODE 2: หน้าหลัก Dashboard (แสดงการ์ดสรุป + ตารางรายชื่อนักศึกษา) -->
    <?php else: ?>

        <!-- การ์ดสรุปจำนวนนักศึกษา -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card card-custom p-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold">นักศึกษาในความดูแล</small>
                            <h2 class="fw-bold text-dark mb-0 mt-1"><?php echo $total_students; ?> <span class="fs-6 text-muted fw-normal">คน</span></h2>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary">
                            <i class="fa-solid fa-user-graduate fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ตารางติดตามความคืบหน้านักศึกษา -->
        <div class="card card-custom p-4 bg-white">
            <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-list-check text-orange me-2"></i> ตารางติดตามความคืบหน้านักศึกษา</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 15%;">รหัสนักศึกษา</th>
                            <th style="width: 20%;">ชื่อ-นามสกุล</th>
                            <th style="width: 20%;">สาขาวิชา</th>
                            <th style="width: 25%;">ความคืบหน้าชั่วโมง (เกณฑ์ 480 ชม.)</th>
                            <th style="width: 10%; text-align: center;">งานค้างตรวจ</th>
                            <th style="width: 10%; text-align: center;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_students && mysqli_num_rows($res_students) > 0): ?>
                            <?php while($std = mysqli_fetch_assoc($res_students)): ?>
                                <?php 
                                    $percent = min(($std['approved_hours'] / 480) * 100, 100);
                                ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?php echo $std['student_id']; ?></td>
                                    <td><?php echo $std['full_name']; ?></td>
                                    <td><?php echo $std['major'] ?? 'เทคโนโลยีสารสนเทศ'; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 8px; border-radius: 10px;">
                                                <div class="progress-bar bg-orange" role="progressbar" style="width: <?php echo $percent; ?>%;"></div>
                                            </div>
                                            <small class="fw-bold text-dark"><?php echo $std['approved_hours']; ?> ชม.</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if($std['pending_count'] > 0): ?>
                                            <span class="badge bg-danger rounded-pill px-3">ค้าง <?php echo $std['pending_count']; ?> รายการ</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted rounded-pill px-3">ไม่มีงานค้าง</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <!-- 🚀 พอกดปุ่มนี้ จะโหลดหน้าเดิมพร้อมดึงตารางตรวจงานของเด็กคนนี้ขึ้นมาทันที -->
                                        <a href="mentor_dashboard.php?student_id=<?php echo $std['student_id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                                            <i class="fa-solid fa-magnifying-glass me-1"></i> ตรวจงาน
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <!-- ข้อมูลตัวอย่างทดสอบกรณีไม่มีฐานข้อมูล -->
                            <tr>
                                <td class="fw-bold">66124630101</td>
                                <td>นายพัฐธนนท์ ชื่นอารมณ์</td>
                                <td>เทคโนโลยีสารสนเทศ</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 8px; border-radius: 10px;">
                                            <div class="progress-bar bg-orange" style="width: 25%;"></div>
                                        </div>
                                        <small class="fw-bold text-dark">120.0 ชม.</small>
                                    </div>
                                </td>
                                <td class="text-center"><span class="badge bg-danger rounded-pill px-3">ค้าง 1 รายการ</span></td>
                                <td class="text-center">
                                    <a href="mentor_dashboard.php?student_id=66124630101" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                                        <i class="fa-solid fa-magnifying-glass me-1"></i> ตรวจงาน
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>