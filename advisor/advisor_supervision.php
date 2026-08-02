<?php
// advisor/advisor_supervision.php
session_start();
// include('../config/db_connect.php'); // เปิดคอมเมนต์เมื่อเชื่อมต่อฐานข้อมูลจริง

$advisor_name = $_SESSION['full_name'] ?? "อาจารย์ธนชัย ปฐมรัตน์";
$selected_std_id = $_GET['student_id'] ?? '';
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $msg = "<div class='alert alert-success rounded-3 mb-4'><i class='fa-solid fa-circle-check me-2'></i> บันทึกผลการนิเทศงานเรียบร้อยแล้วค่ะ!</div>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกการนิเทศงาน - ILOG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #F4F7FE; font-family: 'Sarabun', sans-serif; }
        .sidebar { background-color: #2D3748; min-height: 100vh; color: white; position: fixed; width: 250px; z-index: 1000; }
        .sidebar .nav-link { color: #CBD5E0; border-radius: 10px; margin-bottom: 8px; padding: 12px 18px; font-size: 15px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: #FD5D00; font-weight: bold; }
        
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
        .text-orange { color: #FD5D00; }
        .btn-orange { background-color: #FD5D00; color: white; border: none; }
        .btn-orange:hover { background-color: #E05200; color: white; }
    </style>
</head>
<body>

<div class="sidebar p-3 d-flex flex-column justify-content-between">
    <div>
        <div class="text-center my-4">
            <img src="../assets/images/logo.jpg" alt="Logo" class="img-fluid rounded mb-2" style="max-width: 65px;">
            <h6 class="fw-bold mb-0 text-white">คณะเทคโนโลยีสารสนเทศ</h6>
            <small class="text-secondary">มรภ.เทพสตรี</small>
        </div>
        <ul class="nav flex-column mt-4">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie me-3"></i> หน้าหลักอาจารย์</a>
            </li>
            <li class="nav-item">
                <a href="advisor_students.php" class="nav-link"><i class="fa-solid fa-users me-3"></i> นักศึกษาในความดูแล</a>
            </li>
            <li class="nav-item">
                <a href="advisor_supervision.php" class="nav-link active"><i class="fa-solid fa-clipboard-check me-3"></i> บันทึกการนิเทศงาน</a>
            </li>
        </ul>
    </div>

    <div class="logout-item">
        <a class="nav-link text-center fw-bold btn-logout" href="../logout.php">
            <i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ
        </a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0"><i class="fa-solid fa-clipboard-check text-orange me-2"></i> บันทึกการนิเทศงานฝึกงาน</h4>
            <p class="text-muted m-0">อาจารย์นิเทศก์: <?php echo $advisor_name; ?></p>
        </div>
    </div>

    <?php echo $msg; ?>

    <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
        <form action="advisor_supervision.php" method="POST">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold text-secondary small">เลือกนักศึกษาที่รับการนิเทศ</label>
                    <select class="form-select" name="student_id" required>
                        <option value="">-- กรุณาเลือกนักศึกษา --</option>
                        <option value="66124630101" <?php echo ($selected_std_id == '66124630101') ? 'selected' : ''; ?>>66124630101 - นายพัฐธนนท์ ชื่นอารมณ์</option>
                        <option value="66124630102" <?php echo ($selected_std_id == '66124630102') ? 'selected' : ''; ?>>66124630102 - นางสาวสมศรี เรียนดี</option>
                        <option value="66124630103" <?php echo ($selected_std_id == '66124630103') ? 'selected' : ''; ?>>66124630103 - นายสมศักดิ์ มุ่งมั่น</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold text-secondary small">วันที่ออกนิเทศ</label>
                    <input type="date" class="form-control" name="visit_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold text-secondary small">รูปแบบการนิเทศ</label>
                    <select class="form-select" name="visit_type" required>
                        <option value="onsite">On-site (ลงพื้นที่จริง)</option>
                        <option value="online">Online (ประชุมออนไลน์)</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-secondary small">คะแนนประเมินภาพรวม (1 - 100 คะแนน)</label>
                <input type="number" class="form-control" name="eval_score" min="0" max="100" placeholder="เช่น 85" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-secondary small">สรุปผลการนิเทศ / ข้อเสนอแนะแก่นักศึกษาและสถานประกอบการ</label>
                <textarea class="form-control" name="advisor_comment" rows="4" placeholder="ระบุรายละเอียดการพูดคุยกับพี่เลี้ยง พฤติกรรมนักศึกษา และข้อเสนอแนะ..." required></textarea>
            </div>

            <div class="text-end border-top pt-3">
                <button type="submit" class="btn btn-orange btn-lg px-5 rounded-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-floppy-disk me-2"></i> บันทึกผลการนิเทศ
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>