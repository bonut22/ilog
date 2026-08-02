<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../config/db_connect.php');
if ($conn) { $conn->set_charset("utf8mb4"); }

$admin_name = $_SESSION['full_name'] ?? "แอดมินคณะเทคโนโลยีสารสนเทศ";
$msg = "";

// 📌 1. ระบบประมวลผลการจัดสรรนักศึกษาเข้าสถานประกอบการ (เมื่อกดบันทึกใน Modal)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'assign_students') {
    $company_id = intval($_POST['company_id']);
    $selected_students = $_POST['assigned_students'] ?? []; // อาร์เรย์รหัสนักศึกษาที่ถูกเลือก

    // เคลียร์นักศึกษาเดิมที่เคยสังกัดบริษัทนี้ก่อน
    $sql_clear = "UPDATE tb_students SET company_id = NULL WHERE company_id = '$company_id'";
    mysqli_query($conn, $sql_clear);

    // อัปเดตนักศึกษาใหม่ที่ถูกเลือกเข้าสังกัดบริษัทนี้
    if (!empty($selected_students)) {
        foreach ($selected_students as $std_id) {
            $std_id_clean = mysqli_real_escape_string($conn, $std_id);
            $sql_update = "UPDATE tb_students SET company_id = '$company_id' WHERE student_id = '$std_id_clean'";
            mysqli_query($conn, $sql_update);
        }
    }
    $msg = "<div class='alert alert-success alert-dismissible fade show rounded-3 mb-4' role='alert'>
                <i class='fa-solid fa-circle-check me-2'></i> อัปเดตรานชื่อนักศึกษาในความดูแลเรียบร้อยแล้วค่ะ!
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

// 📌 2. ดึงข้อมูลสถานประกอบการ พร้อมดึงรายชื่อนักศึกษาที่สังกัดอยู่ (GROUP_CONCAT)
$sql_comp = "SELECT c.*, 
             GROUP_CONCAT(CONCAT(u.full_name, ' (', s.student_id, ')') SEPARATOR '||') AS student_list,
             COUNT(s.student_id) AS student_count 
             FROM tb_companies c 
             LEFT JOIN tb_students s ON c.company_id = s.company_id 
             LEFT JOIN tb_users u ON s.user_id = u.user_id
             GROUP BY c.company_id 
             ORDER BY c.company_id DESC";
$res_comp = mysqli_query($conn, $sql_comp);

// 📌 3. ดึงรายชื่อนักศึกษาทั้งหมดในระบบไว้ใช้ใน Modal จัดการ
$sql_all_students = "SELECT s.student_id, u.full_name, s.company_id 
                     FROM tb_students s 
                     JOIN tb_users u ON s.user_id = u.user_id 
                     ORDER BY s.student_id ASC";
$res_all_students = mysqli_query($conn, $sql_all_students);
$all_students = [];
if ($res_all_students) {
    while ($st = mysqli_fetch_assoc($res_all_students)) {
        $all_students[] = $st;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสถานประกอบการ - ILOG</title>
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
        
        .student-badge {
            background-color: #FFF0E6;
            color: #FF6B00;
            border: 1px solid rgba(255, 107, 0, 0.3);
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 13px;
            display: inline-block;
            margin: 2px;
        }
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
                <a href="manage_companies.php" class="nav-link active">
                    <i class="fa-solid fa-building me-3"></i> จัดการสถานประกอบการ
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_reports.php" class="nav-link">
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
            <h3 class="fw-bold text-dark mb-1">จัดการสถานประกอบการ</h3>
            <p class="text-muted mb-0">ผู้ดูแลระบบ: <strong><?php echo $admin_name; ?></strong></p>
        </div>
        <button class="btn btn-orange px-4 py-2 rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
            <i class="fa-solid fa-plus me-2"></i> เพิ่มสถานประกอบการ
        </button>
    </div>

    <?php echo $msg; ?>

    <div class="card card-custom p-4 bg-white">
        <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-building text-orange me-2"></i> รายชื่อบริษัท / หน่วยงานรับฝึกงาน</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 20%;">ชื่อสถานประกอบการ</th>
                        <th style="width: 15%;">เบอร์โทรศัพท์</th>
                        <th style="width: 35%;">นักศึกษาฝึกงานในความดูแล</th>
                        <th style="width: 10%; text-align: center;">จำนวน</th>
                        <th style="width: 15%; text-align: center;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_comp && mysqli_num_rows($res_comp) > 0): ?>
                        <?php $i=1; while($row = mysqli_fetch_assoc($res_comp)): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td class="fw-bold text-dark"><?php echo $row['company_name']; ?></td>
                                <td><?php echo $row['phone'] ?? '-'; ?></td>
                                <td>
                                    <?php 
                                    if (!empty($row['student_list'])) {
                                        $std_array = explode('||', $row['student_list']);
                                        foreach ($std_array as $std_item) {
                                            echo "<span class='student-badge'><i class='fa-solid fa-user-graduate me-1'></i> " . htmlspecialchars($std_item) . "</span>";
                                        }
                                    } else {
                                        echo "<span class='text-muted small'>- ยังไม่มีนักศึกษา -</span>";
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill px-3"><?php echo $row['student_count']; ?> คน</span>
                                </td>
                                <td class="text-center">
                                    <!-- ปุ่มเปิด Modal จัดการนักศึกษา -->
                                    <button class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3 fw-bold me-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#assignModal<?php echo $row['company_id']; ?>">
                                        <i class="fa-solid fa-user-plus me-1"></i> เลือกนักศึกษา
                                    </button>
                                </td>
                            </tr>

                            <!-- 📌 Modal เลือกจัดการนักศึกษาให้บริษัทนี้ -->
                            <div class="modal fade" id="assignModal<?php echo $row['company_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content rounded-4 border-0">
                                        <form action="manage_companies.php" method="POST">
                                            <input type="hidden" name="action" value="assign_students">
                                            <input type="hidden" name="company_id" value="<?php echo $row['company_id']; ?>">
                                            
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-header-title fw-bold text-dark">
                                                    <i class="fa-solid fa-building text-orange me-2"></i> จัดสรรนักศึกษาให้บริษัท
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body py-3">
                                                <p class="text-muted small mb-3">บริษัท: <strong><?php echo $row['company_name']; ?></strong></p>
                                                <label class="form-label fw-bold text-secondary small">เลือกนักศึกษาที่ฝึกงาน ณ บริษัทนี้:</label>
                                                
                                                <div class="border rounded-3 p-3 bg-light" style="max-height: 250px; overflow-y: auto;">
                                                    <?php foreach ($all_students as $std): ?>
                                                        <?php $isChecked = ($std['company_id'] == $row['company_id']) ? 'checked' : ''; ?>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="assigned_students[]" 
                                                                   value="<?php echo $std['student_id']; ?>" 
                                                                   id="std_<?php echo $row['company_id'] . '_' . $std['student_id']; ?>" 
                                                                   <?php echo $isChecked; ?>>
                                                            <label class="form-check-label small fw-bold text-dark" for="std_<?php echo $row['company_id'] . '_' . $std['student_id']; ?>">
                                                                <?php echo $std['student_id'] . ' - ' . $std['full_name']; ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pt-0">
                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                                                <button type="submit" class="btn btn-orange rounded-pill px-4 fw-bold">บันทึกการจัดสรร</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">ไม่พบข้อมูลสถานประกอบการ</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>