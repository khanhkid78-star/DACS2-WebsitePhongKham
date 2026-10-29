<?php 
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('doctor', 'admin')); // Chỉ Bác sĩ và Admin[cite: 25]

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<h2>Hồ Sơ Bệnh Án (View Bác Sĩ)</h2>

<table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse; background: white;">
    <tr style="background-color: #004b93; color: white;">
        <th>Mã HS</th>
        <th>Bệnh Nhân</th>
        <th>Ngày Khám</th>
        <th>Triệu Chứng</th>
        <th>Chẩn Đoán</th>
        <th>Kết Luận</th>
    </tr>
    <?php foreach ($records as $rec): ?>
    <tr>
        <td>#<?php echo $rec['record_id']; ?></td>
        <td><?php echo htmlspecialchars($rec['patient_name']); ?></td>
        <td><?php echo $rec['visit_date']; ?></td>
        <td><?php echo htmlspecialchars($rec['symptoms']); ?></td>
        <td><?php echo htmlspecialchars($rec['diagnosis']); ?></td>
        <td><?php echo htmlspecialchars($rec['conclusion']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>