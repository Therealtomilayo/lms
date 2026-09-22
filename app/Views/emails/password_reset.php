<h2 style="margin-top:0;color:#0f172a;font-size:20px;font-weight:800;">Password Recovery Instructions</h2>

<p>Hello <?= htmlspecialchars($name ?? 'User') ?>,</p>

<p>We received an official request to reset the password for your Claret International School portal account (<strong><?= htmlspecialchars($email ?? '') ?></strong>).</p>

<p>To establish a new password and regain access to your account, please click the secure authorization button below:</p>

<div style="text-align:center;margin:32px 0;">
    <a href="<?= htmlspecialchars($resetUrl ?? '#') ?>" class="btn" style="background-color:#7B3046;">Reset Account Password</a>
</div>

<div class="info-box">
    <p style="margin:0;font-size:12px;color:#64748b;">
        <strong>Security Notice:</strong> This authorization link is strictly time-sensitive and will automatically expire in <strong>60 minutes</strong>.
    </p>
    <p style="margin:6px 0 0 0;font-size:12px;color:#64748b;">
        If button clicking does not work, copy and paste this address into your browser:
        <br>
        <span style="font-family:monospace;color:#7B3046;word-break:break-all;"><?= htmlspecialchars($resetUrl ?? '') ?></span>
    </p>
</div>

<p style="font-size:13px;color:#94a3b8;">
    If you did not initiate this request, no action is required. Your account remains completely secure and your current credentials will not be altered.
</p>
