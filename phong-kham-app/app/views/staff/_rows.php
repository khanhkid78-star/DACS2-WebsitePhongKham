<?php
$has = function ($flag) {
    return $flag === true || $flag === 1 || $flag === '1' || $flag === 't';
};
$colspan = $kind === 'receptionist' ? 8 : 7;
if (empty($rows)): ?>
    <tr><td colspan="<?php echo $colspan; ?>">Không có hồ sơ khớp bộ lọc.</td></tr>
<?php else: foreach ($rows as $row): ?>
    <tr class="person-row" data-kind="<?php echo htmlspecialchars($kind); ?>" data-id="<?php echo (int) $row['id']; ?>">
        <td>
            <?php if ($has($row['has_photo'])): ?>
                <img class="avatar" alt="" src="/index.php?action=staff_photo&kind=<?php echo urlencode($kind); ?>&id=<?php echo (int) $row['id']; ?>">
            <?php else: ?>
                <span class="avatar avatar-empty">?</span>
            <?php endif; ?>
        </td>
        <td>#<?php echo (int) $row['id']; ?></td>
        <td><?php echo htmlspecialchars((string) $row['full_name']); ?></td>
        <td><?php echo htmlspecialchars((string) $row['gender']); ?></td>
        <td><?php echo htmlspecialchars((string) $row['phone']); ?></td>
        <?php if ($kind === 'doctor' || $kind === 'nurse'): ?>
            <td><?php echo htmlspecialchars((string) ($row['specialty'] ?? '')); ?></td>
        <?php elseif ($kind === 'receptionist'): ?>
            <td><?php echo htmlspecialchars((string) ($row['shift'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string) ($row['status'] ?? '')); ?></td>
        <?php else: ?>
            <td><?php echo htmlspecialchars((string) ($row['cccd'] ?? '')); ?></td>
        <?php endif; ?>
        <td>
            <button class="btn-ghost" type="button" data-edit>Sửa</button>
            <form action="/index.php?action=staff_delete" method="POST" onsubmit="return confirm('Xóa hồ sơ này?');">
                <input type="hidden" name="kind" value="<?php echo htmlspecialchars($kind); ?>">
                <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                <button class="btn-logout" type="submit">Xóa</button>
            </form>
        </td>
    </tr>
<?php endforeach; endif; ?>