<h2 style="margin-top:0;color:#0f172a;font-size:20px;font-weight:800;"><?= htmlspecialchars($title ?? 'Official Bulletin') ?></h2>

<p>Dear Member of the Claret Community,</p>

<div style="font-size:15px;line-height:1.7;color:#334155;margin:20px 0;">
    <?= nl2br(htmlspecialchars($bodyText ?? '')) ?>
</div>

<?php if (!empty($actionUrl) && !empty($actionText)): ?>
<div style="text-align:center;margin-top:28px;">
    <a href="<?= htmlspecialchars($actionUrl) ?>" class="btn"><?= htmlspecialchars($actionText) ?></a>
</div>
<?php endif; ?>
