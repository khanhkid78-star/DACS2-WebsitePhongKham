<?php
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/../helpers/Auth.php';

Auth::startSession();
$role = Auth::getRole();

$specialties = array(
    'Nội tổng quát' => 'Khám định kỳ, sốt, đau bụng, huyết áp.',
    'Nhi khoa' => 'Khám trẻ em, hô hấp và tiêu hóa.',
    'Tim mạch' => 'Huyết áp, điện tim, theo dõi đau ngực.',
    'Sản phụ khoa' => 'Khám phụ khoa và theo dõi thai kỳ.',
    'Tai mũi họng' => 'Viêm họng, viêm xoang, nội soi.',
    'Da liễu' => 'Mụn, viêm da, dị ứng.',
    'Mắt' => 'Đo thị lực, khô mắt, viêm kết mạc.',
    'Răng hàm mặt' => 'Khám răng, lấy cao, vệ sinh răng miệng.',
);

$prices = array(
    'Khám nội tổng quát' => '250.000 đ',
    'Khám chuyên khoa' => '350.000 đ',
    'Siêu âm' => '200.000 đ',
    'Điện tim' => '150.000 đ',
);
?>

<?php if (isset($_GET['booked'])): ?>
    <p class="alert">Đã gửi yêu cầu. Lễ tân sẽ gọi xác nhận.</p>
<?php endif; ?>

<?php if (Auth::isLoggedIn()): ?>
    <section class="role-dash card">
        <p class="kicker"><?php echo htmlspecialchars(Auth::roleLabel($role)); ?></p>
        <h2>Xin chào, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        <?php if ($role === 'patient'): ?>
            <p class="lead">Đặt lịch và xem lịch của chính bạn. Khách chưa đăng nhập không đặt được.</p>
            <div class="actions">
                <a class="btn-booking" href="/index.php?action=booking">Đặt lịch khám</a>
                <a class="btn-ghost" href="/index.php?action=my_appointments">Lịch của tôi</a>
            </div>
        <?php elseif ($role === 'doctor'): ?>
            <p class="lead">Bạn ghi bệnh án và kê đơn cho lịch đã duyệt. Không duyệt lịch, không sửa kho thuốc.</p>
            <div class="actions">
                <a class="btn-navy" href="/index.php?action=doctor_records">Mở hồ sơ bệnh án</a>
            </div>
        <?php elseif ($role === 'nurse'): ?>
            <p class="lead">Bạn thêm, sửa và xóa thuốc trong kho. Đặt lịch và hóa đơn không thuộc quyền này.</p>
            <div class="actions">
                <a class="btn-navy" href="/index.php?action=nurse_medicines">Mở kho thuốc</a>
            </div>
        <?php elseif ($role === 'receptionist'): ?>
            <p class="lead">Bạn duyệt lịch, đánh dấu đã khám và lập hóa đơn. Bệnh nhân tự đặt sau khi đăng nhập.</p>
            <div class="actions">
                <a class="btn-navy" href="/index.php?action=receptionist_appointments">Duyệt lịch hẹn</a>
                <a class="btn-ghost" href="/index.php?action=invoices">Hóa đơn</a>
            </div>
        <?php elseif ($role === 'admin'): ?>
            <p class="lead">Bạn xem toàn bộ: tài khoản, lịch, bệnh án, thuốc, hóa đơn.</p>
            <div class="actions">
                <a class="btn-navy" href="/index.php?action=admin_users">Tài khoản</a>
                <a class="btn-ghost" href="/index.php?action=receptionist_appointments">Lịch hẹn</a>
                <a class="btn-ghost" href="/index.php?action=doctor_records">Bệnh án</a>
                <a class="btn-ghost" href="/index.php?action=invoices">Hóa đơn</a>
                <a class="btn-ghost" href="/index.php?action=nurse_medicines">Kho thuốc</a>
            </div>
        <?php endif; ?>
    </section>

    <?php if (Auth::isLoggedIn() && $role !== 'patient'): ?>
        <script>document.body.classList.add("staff-home");</script>
            <section class="section">
                <p class="lead"></p>
            </section>
        <?php require_once __DIR__ . '/partials/footer.php'; return; ?>
    <?php endif; ?>
<?php endif; ?>

<section class="hero">
    <div>
        <p class="kicker">Đa khoa · Phú Nhuận</p>
        <h1>Khám đúng chuyên khoa. Không chờ cả buổi.</h1>
        <p class="lead">
            Chọn bác sĩ, gửi triệu chứng. Lễ tân gọi xác nhận
            trước khi bạn đến.
        </p>
        <div class="actions">
            <a class="btn-booking" href="/index.php?action=booking"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg>
                Đặt lịch khám
            </a>
            <a class="btn-ghost" href="#chuyen-khoa"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v8M8 12h8"/></svg>
                Xem 8 chuyên khoa
            </a>
        </div>
        <dl class="stats">
            <div>
                <dt>Chuyên khoa</dt>
                <dd>8</dd>
            </div>
            <div>
                <dt>Bác sĩ</dt>
                <dd><?php echo count($doctors); ?></dd>
            </div>
            <div>
                <dt>Giờ chủ nhật</dt>
                <dd>11h</dd>
            </div>
        </dl>
    </div>
    <div class="hero-photo">
        <img src="/public/photos/lobby.jpg" alt="Sảnh tiếp đón phòng khám">
        <aside class="hero-card">
            <p class="kicker">Đường dây trực</p>
            <a href="tel:0931999309">0931 999 309</a>
            <p class="lead">Lễ tân trực trong giờ khám</p>
        </aside>
    </div>
</section>

<section class="section" id="chuyen-khoa">
    <h2>Tám chuyên khoa trong một địa chỉ</h2>
    <div class="grid-4">
        <?php foreach ($specialties as $name => $text): ?>
            <article class="specialty">
                <h3><?php echo htmlspecialchars($name); ?></h3>
                <p class="lead"><?php echo htmlspecialchars($text); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section" id="bac-si">
    <p class="kicker">Đội ngũ</p>
    <h2>Bác sĩ nhận khám tại Hưng Định</h2>
    <p class="lead">Dải bác sĩ tự chạy. Đưa chuột vào để dừng, kéo ngang để xem.</p>
    <?php if (empty($doctors)): ?>
        <p class="lead">Chưa có dữ liệu bác sĩ.</p>
    <?php else: ?>
        <div class="doc-marquee" id="doc-marquee">
            <div class="doc-track">
                <?php foreach ($doctors as $doc): ?>
                    <article class="doc-card">
                        <?php $shown = !empty($doc['has_photo']) && ($doc['has_photo'] === true || $doc['has_photo'] === 't' || $doc['has_photo'] === '1'); ?>
                        <?php if ($shown): ?>
                            <img src="/index.php?action=doctor_photo&id=<?php echo (int) $doc['doctor_id']; ?>" alt="">
                        <?php else: ?>
                            <span class="doc-fallback"><?php echo htmlspecialchars(mb_substr($doc['full_name'], 0, 1)); ?></span>
                        <?php endif; ?>
                        <p class="kicker"><?php echo htmlspecialchars((string) $doc['specialty']); ?></p>
                        <h3><?php echo htmlspecialchars((string) ($doc['degree'] ?? '')); ?> <?php echo htmlspecialchars($doc['full_name']); ?></h3>
                        <p class="lead"><?php echo htmlspecialchars($specialties[$doc['specialty']] ?? 'Khám và theo dõi theo chuyên khoa được phân công.'); ?></p>
                        <p class="doc-book">
                            <a class="btn-booking" href="/index.php?action=booking&doctor_id=<?php echo (int) $doc['doctor_id']; ?>">
                                Đặt với bác sĩ này
                            </a>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var box = document.getElementById("doc-marquee");
    if (!box) return;
    var track = box.querySelector(".doc-track");
    track.insertAdjacentHTML("beforeend", track.innerHTML);
    var paused = false;
    var dragging = false;
    var startX = 0;
    var startLeft = 0;
    setInterval(function () {
        if (paused || dragging) return;
        box.scrollLeft += 1;
        if (box.scrollLeft >= track.scrollWidth / 2) box.scrollLeft = 0;
    }, 16);
    box.addEventListener("mouseenter", function () { paused = true; });
    box.addEventListener("mouseleave", function () { paused = false; dragging = false; box.classList.remove("is-drag"); });
    box.addEventListener("mousedown", function (event) {
        if (event.target.closest("a, button")) return;
        dragging = true;
        box.classList.add("is-drag");
        startX = event.pageX;
        startLeft = box.scrollLeft;
    });
    window.addEventListener("mousemove", function (event) {
        if (!dragging) return;
        box.scrollLeft = startLeft - (event.pageX - startX);
    });
    window.addEventListener("mouseup", function () { dragging = false; box.classList.remove("is-drag"); });
    box.addEventListener("wheel", function (event) {
        box.scrollLeft += event.deltaY;
        event.preventDefault();
    }, { passive: false });
});
</script>

<section class="steps-band">
    <div class="grid-3">
        <article>
            <p class="step-no">01</p>
            <h2>Gửi yêu cầu</h2>
            <p>Lịch vào hàng chờ, trạng thái Chờ duyệt.</p>
        </article>
        <article>
            <p class="step-no">02</p>
            <h2>Lễ tân xác nhận</h2>
            <p>Duyệt thành Đã lên lịch, hoặc hủy nếu trùng ca.</p>
        </article>
        <article>
            <p class="step-no">03</p>
            <h2>Đến đúng giờ</h2>
            <p>Bác sĩ ghi bệnh án, y tá đối chiếu thuốc, lễ tân lập hóa đơn.</p>
        </article>
    </div>
</section>

<section class="section grid-2">
    <div>
        <h2>Giá tham khảo</h2>
        <?php foreach ($prices as $label => $amount): ?>
            <div class="price-row">
                <span><?php echo htmlspecialchars($label); ?></span>
                <strong><?php echo htmlspecialchars($amount); ?></strong>
            </div>
        <?php endforeach; ?>
        <p class="lead">Thuốc tính riêng theo đơn.</p>
    </div>
    <blockquote class="card">
        <h2>Vì sao cần đặt trước</h2>
        <p>
            Lễ tân thấy triệu chứng và giờ bạn chọn,
            nên không phải ngồi chờ ngoài sảnh cả buổi.
        </p>
    </blockquote>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>