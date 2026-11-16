<?php if (empty($records)): ?>
    <tr><td colspan="6">Không có bệnh án khớp bộ lọc.</td></tr>
<?php else: foreach ($records as $rec):
    $payload = htmlspecialchars(json_encode(array(
        'id' => (int) $rec['record_id'],
        'symptoms' => (string) $rec['symptoms'],
        'diagnosis' => (string) $rec['diagnosis'],
        'conclusion' => (string) $rec['conclusion'],
    ), JSON_UNESCAPED_UNICODE), ENT_QUOTES);
?>
    <tr class="record-row" data-record-id="<?php echo (int) $rec['record_id']; ?>" style="cursor:pointer">
        <td>#<?php echo (int) $rec['record_id']; ?></td>
        <td><?php echo htmlspecialchars((string) $rec['patient_name']); ?></td>
        <td><?php echo htmlspecialchars((string) $rec['doctor_name']); ?></td>
        <td><?php echo date('H:i d/m/Y', strtotime($rec['visit_date'])); ?></td>
        <td class="clip" title="<?php echo htmlspecialchars((string) $rec['diagnosis']); ?>"><?php echo htmlspecialchars((string) $rec['diagnosis']); ?></td>
        <td><button class="btn-ghost" type="button" data-record="<?php echo $payload; ?>">Sửa</button></td>
    </tr>
<?php endforeach; endif; ?>