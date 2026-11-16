<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('patient', 'admin'));
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<div class="actions">
    <h2>Lịch hẹn của tôi</h2>
    <a class="btn-booking" href="/index.php?action=booking">Đặt lịch mới</a>
</div>
<p class="lead">Chỉ hủy được lịch đang chờ duyệt.</p>

<label class="mine-search">Tìm của tôi
    <input type="search" id="mine-filter" placeholder="Bác sĩ, triệu chứng, mã hóa đơn">
</label>

<?php if (empty($my_appointments)): ?>
    <div class="card">
        <p class="lead">Bạn chưa đăng ký lịch khám nào.</p>
        <a href="/index.php?action=booking">Đặt lịch khám ngay</a>
    </div>
<?php else: ?>
    <div class="table-wrap">
    <table>
        <tr>
            <th>Mã</th>
            <th>Bác sĩ</th>
            <th>Chuyên khoa</th>
            <th>Ngày giờ</th>
            <th>Lý do</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
        </tr>
        <tbody id="mine-appts">
        <?php foreach ($my_appointments as $app): ?>
        <tr>
            <td>#<?php echo (int) $app['appointment_id']; ?></td>
            <td>BS. <?php echo htmlspecialchars((string) $app['doctor_name']); ?></td>
            <td><?php echo htmlspecialchars((string) $app['specialty']); ?></td>
            <td><?php echo date('H:i - d/m/Y', strtotime($app['appointment_date'])); ?></td>
            <td><?php echo htmlspecialchars((string) $app['note']); ?></td>
            <td>
                <?php if ($app['status'] === 'Pending'): ?>
                    <span class="badge badge-pending">Chờ lễ tân duyệt</span>
                <?php elseif ($app['status'] === 'Scheduled'): ?>
                    <span class="badge badge-ok">Đã lên lịch</span>
                <?php elseif ($app['status'] === 'Completed'): ?>
                    <span class="badge badge-done">Đã khám xong</span>
                <?php elseif ($app['status'] === 'Cancelled'): ?>
                    <span class="badge badge-cancel">Đã hủy</span>
                <?php else: ?>
                    <?php echo htmlspecialchars((string) $app['status']); ?>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($app['status'] === 'Pending'): ?>
                    <form action="/index.php?action=cancel_appointment" method="POST" onsubmit="return confirm('Hủy lịch đang chờ này?');">
                        <input type="hidden" name="appointment_id" value="<?php echo (int) $app['appointment_id']; ?>">
                        <button class="btn-ghost" type="submit">Hủy lịch</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>

<section class="section" id="ho-so">
    <h2>Bệnh án của tôi</h2>
    <?php if (empty($my_records)): ?>
        <p class="lead">Chưa có bệnh án.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <tr>
                <th>Ngày khám</th>
                <th>Bác sĩ</th>
                <th>Triệu chứng</th>
                <th>Chẩn đoán</th>
                <th>Kết luận</th>
                <th>Đơn thuốc</th>
            </tr>
            <tbody id="mine-records">
            <?php foreach ($my_records as $rec): ?>
            <tr>
                <td><?php echo date('H:i d/m/Y', strtotime($rec['visit_date'])); ?></td>
                <td><?php echo htmlspecialchars((string) $rec['doctor_name']); ?></td>
                <td><?php echo htmlspecialchars((string) $rec['symptoms']); ?></td>
                <td><?php echo htmlspecialchars((string) $rec['diagnosis']); ?></td>
                <td><?php echo htmlspecialchars((string) $rec['conclusion']); ?></td>
                <td><?php echo $rec['prescription_text'] ? htmlspecialchars($rec['prescription_text']) : 'Chưa kê'; ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</section>

<section class="section" id="hoa-don">
    <h2>Hóa đơn của tôi</h2>
    <?php if (empty($my_invoices)): ?>
        <p class="lead">Chưa có hóa đơn.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <tr>
                <th>Mã</th>
                <th>Tổng tiền</th>
                <th>Phương thức</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
            </tr>
            <tbody id="mine-invoices">
            <?php foreach ($my_invoices as $inv): ?>
            <tr>
                <td>#<?php echo (int) $inv['invoice_id']; ?></td>
                <td><?php echo number_format((float) $inv['total_amount']); ?> đ</td>
                <td><?php echo htmlspecialchars((string) $inv['payment_method']); ?></td>
                <td>
                    <?php if ($inv['status'] === 'Paid'): ?>
                        <span class="badge badge-ok">Đã thanh toán</span>
                    <?php else: ?>
                        <span class="badge badge-pending">Chưa thanh toán</span>
                    <?php endif; ?>
                </td>
                <td><?php echo date('d/m/Y H:i', strtotime($inv['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>