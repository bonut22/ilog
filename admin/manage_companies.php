<?php
session_start();

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include('../config/db_connect.php');
if ($conn) { $conn->set_charset("utf8mb4"); }

// ==========================================
// 📌 1. ระบบเพิ่มสถานประกอบการ (Create)
// ==========================================
// ==========================================
// 📌 1. ระบบเพิ่มสถานประกอบการ (Create)
// ==========================================
if (isset($_POST['add_company'])) {
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    $sql_add = "INSERT INTO tb_companies (company_name, phone) VALUES ('$company_name', '$phone')";
    
    if (mysqli_query($conn, $sql_add)) {
        echo "<script>alert('✅ เพิ่มสถานประกอบการเรียบร้อยแล้ว'); window.location='manage_companies.php';</script>";
        exit();
    } else {
        // เพิ่มบรรทัดนี้เข้ามา เพื่อให้ระบบฟ้องว่า Error เพราะอะไร
        $error_msg = mysqli_error($conn);
        echo "<script>alert('❌ บันทึกไม่สำเร็จ สาเหตุ: $error_msg'); window.history.back();</script>";
        exit();
    }
}

// ==========================================
// 📌 2. ระบบจัดสรรนักศึกษาเข้าบริษัท (Update)
// ==========================================
if (isset($_POST['assign_student'])) {
    $company_id = mysqli_real_escape_string($conn, $_POST['company_id']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    
    if (!empty($student_id)) {
        $sql_assign = "UPDATE tb_students SET company_id = '$company_id' WHERE student_id = '$student_id'";
        if (mysqli_query($conn, $sql_assign)) {
            echo "<script>alert('✅ จัดสรรนักศึกษาเข้าบริษัทเรียบร้อย'); window.location='manage_companies.php';</script>";
        }
    }
}

// ==========================================
// 📌 3. ระบบลบสถานประกอบการ (Delete)
// ==========================================
if (isset($_GET['delete_id'])) {
    $del_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    
    // เคลียร์ค่า company_id ของเด็กที่อยู่บริษัทนี้ให้กลายเป็น NULL ก่อน (กันบัค)
    mysqli_query($conn, "UPDATE tb_students SET company_id = NULL WHERE company_id = '$del_id'");
    
    // แล้วค่อยลบบริษัท
    mysqli_query($conn, "DELETE FROM tb_companies WHERE company_id = '$del_id'");
    echo "<script>alert('🗑️ ลบสถานประกอบการเรียบร้อย'); window.location='manage_companies.php';</script>";
}

// ==========================================
// 📌 4. ระบบถอดนักศึกษาออกจากบริษัท (Unassign)
// ==========================================
if (isset($_GET['remove_student_id'])) {
    $rem_std_id = mysqli_real_escape_string($conn, $_GET['remove_student_id']);
    mysqli_query($conn, "UPDATE tb_students SET company_id = NULL WHERE student_id = '$rem_std_id'");
    echo "<script>window.location='manage_companies.php';</script>";
}

// ดึงข้อมูลสถานประกอบการทั้งหมด
$sql_comp = "SELECT * FROM tb_companies ORDER BY company_id DESC";
$res_comp = mysqli_query($conn, $sql_comp);

// ดึงรายชื่อนักศึกษาที่ยัง "ไม่มี" บริษัท (เอาไว้แสดงใน Dropdown)
$sql_free = "SELECT s.student_id, u.full_name 
             FROM tb_students s 
             JOIN tb_users u ON s.user_id = u.user_id 
             WHERE s.company_id IS NULL OR s.company_id = 0";
$res_free = mysqli_query($conn, $sql_free);
$free_students = [];
while ($row_free = mysqli_fetch_assoc($res_free)) {
    $free_students[] = $row_free;
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
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: #FD5D00; font-weight: bold; }
        .logout-item { position: absolute; bottom: 25px; left: 20px; right: 20px; }
        .btn-logout { color: #FC8181 !important; border: 1px solid rgba(252, 129, 129, 0.3) !important; border-radius: 10px; background-color: rgba(252, 129, 129, 0.05); }
        .btn-logout:hover { color: white !important; background-color: #E53E3E !important; }
        .main-content { margin-left: 250px; padding: 30px 40px; }
        .card-custom { border-radius: 15px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        .btn-orange { background-color: #FD5D00; color: white; border: none; }
        .btn-orange:hover { background-color: #E05A00; color: white; }
        
        .student-badge {
            display: inline-block;
            background-color: #FFF3EB;
            color: #FD5D00;
            border: 1px solid #FCD2B6;
            padding: 5px 12px;
            border-radius: 8px;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }
        .remove-student-btn { color: #E53E3E; margin-left: 8px; cursor: pointer; text-decoration: none; }
        .remove-student-btn:hover { color: #9B2C2C; }
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
            <li class="nav-item"><a href="manage_companies.php" class="nav-link active"><i class="fa-solid fa-building me-2"></i> จัดการสถานประกอบการ</a></li>
            <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fa-solid fa-chart-line me-2"></i> รายงานสถิติภาพรวม</a></li>
        </ul>
    </div>
    <div class="logout-item">
        <a href="../logout.php" class="nav-link text-center btn-logout fw-bold"><i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ</a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">จัดการสถานประกอบการ</h3>
            <p class="text-muted m-0">ผู้ดูแลระบบ: เจ้าหน้าที่คณะเทคโนโลยีสารสนเทศ</p>
        </div>
        <button class="btn btn-orange rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
            <i class="fa-solid fa-plus me-2"></i> เพิ่มสถานประกอบการ
        </button>
    </div>

    <!-- ตารางบริษัท -->
    <div class="card card-custom p-4 bg-white">
        <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-building text-orange me-2"></i> รายชื่อบริษัท / หน่วยงานรับฝึกงาน</h5>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 25%;">ชื่อสถานประกอบการ</th>
                        <th style="width: 15%;">เบอร์โทรศัพท์</th>
                        <th style="width: 35%;">นักศึกษาฝึกงานในความดูแล</th>
                        <th style="width: 10%; text-align: center;">จำนวน</th>
                        <th style="width: 10%; text-align: center;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($res_comp) > 0): 
                        $i = 1;
                        while($comp = mysqli_fetch_assoc($res_comp)): 
                            $comp_id = $comp['company_id'];
                            
                            // ดึงรายชื่อเด็กที่อยู่ในบริษัทนี้
                            $sql_in_comp = "SELECT s.student_id, u.full_name 
                                            FROM tb_students s 
                                            JOIN tb_users u ON s.user_id = u.user_id 
                                            WHERE s.company_id = '$comp_id'";
                            $res_in_comp = mysqli_query($conn, $sql_in_comp);
                            $std_count = mysqli_num_rows($res_in_comp);
                    ?>
                    <tr>
                        <td class="text-muted"><?php echo $i++; ?></td>
                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($comp['company_name']); ?></td>
                        <td class="text-muted small"><?php echo htmlspecialchars($comp['phone']) ?: '-'; ?></td>
                        <td>
                            <?php if($std_count > 0): ?>
                                <?php while($std = mysqli_fetch_assoc($res_in_comp)): ?>
                                    <div class="student-badge">
                                        <i class="fa-solid fa-user-graduate me-1"></i> 
                                        <?php echo $std['full_name']; ?> <span class="small text-muted">(<?php echo $std['student_id']; ?>)</span>
                                        <!-- ปุ่มถอดนักศึกษาออกจากบริษัท -->
                                        <a href="manage_companies.php?remove_student_id=<?php echo $std['student_id']; ?>" 
                                           class="remove-student-btn" title="นำออก" 
                                           onclick="return confirm('ถอดนักศึกษาออกจากบริษัทนี้?');">
                                           <i class="fa-solid fa-xmark"></i>
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <span class="text-muted small">- ยังไม่มีนักศึกษา -</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary rounded-pill px-3"><?php echo $std_count; ?> คน</span>
                        </td>
                        <td class="text-center text-nowrap">
                            <!-- ปุ่มจัดสรรนักศึกษา -->
                            <button class="btn btn-sm btn-outline-warning text-dark fw-bold rounded-pill px-3 me-1" 
                                    data-bs-toggle="modal" data-bs-target="#assignModal<?php echo $comp_id; ?>">
                                <i class="fa-solid fa-user-plus"></i> เลือกนักศึกษา
                            </button>
                            <!-- ปุ่มลบบริษัท -->
                            <a href="manage_companies.php?delete_id=<?php echo $comp_id; ?>" 
                               class="btn btn-sm btn-outline-danger rounded-circle" 
                               onclick="return confirm('⚠️ ยืนยันการลบบริษัท <?php echo htmlspecialchars($comp['company_name']); ?>? ข้อมูลที่ผูกกับนักศึกษาจะถูกยกเลิก');">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </td>
                    </tr>

                    <!-- Modal: จัดสรรนักศึกษาเข้าบริษัท -->
                    <div class="modal fade" id="assignModal<?php echo $comp_id; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0">
                                <div class="modal-header border-0 bg-light">
                                    <h6 class="modal-title fw-bold"><i class="fa-solid fa-building text-orange me-2"></i> จัดสรรนักศึกษา</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="manage_companies.php" method="POST">
                                    <div class="modal-body p-4 text-start">
                                        <p class="small text-muted mb-3">
                                            เลือกนักศึกษาเพื่อเข้าฝึกงานที่ <strong><?php echo htmlspecialchars($comp['company_name']); ?></strong>
                                        </p>
                                        <input type="hidden" name="company_id" value="<?php echo $comp_id; ?>">
                                        <select class="form-select" name="student_id" required>
                                            <option value="">-- เลือกนักศึกษา (ที่ยังไม่มีบริษัท) --</option>
                                            <?php foreach($free_students as $free): ?>
                                                <option value="<?php echo $free['student_id']; ?>">
                                                    <?php echo $free['student_id'] . " - " . $free['full_name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="modal-footer border-0">
                                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                                        <button type="submit" name="assign_student" class="btn btn-orange rounded-pill px-4 fw-bold">บันทึกข้อมูล</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">ยังไม่มีข้อมูลสถานประกอบการ</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: เพิ่มบริษัทใหม่ -->
<div class="modal fade" id="addCompanyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 bg-light">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus text-orange me-2"></i> เพิ่มสถานประกอบการ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="manage_companies.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ชื่อสถานประกอบการ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="company_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">เบอร์โทรศัพท์ติดต่อ</label>
                        <input type="text" class="form-control" name="phone" placeholder="เช่น 02-xxx-xxxx">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" name="add_company" class="btn btn-orange rounded-pill px-4 fw-bold">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>