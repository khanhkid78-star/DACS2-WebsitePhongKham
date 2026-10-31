<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('doctor', 'admin'));

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<h2>Hồ sơ bệnh án</h2>

<div class="table-wrap">
<table>
    <tr>
        <th>Mã</th>
        <th>Bệnh nhân</th>
        <th>Ngày khám</th>
        <th>Triệu chứng</th>
        <th>Chẩn đoán</th>
        <th>Kết luận</th>
    </tr>
    <?php foreach ($records as $rec): ?>
    <tr>
        <td>#<?php echo (int) $rec['record_id']; ?></td>
        <td><?php echo htmlspecialchars($rec['patient_name']); ?></td>
        <td><?php echo htmlspecialchars($rec['visit_date']); ?></td>
        <td><?php echo htmlspecialchars($rec['symptoms']); ?></td>
        <td><?php echo htmlspecialchars($rec['diagnosis']); ?></td>
        <td><?php echo htmlspecialchars($rec['conclusion']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>