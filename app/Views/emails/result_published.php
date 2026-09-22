<h2 style="margin-top:0;color:#0f172a;font-size:20px;font-weight:800;">Official Notice: Terminal Results Released</h2>

<p>Dear Parent / Guardian,</p>

<p>The academic examination council of <strong>Claret International School</strong> has formally completed review, administrative auditing, and position ranking for <strong><?= htmlspecialchars($className ?? 'your child\'s class') ?></strong> for the <strong><?= htmlspecialchars($termName ?? 'academic term') ?></strong>.</p>

<div class="info-box" style="border-left:4px solid #0284c7;">
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <tr>
            <td style="padding:6px 0;color:#64748b;">Academic Session:</td>
            <td style="padding:6px 0;font-weight:bold;color:#0f172a;" align="right"><?= htmlspecialchars($sessionName ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding:6px 0;color:#64748b;">Academic Term:</td>
            <td style="padding:6px 0;font-weight:bold;color:#0284c7;" align="right"><?= htmlspecialchars($termName ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding:6px 0;color:#64748b;">Class Arm:</td>
            <td style="padding:6px 0;font-weight:bold;color:#0f172a;" align="right"><?= htmlspecialchars($className ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding:6px 0;color:#64748b;">Publication Status:</td>
            <td style="padding:6px 0;font-weight:bold;color:#059669;" align="right">Published & Verified ✓</td>
        </tr>
    </table>
</div>

<p style="font-size:14px;color:#475569;">
    The electronic terminal report card featuring continuous assessments, final exam scores, behavioral trait ratings, homeroom teacher remarks, and cumulative positions is now accessible online.
</p>

<div style="text-align:center;margin-top:28px;">
    <a href="<?= htmlspecialchars($portalUrl ?? 'https://lms.test/parent/dashboard') ?>" class="btn">View & Download Report Card</a>
</div>

<p style="font-size:12px;color:#94a3b8;margin-top:20px;text-align:center;">
    *Please note that in compliance with school policy, terminal report cards for accounts with uncleared termly fees are restricted until settlement.
</p>
