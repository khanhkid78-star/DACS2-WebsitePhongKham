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


        <?php if (Auth::isLoggedIn()): ?>
        <div class="nav-links">
            <span class="bell-wrap">
                <button class="nav-bell" id="nav-bell" type="button" aria-label="Thông báo">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9"/><path d="M10 20a2 2 0 0 0 4 0"/></svg>
                    <span class="bell-count" id="bell-count"></span>
                </button>
                <div class="bell-panel" id="bell-panel"></div>
            </span>
            <span class="nav-user"><?php echo htmlspecialchars((string) ($_SESSION['username'] ?? '')); ?></span>
            <a href="/index.php?action=logout<?php echo Auth::currentTab() !== '' ? '&tab=' . urlencode(Auth::currentTab()) : ''; ?>" class="nav-logout">Đăng xuất</a>
        </div>
    <?php else: ?>
        <div class="nav-links">
            <a href="/index.php">Trang Chủ</a>
            <a href="/index.php?action=booking">Đặt lịch khám</a>
            <a class="nav-login" href="/index.php?action=login&next=booking" data-open="login">Đăng nhập</a>
            <a class="nav-register" href="/index.php?action=register" data-open="register">Đăng ký</a>
        </div>
    <?php endif; ?>
</div>

<?php if (Auth::isLoggedIn()): ?>
<button class="side-toggle" id="side-toggle" type="button" aria-label="Đóng mở mục lục"><span></span></button>
<aside class="side-nav" id="side-nav">
    <a href="/index.php">Trang Chủ</a>
    <?php if ($role === 'patient'): ?>
        <a href="/index.php?action=booking">Đặt lịch khám</a>
        <a href="/index.php?action=my_appointments">Lịch Hẹn Của Tôi</a>
        <a href="/index.php?action=my_appointments#ho-so">Bệnh án</a>
        <a href="/index.php?action=my_appointments#hoa-don">Hóa đơn</a>
        <a href="/index.php?action=account">Tài khoản</a>
    <?php elseif ($role === 'receptionist'): ?>
        <a href="/index.php?action=receptionist_appointments">Quản Lý Lịch Hẹn</a>
        <a href="/index.php?action=invoices">Quản Lý Hóa Đơn</a>
        <a href="/index.php?action=staff&kind=patient">Bệnh nhân</a>
        <a href="/index.php?action=account">Tài khoản</a>
    <?php elseif ($role === 'doctor'): ?>
        <a href="/index.php?action=doctor_records">Hồ Sơ Bệnh Án</a>
        <a href="/index.php?action=account">Tài khoản</a>
    <?php elseif ($role === 'nurse'): ?>
        <a href="/index.php?action=nurse_medicines">Quản Lý Kho Thuốc</a>
        <a href="/index.php?action=account">Tài khoản</a>
    <?php elseif ($role === 'admin'): ?>
        <a href="/index.php?action=admin_stats">Thống kê</a>
        <a href="/index.php?action=staff">Nhân sự</a>
        <a href="/index.php?action=doctor_records">Bệnh Án</a>
        <a href="/index.php?action=invoices">Hóa Đơn</a>
        <a href="/index.php?action=receptionist_appointments">Lịch hẹn</a>
        <a href="/index.php?action=nurse_medicines">Kho thuốc</a>
        <a href="/index.php?action=admin_users">Tài khoản</a>
    <?php endif; ?>
</aside>
<script>
(function () {
    var key = "pk_side";
    var btn = document.getElementById("side-toggle");
    var side = document.getElementById("side-nav");
    if (!btn || !side) {
        document.body.classList.remove("side-open");
        return;
    }
    function place() {
        var bar = document.querySelector(".navbar");
        if (!bar) return;
        var bottom = Math.max(0, bar.getBoundingClientRect().bottom);
        side.style.top = bottom + "px";
        side.style.height = "calc(100vh - " + bottom + "px)";
        btn.style.top = (bottom + 10) + "px";
    }
    function preview(on) {
        document.body.classList.toggle("side-hover", on);
    }
    try {
        if (localStorage.getItem(key) === "1") document.body.classList.add("side-open");
    } catch (e) {}
    place();
    window.addEventListener("resize", place);
    window.addEventListener("scroll", place);
    [btn, side].forEach(function (el) {
        el.addEventListener("mouseenter", function () { preview(true); });
        el.addEventListener("mouseleave", function (e) {
            var to = e.relatedTarget;
            if (to && (to === btn || to === side || btn.contains(to) || side.contains(to))) return;
            preview(false);
        });
    });
    btn.addEventListener("click", function () {
        document.body.classList.toggle("side-open");
        try { localStorage.setItem(key, document.body.classList.contains("side-open") ? "1" : "0"); } catch (e) {}
    });
})();
</script>
<?php endif; ?>

<?php if (!Auth::isLoggedIn()): ?>
<div class="modal" id="auth-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close>Đóng</button>
        <div class="modal-tabs">
            <p class="alert" id="auth-error" hidden></p>
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