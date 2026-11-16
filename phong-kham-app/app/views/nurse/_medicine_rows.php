<?php if (empty($medicines)): ?>
    <tr><td colspan="6">Không có thuốc khớp bộ lọc.</td></tr>
<?php else: foreach ($medicines as $med):
    $payload = htmlspecialchars(json_encode(array(
        'id' => (int) $med['medicine_id'],
        'name' => (string) $med['name'],
        'unit' => (string) $med['unit'],
        'price' => (string) $med['price'],
        'description' => (string) $med['description'],
    ), JSON_UNESCAPED_UNICODE), ENT_QUOTES);
?>
    <tr>
        <td>#<?php echo (int) $med['medicine_id']; ?></td>
        <td><?php echo htmlspecialchars((string) $med['name']); ?></td>
        <td><?php echo htmlspecialchars((string) $med['unit']); ?></td>
        <td><?php echo number_format((float) $med['price']); ?> đ</td>
        <td class="clip" title="<?php echo htmlspecialchars((string) $med['description']); ?>"><?php echo htmlspecialchars((string) $med['description']); ?></td>
        <td>
            <button class="btn-ghost" type="button" data-medicine="<?php echo $payload; ?>">Sửa</button>
            <form action="/index.php?action=delete_medicine" method="POST" onsubmit="return confirm('Xóa thuốc này?');">
                <input type="hidden" name="medicine_id" value="<?php echo (int) $med['medicine_id']; ?>">
                <button class="btn-logout" type="submit">Xóa</button>
            </form>
        </td>
    </tr>
<?php endforeach; endif; ?>