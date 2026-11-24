<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('receptionist', 'admin'));
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<h2>Lập hóa đơn</h2>
<?php if (empty($billable)): ?>
    <p class="lead">Không có bệnh án nào chưa lập hóa đơn.</p>
<?php else: ?>
    <form class="card form-narrow" action="/index.php?action=create_invoice" method="POST">
        <label>
            Bệnh án
            <select name="record_id" id="bill-record" required>
                <option value="">Chọn bệnh án</option>
                <?php foreach ($billable as $row): ?>
                    <option value="<?php echo (int) $row['record_id']; ?>">
                        #<?php echo (int) $row['record_id']; ?>
                        — <?php echo htmlspecialchars((string) $row['patient_name']); ?>
                        — BS. <?php echo htmlspecialchars((string) $row['doctor_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div id="bill-preview" class="lead">Chọn bệnh án để xem người khám, chẩn đoán và đơn thuốc.</div>
        <label>
            Tổng tiền
            <input type="text" id="bill-total" value="" readonly>
        </label>
        <label>
            Phương thức
            <select name="payment_method" required>
                <option value="Tiền mặt">Tiền mặt</option>
                <option value="Chuyển khoản">Chuyển khoản</option>
                <option value="Thẻ">Thẻ</option>
            </select>
        </label>
        <p><button class="btn-navy" type="submit">Lập hóa đơn</button></p>
    </form>
<?php endif; ?>

<form class="filters" data-range="#invoice-body">
    <label>Tìm kiếm
        <input type="search" data-q placeholder="Bệnh nhân, mã hoặc tổng tiền">
    </label>
    <label>Từ ngày <input type="date" data-from></label>
    <label>Đến ngày <input type="date" data-to></label>
    <label>Tổng tiền
        <select data-sort>
            <option value="">Mặc định</option>
            <option value="desc">Cao đến thấp</option>
            <option value="asc">Thấp đến cao</option>
        </select>
    </label>
</form>

<h2>Hóa đơn khám bệnh</h2>
<div class="table-wrap">
<table>
    <tr>
        <th>Mã</th>
        <th>Bệnh nhân</th>
        <th>Tổng tiền</th>
        <th>Phương thức</th>
        <th>Trạng thái</th>
        <th>Ngày tạo</th>
        <th>Thao tác</th>
    </tr>
    <tbody id="invoice-body">
        <?php if (empty($invoices)): ?>
            <tr><td colspan="7">Chưa có hóa đơn.</td></tr>
        <?php endif; ?>
        <?php foreach ($invoices as $inv): ?>
        <tr class="invoice-row" data-invoice-id="<?php echo (int) $inv['invoice_id']; ?>" 
        data-date="<?php echo date('Y-m-d', strtotime($inv['created_at'])); ?>" 
        data-amount="<?php echo (int) round((float) $inv['total_amount']); ?>" 
        style="cursor:pointer">
            <td>#<?php echo (int) $inv['invoice_id']; ?></td>
            <td><?php echo htmlspecialchars((string) $inv['patient_name']); ?></td>
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
            <td>
                <?php if ($inv['status'] !== 'Paid'): ?>
                    <form action="/index.php?action=pay_invoice" method="POST" onsubmit="return confirm('Xác nhận đã thu tiền?');">
                        <input type="hidden" name="invoice_id" value="<?php echo (int) $inv['invoice_id']; ?>">
                        <button class="btn-navy" type="submit">Đã thu tiền</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<div class="modal" id="invoice-detail-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close-box>Đóng</button>
        <h2 id="invoice-detail-title">Chi tiết hóa đơn</h2>
        <div id="invoice-detail-body"><p class="lead">Đang tải…</p></div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>