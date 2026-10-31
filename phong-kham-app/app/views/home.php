<?php
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';

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

<section class="hero">
    <div>
        <p class="kicker">Phòng khám đa khoa</p>
        <h1>Khám đúng chuyên khoa. Không chờ cả buổi.</h1>
        <p class="lead">
            Chọn bác sĩ, gửi triệu chứng. Lễ tân gọi xác nhận
            trước khi bạn đến. Hotline 0931 999 309.
        </p>
        <div class="actions">
            <a class="btn-booking" href="/index.php?action=booking">
                Đặt lịch khám
            </a>
            <a class="btn-ghost" href="#chuyen-khoa">
                Xem chuyên khoa
            </a>
        </div>
    </div>
    <aside class="card">
        <p class="kicker">Giờ làm việc</p>
        <p><strong>Thứ 2 – Thứ 7</strong><br>7:00 – 17:00</p>
        <p><strong>Chủ nhật</strong><br>7:00 – 11:00</p>
        <p class="lead">
            Giấy phép hoạt động số CRN0401597030.
            Địa chỉ: 128 Nguyễn Văn Trỗi, Phú Nhuận.
        </p>
    </aside>
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
    <h2>Bác sĩ đang nhận lịch</h2>
    <?php if (empty($doctors)): ?>
        <p class="lead">Chưa có dữ liệu bác sĩ.</p>
    <?php else: ?>
        <div class="grid-4">
            <?php foreach ($doctors as $doc): ?>
                <article class="doctor">
                    <h3>
                        BS. <?php echo htmlspecialchars($doc['full_name']); ?>
                    </h3>
                    <p><?php echo htmlspecialchars($doc['specialty']); ?></p>
                    <p>
                        <a href="/index.php?action=booking&doctor_id=<?php echo (int) $doc['doctor_id']; ?>">
                            Đặt với bác sĩ này
                        </a>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="section">
    <h2>Một lần đặt lịch đi qua ba bước</h2>
    <div class="grid-3">
        <article class="step">
            <h3>1. Gửi yêu cầu</h3>
            <p class="lead">Lịch mới ở trạng thái chờ duyệt.</p>
        </article>
        <article class="step">
            <h3>2. Lễ tân xác nhận</h3>
            <p class="lead">Duyệt thành đã lên lịch, hoặc hủy nếu trùng ca.</p>
        </article>
        <article class="step">
            <h3>3. Đến đúng giờ</h3>
            <p class="lead">
                Bác sĩ ghi bệnh án, y tá đối chiếu thuốc, lễ tân lập hóa đơn.
            </p>
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