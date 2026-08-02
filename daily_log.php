<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}
include('config/db_connect.php');
$current_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บันทึกงานรายวัน - ILOG</title>
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
        .btn-orange { background-color: #FF6B00; color: white; }
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
            <li class="nav-item"><a class="nav-link active" href="daily_log.php"><i class="fa-solid fa-pen-to-square me-3"></i> บันทึกงานรายวัน</a></li>
            <li class="nav-item"><a class="nav-link" href="weekly_submit.php"><i class="fa-solid fa-paper-plane me-3"></i> ส่งอนุมัติรายสัปดาห์</a></li>
            <li class="nav-item"><a class="nav-link" href="log_history.php"><i class="fa-solid fa-clock-rotate-left me-3"></i> ประวัติย้อนหลัง</a></li>
        </ul>
    </div>
    <div class="logout-item"><a class="nav-link text-center fw-bold btn-logout" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ</a></div>
</div>

<div class="main-content">
    <h3 class="fw-bold text-dark mb-4"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> บันทึกการปฏิบัติงานรายวัน</h3>

    <div class="card card-custom p-4 bg-white">
        <form action="save_log.php" method="POST" enctype="multipart/form-data">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-secondary small">ประเภทการลงงาน</label>
                    <select class="form-select" name="log_type" id="log_type" onchange="toggleTimeFields()">
                        <option value="work">มาทำงานปกติ</option>
                        <option value="leave">ลางาน (กิจ/ป่วย)</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold text-secondary small">วันที่ปฏิบัติงาน / ลา</label>
                    <input type="date" class="form-control" name="log_date" value="<?php echo $current_date; ?>" required>
                </div>
            </div>

            <!-- โซนเวลาเข้า-ออกงาน (จะถูกซ่อนเมื่อเลือกลางาน) -->
            <div class="row g-3 mb-4" id="time_zone">
                <div class="col-md-6">
                    <label class="form-label fw-bold text-secondary small">เวลาเข้างาน</label>
                    <input type="time" class="form-control" name="time_in" value="08:30">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold text-secondary small">เวลาเลิกงาน</label>
                    <input type="time" class="form-control" name="time_out" value="17:30">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-secondary small">รายละเอียดงาน / เหตุผลการลา</label>
                <textarea class="form-control" name="work_detail" rows="5" placeholder="ระบุสิ่งที่ทำในวันนี้ หรือสาเหตุการลา..." required></textarea>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-secondary small">อัปโหลดภาพหลักฐาน / ใบลา</label>
                <input type="file" class="form-control" name="log_image" accept="image/*" required>
            </div>

            <div class="text-end border-top pt-3">
                <button type="submit" class="btn btn-orange btn-lg px-5 rounded-3 fw-bold">
                    <i class="fa-solid fa-floppy-disk me-2"></i> บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleTimeFields() {
    var type = document.getElementById("log_type").value;
    var timeZone = document.getElementById("time_zone");
    if (type === "leave") {
        timeZone.style.display = "none";
    } else {
        timeZone.style.display = "flex";
    }
}
</script>
</body>
</html>