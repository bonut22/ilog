<?php
// advisor/advisor_students.php
session_start();
// include('../config/db_connect.php'); // เปิดคอมเมนต์เมื่อเชื่อมต่อฐานข้อมูลจริง

$advisor_name = $_SESSION['full_name'] ?? "อาจารย์ธนชัย ปฐมรัตน์";

$students = [
    [
        'student_id' => '66124630101',
        'name' => 'นายพัฐธนนท์ ชื่นอารมณ์',
        'major' => 'เทคโนโลยีสารสนเทศ',
        'company' => 'บริษัท เทคโนโลยี ดิจิทัล จำกัด',
        'mentor' => 'คุณสมชาย ใจดี',
        'approved_hours' => 120
    ],
    [
        'student_id' => '66124630102',
        'name' => 'นางสาวสมศรี เรียนดี',
        'major' => 'เทคโนโลยีสารสนเทศ',
        'company' => 'บริษัท อินโนเวชัน ซอฟต์แวร์',
        'mentor' => 'คุณวิชัย พัฒนา',
        'approved_hours' => 450
    ],
    [
        'student_id' => '66124630103',
        'name' => 'นายสมศักดิ์ มุ่งมั่น',
        'major' => 'เทคโนโลยีสารสนเทศ',
        'company' => 'สถาบันวิจัยไอที มรภ.เทพสตรี',
        'mentor' => 'คุณอารี มีสุข',
        'approved_hours' => 80
    ]
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>นักศึกษาในความดูแล - ILOG</title>
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
                <a href="advisor_students.php" class="nav-link active"><i class="fa-solid fa-users me-3"></i> นักศึกษาในความดูแล</a>
            </li>
            <li class="nav-item">
                <a href="advisor_supervision.php" class="nav-link"><i class="fa-solid fa-clipboard-check me-3"></i> บันทึกการนิเทศงาน</a>
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
            <h4 class="fw-bold m-0">รายชื่อนักศึกษาในความดูแล</h4>
            <p class="text-muted m-0">อาจารย์นิเทศก์: <?php echo $advisor_name; ?></p>
        </div>
        <span class="badge bg-primary px-3 py-2 rounded-pill">ปีการศึกษา 2569</span>
    </div>

    <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>รหัสนักศึกษา</th>
                        <th>ชื่อ - นามสกุล</th>
                        <th>สาขาวิชา</th>
                        <th>สถานประกอบการ</th>
                        <th>พี่เลี้ยงหลัก</th>
                        <th class="text-center">ชั่วโมงสะสม</th>
                        <th class="text-center">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $row): ?>
                    <tr>
                        <td class="fw-bold text-orange"><?php echo $row['student_id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['major']; ?></td>
                        <td><?php echo $row['company']; ?></td>
                        <td><?php echo $row['mentor']; ?></td>
                        <td class="text-center fw-bold text-success"><?php echo $row['approved_hours']; ?> / 480 ชม.</td>
                        <td class="text-center">
                            <a href="advisor_supervision.php?student_id=<?php echo $row['student_id']; ?>" class="btn btn-warning btn-sm text-dark rounded-pill px-3 fw-bold">
                                <i class="fa-solid fa-pen-to-square me-1"></i> นิเทศงาน
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