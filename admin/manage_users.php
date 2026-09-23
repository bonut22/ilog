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
// 📌 1. ระบบจัดการเพิ่มผู้ใช้งานใหม่ (Create)
// ==========================================
if (isset($_POST['add_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // เช็คว่า Username ซ้ำไหม
    $check_sql = "SELECT user_id FROM tb_users WHERE username = '$username'";
    $check_query = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_query) > 0) {
        echo "<script>alert('❌ Username นี้มีในระบบแล้ว กรุณาใช้ชื่ออื่น'); window.history.back();</script>";
        exit();
    } else {
        // เพิ่มข้อมูลลงตาราง tb_users
        $sql_add = "INSERT INTO tb_users (username, password, full_name, email, role) 
                    VALUES ('$username', '$password', '$full_name', '$email', '$role')";
        
        if (mysqli_query($conn, $sql_add)) {
            $new_user_id = mysqli_insert_id($conn); // ดึง ID ที่เพิ่งสร้าง

            // ถ้าเป็น "นักศึกษา" ให้เพิ่มข้อมูลลงตาราง tb_students ด้วย
            if ($role === 'student') {
                $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
                $major = mysqli_real_escape_string($conn, $_POST['major']);
                $sql_std = "INSERT INTO tb_students (student_id, user_id, major) VALUES ('$student_id', '$new_user_id', '$major')";
                mysqli_query($conn, $sql_std);
            }
            echo "<script>alert('✅ เพิ่มบัญชีผู้ใช้สำเร็จ!'); window.location='manage_users.php';</script>";
        }
    }
}

// ==========================================
// 📌 2. ระบบลบผู้ใช้งาน (Delete)
// ==========================================
if (isset($_GET['delete_id'])) {
    $del_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    // ลบข้อมูลนักศึกษาก่อน (ถ้ามี)
    mysqli_query($conn, "DELETE FROM tb_students WHERE user_id = '$del_id'");
    // ลบข้อมูล user
    mysqli_query($conn, "DELETE FROM tb_users WHERE user_id = '$del_id'");
    echo "<script>alert('🗑️ ลบผู้ใช้งานเรียบร้อยแล้ว'); window.location='manage_users.php';</script>";
}
// ==========================================
// 📌 ระบบแก้ไขข้อมูลผู้ใช้งาน (Update)
// ==========================================
if (isset($_POST['edit_user'])) {
    $edit_user_id = mysqli_real_escape_string($conn, $_POST['edit_user_id']);
    $edit_full_name = mysqli_real_escape_string($conn, $_POST['edit_full_name']);
    $edit_email = mysqli_real_escape_string($conn, $_POST['edit_email']);
    $edit_password = mysqli_real_escape_string($conn, $_POST['edit_password']);

    // สร้างคำสั่ง SQL สำหรับอัปเดตข้อมูลในตาราง tb_users
    // ถ้ามีการกรอกรหัสผ่านใหม่ ก็ให้อัปเดตรหัสผ่านด้วย
    if (!empty($edit_password)) {
        $sql_edit = "UPDATE tb_users SET full_name = '$edit_full_name', email = '$edit_email', password = '$edit_password' WHERE user_id = '$edit_user_id'";
    } else {
        $sql_edit = "UPDATE tb_users SET full_name = '$edit_full_name', email = '$edit_email' WHERE user_id = '$edit_user_id'";
    }

    if (mysqli_query($conn, $sql_edit)) {
        echo "<script>alert('✅ แก้ไขข้อมูลสำเร็จ!'); window.location='manage_users.php';</script>";
        exit();
    } else {
         $error_msg = addslashes(mysqli_error($conn));
         echo "<script>alert('❌ แก้ไขข้อมูลไม่สำเร็จ สาเหตุ: $error_msg'); window.history.back();</script>";
         exit();
    }
}
// ==========================================
// 📌 3. ระบบดึงข้อมูลผู้ใช้ทั้งหมดมาแสดง (Read)
// ==========================================
$sql_users = "SELECT u.*, s.student_id, s.major 
              FROM tb_users u 
              LEFT JOIN tb_students s ON u.user_id = s.user_id 
              ORDER BY 
                CASE u.role 
                    WHEN 'admin' THEN 1 
                    WHEN 'advisor' THEN 2 
                    WHEN 'mentor' THEN 3 
                    WHEN 'student' THEN 4 
                END ASC, u.user_id DESC";
$res_users = mysqli_query($conn, $sql_users);
$total_users = mysqli_num_rows($res_users);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบัญชีผู้ใช้ - ILOG</title>
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
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar p-3 d-flex flex-column justify-content-between">
    <div>
        <div class="text-center my-4">
            <img src="../assets/images/logo.jpg" alt="Logo" class="img-fluid rounded mb-2" style="max-width: 60px;">
            <h6 class="m-0 small fw-bold text-white">คณะเทคโนโลยีสารสนเทศ</h6>
            <p class="small text-secondary mb-0">มรภ.เทพสตรี</p>
        </div>
        <hr class="text-secondary">
        <ul class="nav flex-column mt-4">
            <li class="nav-item"><a href="manage_users.php" class="nav-link active"><i class="fa-solid fa-user-gear me-2"></i> จัดการบัญชีผู้ใช้</a></li>
            <li class="nav-item"><a href="manage_companies.php" class="nav-link"><i class="fa-solid fa-building me-2"></i> จัดการสถานประกอบการ</a></li>
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
            <h3 class="fw-bold text-dark mb-1">ระบบบริหารจัดการหลังบ้าน (ILOG)</h3>
            <p class="text-muted m-0">ผู้ดูแลระบบ: เจ้าหน้าที่คณะเทคโนโลยีสารสนเทศ</p>
        </div>
        <button class="btn btn-orange rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fa-solid fa-user-plus me-2"></i> เพิ่มบัญชีผู้ใช้ใหม่
        </button>
    </div>

    <!-- การ์ดสรุป -->
    <div class="card card-custom p-4 bg-white mb-4 d-inline-block" style="min-width: 250px;">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <p class="text-muted fw-bold mb-1 small">บัญชีผู้ใช้ทั้งหมดในระบบ</p>
                <h2 class="fw-bold text-dark m-0"><?php echo $total_users; ?> <span class="fs-6 text-muted fw-normal">บัญชี</span></h2>
            </div>
            <div class="rounded-circle bg-warning bg-opacity-10 p-3 text-warning ms-4">
                <i class="fa-solid fa-users fs-3"></i>
            </div>
        </div>
    </div>

    <!-- ตารางผู้ใช้งาน -->
    <div class="card card-custom p-4 bg-white">
        <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-users-gear text-orange me-2"></i> รายชื่อผู้ใช้งานและสิทธิ์ในระบบ</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Username</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>อีเมล</th>
                        <th>รายละเอียดสมาชิก</th>
                        <th class="text-center">สิทธิ์ผู้ใช้</th>
                        <th class="text-center">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($res_users)): ?>
                    <tr>
                        <td class="fw-bold"><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td class="small text-muted"><?php echo htmlspecialchars($row['email'] ?? '-'); ?></td>
                        <td class="small text-muted">
                            <?php 
                                if($row['role'] == 'student') echo 'รหัสนักศึกษา: ' . htmlspecialchars($row['student_id'] ?? '-') . '<br>สาขา: ' . htmlspecialchars($row['major'] ?? '-');
                                elseif($row['role'] == 'mentor') echo 'พี่เลี้ยงประจำสถานประกอบการ';
                                elseif($row['role'] == 'advisor') echo 'อาจารย์นิเทศก์';
                                else echo 'ผู้ดูแลระบบส่วนกลาง';
                            ?>
                        </td>
                        <td class="text-center">
                            <?php 
                                if($row['role'] == 'admin') echo '<span class="badge bg-danger rounded-pill px-3">แอดมิน</span>';
                                elseif($row['role'] == 'advisor') echo '<span class="badge bg-primary rounded-pill px-3">อาจารย์</span>';
                                elseif($row['role'] == 'mentor') echo '<span class="badge bg-warning text-dark rounded-pill px-3">พี่เลี้ยง</span>';
                                else echo '<span class="badge bg-success rounded-pill px-3">นักศึกษา</span>';
                            ?>
                        </td>
                        <td class="text-center text-nowrap">
                            <!-- ปุ่มแก้ไข -->
                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $row['user_id']; ?>" title="แก้ไขข้อมูล">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            
                            <!-- ปุ่มลบ -->
                            <?php if($row['role'] !== 'admin'): // ป้องกันแอดมินลบตัวเอง ?>
                                <a href="manage_users.php?delete_id=<?php echo $row['user_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('⚠️ ยืนยันการลบบัญชี <?php echo htmlspecialchars($row['full_name']); ?> หรือไม่?');" title="ลบข้อมูล">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-secondary" disabled><i class="fa-solid fa-ban"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- ========================================== -->
                    <!-- Modal: แก้ไขผู้ใช้งาน -->
                    <!-- ========================================== -->
                    <div class="modal fade" id="editUserModal<?php echo $row['user_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0">
                                <div class="modal-header border-0 bg-light">
                                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen text-primary me-2"></i> แก้ไขข้อมูลผู้ใช้</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="manage_users.php" method="POST">
                                    <div class="modal-body p-4 text-start">
                                        <input type="hidden" name="edit_user_id" value="<?php echo $row['user_id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted">Username (ไม่สามารถแก้ไขได้)</label>
                                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($row['username']); ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">ชื่อ - นามสกุล (เต็ม) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="edit_full_name" value="<?php echo htmlspecialchars($row['full_name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">อีเมล</label>
                                            <input type="email" class="form-control" name="edit_email" value="<?php echo htmlspecialchars($row['email']); ?>">
                                        </div>
                                        <div class="mb-3 border-top pt-3 mt-3">
                                            <label class="form-label small fw-bold text-danger">ตั้งรหัสผ่านใหม่ (หากไม่ต้องการเปลี่ยน ให้เว้นว่างไว้)</label>
                                            <input type="password" class="form-control" name="edit_password" placeholder="กรอกรหัสผ่านใหม่...">
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0">
                                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                                        <button type="submit" name="edit_user" class="btn btn-primary rounded-pill px-4 fw-bold">บันทึกการแก้ไข</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- สิ้นสุด Modal แก้ไข -->
                    
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- Modal: เพิ่มผู้ใช้ใหม่ -->
<!-- ========================================== -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 bg-light">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus text-orange me-2"></i> เพิ่มบัญชีผู้ใช้ใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="manage_users.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">สิทธิ์ผู้ใช้งาน (Role) <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" id="roleSelect" required onchange="toggleStudentFields()">
                            <option value="student">นักศึกษาฝึกงาน (Student)</option>
                            <option value="mentor">พี่เลี้ยงฝึกงาน (Mentor)</option>
                            <option value="advisor">อาจารย์นิเทศก์ (Advisor)</option>
                            <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                        </select>
                    </div>

                    <!-- ส่วนข้อมูลพิเศษสำหรับนักศึกษา (จะซ่อนถ้าไม่ได้เลือกนักศึกษา) -->
                    <div id="studentFields" class="bg-light p-3 rounded-3 mb-3 border">
                        <div class="mb-2">
                            <label class="form-label small fw-bold">รหัสนักศึกษา <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="student_id" id="student_id">
                        </div>
                        <div>
                            <label class="form-label small fw-bold">สาขาวิชา</label>
                            <input type="text" class="form-control" name="major" value="เทคโนโลยีสารสนเทศ">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ชื่อ - นามสกุล (เต็ม) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">อีเมล</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" name="add_user" class="btn btn-orange rounded-pill px-4 fw-bold">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // สคริปต์ซ่อน-โชว์ ช่องรหัสนักศึกษา
    function toggleStudentFields() {
        var role = document.getElementById('roleSelect').value;
        var stdFields = document.getElementById('studentFields');
        var stdInput = document.getElementById('student_id');
        
        if (role === 'student') {
            stdFields.style.display = 'block';
            stdInput.required = true;
        } else {
            stdFields.style.display = 'none';
            stdInput.required = false;
        }
    }
    // เรียกใช้งานตอนโหลดหน้าครั้งแรก
    toggleStudentFields();
</script>
</body>
</html>