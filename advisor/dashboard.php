<?php
// advisor/dashboard.php
session_start();
// include('../config/db_connect.php'); // เปิดคอมเมนต์เมื่อเชื่อมต่อฐานข้อมูลจริง

$advisor_name = $_SESSION['full_name'] ?? "อาจารย์ธนชัย ปฐมรัตน์";

$students = [
    [
        'student_id' => '66124630101',
        'name' => 'นายพัฐธนนท์ ชื่นอารมณ์',
        'company' => 'บริษัท เทคโนโลยี ดิจิทัล จำกัด',
        'approved_hours' => 120,
        'status' => 'มีรายการรอตรวจ'
    ],
    [
        'student_id' => '66124630102',
        'name' => 'นางสาวสมศรี เรียนดี',
        'company' => 'บริษัท อินโนเวชัน ซอฟต์แวร์',
        'approved_hours' => 450,
        'status' => 'ปกติ'
    ],
    [
        'student_id' => '66124630103',
        'name' => 'นายสมศักดิ์ มุ่งมั่น',
        'company' => 'สถาบันวิจัยไอที มรภ.เทพสตรี',
        'approved_hours' => 80,
        'status' => 'ชั่วโมงต่ำกว่าเกณฑ์'
    ]
];

$total_students = count($students);
$pending_reviews = 1;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบติดตามผลการฝึกงานสำหรับอาจารย์ (ILOG)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #F4F7FE; font-family: 'Sarabun', sans-serif; }
        .sidebar { background-color: #2D3748; min-height: 100vh; color: white; position: fixed; width: 250px; z-index: 1000; }
        .sidebar .nav-link { color: #CBD5E0; border-radius: 10px; margin-bottom: 8px; padding: 12px 18px; font-size: 15px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: #FD5D00; font-weight: bold; }
        
        /* สไตล์ปุ่มออกจากระบบแบบเดียวกับนักศึกษา */
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
        .bg-orange-light { background-color: #FFF0E6; }
        .card-stats { border-radius: 15px; border: none; }
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
                <a href="dashboard.php" class="nav-link active"><i class="fa-solid fa-chart-pie me-3"></i> หน้าหลักอาจารย์</a>
            </li>
            <li class="nav-item">
                <a href="advisor_students.php" class="nav-link"><i class="fa-solid fa-users me-3"></i> นักศึกษาในความดูแล</a>
            </li>
            <li class="nav-item">
                <a href="advisor_supervision.php" class="nav-link"><i class="fa-solid fa-clipboard-check me-3"></i> บันทึกการนิเทศงาน</a>
            </li>
        </ul>
    </div>

    <!-- ปุ่มออกจากระบบรูปแบบเดียวกับนักศึกษา -->
    <div class="logout-item">
        <a class="nav-link text-center fw-bold btn-logout" href="../logout.php">
            <i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ
        </a>
    </div>
</div>

<!-- เนื้อหาฝั่งขวา -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0">ระบบติดตามการฝึกงานออนไลน์ (ILOG)</h4>
            <p class="text-muted m-0">อาจารย์นิเทศก์: <?php echo $advisor_name; ?></p>
        </div>
        <span class="badge bg-primary px-3 py-2 rounded-pill">สิทธิ์อาจารย์นิเทศก์</span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-stats shadow-sm p-4 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1 fw-bold">นักศึกษาในความดูแลทั้งหมด</p>
                        <h2 class="fw-bold m-0 text-dark"><?php echo $total_students; ?> <span class="fs-5 text-muted fw-normal">คน</span></h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                        <i class="fa-solid fa-user-graduate fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card card-stats shadow-sm p-4 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1 fw-bold">สัปดาห์นี้มีรายงานรอตรวจสอบ</p>
                        <h2 class="fw-bold m-0 text-orange"><?php echo $pending_reviews; ?> <span class="fs-5 text-muted fw-normal">รายการ</span></h2>
                    </div>
                    <div class="bg-orange-light p-3 rounded-circle text-orange">
                        <i class="fa-solid fa-bell fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
        <h5 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-table-list text-orange me-2"></i> ตารางติดตามความคืบหน้านักศึกษาฝึกงาน</h5>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%;">รหัสนักศึกษา</th>
                        <th style="width: 20%;">ชื่อ-นามสกุล</th>
                        <th style="width: 25%;">สถานที่ฝึกงาน</th>
                        <th style="width: 20%;">ความคืบหน้าชั่วโมง (เกณฑ์ 480 ชม.)</th>
                        <th style="width: 10%; text-align: center;">สถานะ</th>
                        <th style="width: 10%; text-align: center;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $row): 
                        $percent = ($row['approved_hours'] / 480) * 100;
                        if($percent > 100) $percent = 100;
                    ?>
                    <tr>
                        <td class="fw-bold"><?php echo $row['student_id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td class="text-muted small"><?php echo $row['company']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="me-2 small fw-bold text-orange"><?php echo $row['approved_hours']; ?> ชม.</span>
                                <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar" style="width: <?php echo $percent; ?>%; background-color: #FD5D00;"></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if($row['status'] == 'มีรายการรอตรวจ'): ?>
                                <span class="badge bg-warning text-dark rounded-pill px-3">รอตรวจงาน</span>
                            <?php elseif($row['status'] == 'ชั่วโมงต่ำกว่าเกณฑ์'): ?>
                                <span class="badge bg-danger rounded-pill px-3">ชั่วโมงวิกฤต</span>
                            <?php else: ?>
                                <span class="badge bg-success rounded-pill px-3">ปกติ</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="advisor_supervision.php?student_id=<?php echo $row['student_id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                <i class="fa-solid fa-magnifying-glass me-1"></i> ดูรายละเอียด
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>