<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::startSession();
$role = Auth::getRole();
?>
<div class="top-header">
    Hotline: 0931 999 309 | Giờ làm việc: T2-T7 (7:00 - 17:00), CN (7:00 - 11:00)
</div>
<div class="navbar">
    <a href="/index.php" class="navbar-brand">
        <span class="navbar-mark">PK</span>
        <span>
            Hưng Định
            <span class="brand-sub">Phòng khám đa khoa</span>
        </span>
    </a>

    <button class="nav-toggle" type="button">Menu</button>

    <div class="nav-links">
        <a href="/index.php">Trang Chủ</a>

        <?php if ($role === 'patient'): ?>
            <!-- Menu dành cho Bệnh nhân -->
            <a href="/index.php?action=booking">Đặt lịch khám</a>
            <a href="/index.php?action=my_appointments">Lịch Hẹn Của Tôi</a>
            <a href="/index.php?action=my_appointments#ho-so">Bệnh án</a>
            <a href="/index.php?action=my_appointments#hoa-don">Hóa đơn</a>
        
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
            <a href="/index.php?action=receptionist_appointments">Lịch hẹn</a>
            <a href="/index.php?action=nurse_medicines">Kho thuốc</a>
        <?php endif; ?>

        <?php if (Auth::isLoggedIn()): ?>
            <a href="/index.php?action=account">Tài khoản</a>
            <span class="nav-user"><?php echo htmlspecialchars((string) ($_SESSION['username'] ?? '')); ?></span>
            <a href="/index.php?action=logout" class="nav-logout">Đăng xuất</a>
        <?php else: ?>
            <a class="nav-login" href="/index.php?action=login&next=booking" data-open="login">Đăng nhập</a>
            <a class="nav-register" href="/index.php?action=register" data-open="register">Đăng ký</a>
            <a href="/index.php?action=booking">Đặt lịch khám</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!Auth::isLoggedIn()): ?>
<div class="modal" id="auth-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close>Đóng</button>
        <div class="modal-tabs">
            <button class="is-on" type="button" data-tab="login">Đăng nhập</button>
            <button type="button" data-tab="register">Đăng ký</button>
        </div>

        <div id="panel-login">
            <h2>Đăng nhập</h2>
            <form action="/index.php?action=do_login" method="POST">
                <input type="hidden" name="next" value="booking">
                <input type="hidden" name="doctor_id" value="<?php echo (int) ($_GET['doctor_id'] ?? 0); ?>">
                <label>
                    Tên đăng nhập
                    <input type="text" name="username" required>
                </label>
                <label>
                    Mật khẩu
                    <input type="password" name="password" required>
                </label>
                <p><button class="btn-navy" type="submit">Đăng nhập</button></p>
            </form>
        </div>

        <div id="panel-register" class="is-hidden">
            <h2>Đăng ký bệnh nhân</h2>
            <p class="lead">Điền đủ ngày sinh, giới tính, CCCD và BHYT rồi mới đặt lịch được.</p>
            <form action="/index.php?action=do_register" method="POST">
                <div class="form-row">
                    <label>Tên đăng nhập <input type="text" name="username" required></label>
                    <label>Mật khẩu <input type="password" name="password" required minlength="5"></label>
                </div>
                <label>Họ tên <input type="text" name="full_name" required></label>
                <div class="form-row">
                    <label>Ngày sinh <input type="date" name="date_of_birth" required></label>
                    <label>
                        Giới tính
                        <select name="gender" required>
                            <option value="">Chọn</option>
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </label>
                </div>
                <div class="form-row">
                    <label>Số điện thoại (10 số) <input type="tel" name="phone" required pattern="\d{10}" maxlength="10"></label>
                    <label>Email <input type="email" name="email" required></label>
                </div>
                <label>Địa chỉ <input type="text" name="address" required></label>
                <div class="form-row">
                    <label>CCCD (12 số) <input type="text" name="cccd" required pattern="\d{12}" maxlength="12"></label>
                    <label>Số BHYT <input type="text" name="bhyt" required pattern="\d{10,15}" maxlength="15"></label>
                </div>
                <p><button class="btn-navy" type="submit">Tạo tài khoản</button></p>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container">
<?php if (isset($_GET['deleted'])): ?>
    <p class="alert alert-ok">Đã xóa tài khoản.</p>
<?php endif; ?>

<?php if (!empty($_SESSION['flash'])): ?>
    <p class="alert<?php echo !empty($_SESSION['flash_ok']) ? ' alert-ok' : ''; ?>">
        <?php echo htmlspecialchars((string) $_SESSION['flash']); ?>
    </p>
    <?php unset($_SESSION['flash'], $_SESSION['flash_ok']); ?>
<?php endif; ?>