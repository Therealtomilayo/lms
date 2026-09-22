<h2 style="margin-top:0;color:#0f172a;font-size:20px;font-weight:800;">Congratulations — Admission Approved!</h2>

<p>Dear <?= htmlspecialchars($parentName ?? 'Parent / Guardian') ?>,</p>

<p>On behalf of the Governing Board and Faculty of <strong>Claret International School</strong>, we are thrilled to formally notify you that the admission application for <strong><?= htmlspecialchars($studentName ?? 'your ward') ?></strong> has been successfully reviewed and <strong>APPROVED</strong>!</p>

<div class="info-box" style="border-left:4px solid #7B3046;">
    <h3 style="margin-top:0;margin-bottom:12px;font-size:15px;color:#7B3046;">Student Onboarding & Institutional Access</h3>
    
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <tr>
            <td style="padding:6px 0;color:#64748b;">Full Name:</td>
            <td style="padding:6px 0;font-weight:bold;color:#0f172a;" align="right"><?= htmlspecialchars($studentName ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding:6px 0;color:#64748b;">Assigned Admission No:</td>
            <td style="padding:6px 0;font-family:monospace;font-weight:bold;color:#7B3046;" align="right"><?= htmlspecialchars($admissionNumber ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding:6px 0;color:#64748b;">Allocated Class Arm:</td>
            <td style="padding:6px 0;font-weight:bold;color:#0f172a;" align="right"><?= htmlspecialchars($className ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding:6px 0;color:#64748b;">Portal Login Identifier:</td>
            <td style="padding:6px 0;font-family:monospace;color:#0f172a;" align="right"><?= htmlspecialchars($admissionNumber ?? '') ?> or <?= htmlspecialchars($email ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding:6px 0;color:#64748b;">Initial Default Password:</td>
            <td style="padding:6px 0;font-family:monospace;font-weight:bold;color:#b91c1c;background:#fef2f2;border-radius:4px;padding:4px 8px;" align="right"><?= htmlspecialchars($defaultPassword ?? 'Claret@2026!') ?></td>
        </tr>
    </table>
</div>

<p style="font-size:14px;color:#475569;">
    All curriculum subjects for this class cohort have already been pre-enrolled for your child. Please log into the portal to review the academic calendar, fee schedule, and download the school orientation handbook.
</p>

<div style="text-align:center;margin-top:28px;">
    <a href="<?= htmlspecialchars($portalUrl ?? 'https://lms.test/login') ?>" class="btn">Log into Student Portal</a>
</div>

<p style="font-size:12px;color:#94a3b8;margin-top:24px;text-align:center;">
    *For security, students will be prompted to change their password upon their initial login session.
</p>
