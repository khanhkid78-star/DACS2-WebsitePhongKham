<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::startSession();
$role = Auth::getRole();
?>
<div class="top-header">
    Hotline: 0931 999 309 | Giờ làm việc: T2-T7 (7:00 - 17:00), CN (7:00 - 11:00)
</div>
<div class="navbar">
    <a href="/index.php" class="navbar-brand">PHÒNG KHÁM ĐA KHOA PLACEHOLDER</a>
    <div class="nav-links">
        <a href="/index.php">Trang Chủ</a>

        <?php if ($role === 'patient'): ?>
            <!-- Menu dành cho Bệnh nhân -->
            <a href="/index.php?action=booking" class="btn-booking">Đặt Lịch Khám</a>
            <a href="/index.php?action=my_appointments">Lịch Hẹn Của Tôi</a>
        
        <?php elseif ($role === 'receptionist'): ?>
            <!-- Menu dành cho Lễ tân -->
            <a href="/index.php?action=receptionist_appointments">Quản Lý Lịch Hẹn</a>
            <a href="/index.php?action=invoices">Quản Lý Hóa Đơn</a>
        
        <?php elseif ($role === 'doctor'): ?>
            <!-- Menu dành cho Bác sĩ -->
            <a href="/index.php?action=doctor_records">Hồ Sơ Bệnh Án</a>
        
        <?php elseif ($role === 'nurse'): ?>
            <!-- Menu dành cho Y tá -->
            <a href="/index.php?action=nurse_medicines">Quản Lý Kho Thuốc</a>
        
        <?php elseif ($role === 'admin'): ?>
            <!-- Menu dành cho Admin -->
            <a href="/index.php?action=admin_users">Quản Lý Tài Khoản</a>
            <a href="/index.php?action=doctor_records">Bệnh Án</a>
            <a href="/index.php?action=invoices">Hóa Đơn</a>
        <?php endif; ?>

        <?php if (Auth::isLoggedIn()): ?>
            <a href="/index.php?action=logout" style="color: red;">Đăng Xuất (<?php echo $_SESSION['username']; ?>)</a>
        <?php else: ?>
            <a href="/index.php?action=login">Đăng Nhập</a>
            <a href="/index.php?action=booking" class="btn-booking">Đặt Lịch Khám</a>
        <?php endif; ?>
    </div>
</div>
<div class="container">