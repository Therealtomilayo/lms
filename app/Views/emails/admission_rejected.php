<h2 style="margin-top:0;color:#0f172a;font-size:20px;font-weight:800;">Admission Application Update</h2>

<p>Dear <?= htmlspecialchars($parentName ?? 'Parent / Guardian') ?>,</p>

<p>Thank you for your interest in <strong>Claret International School</strong> and for submitting an application for <strong><?= htmlspecialchars($wardName ?? 'your child') ?></strong>.</p>

<p>Following a thorough assessment of applications for the current academic session, the Admissions Committee has concluded its review. We regret to inform you that we are unable to offer admission to <strong><?= htmlspecialchars($wardName ?? 'your child') ?></strong> at this time.</p>

<div class="info-box" style="border-left:4px solid #b91c1c;background:#fef2f2;padding:16px;border-radius:6px;margin:20px 0;">
    <h3 style="margin-top:0;margin-bottom:8px;font-size:15px;color:#991b1b;">Admissions Committee Decision Note</h3>
    <p style="margin:0;font-size:14px;color:#7f1d1d;">
        <?= nl2br(htmlspecialchars($rejectionReason ?: 'Due to cohort enrollment quotas and class capacity limits, we are unable to admit additional candidates for this grade level at this time.')) ?>
    </p>
</div>

<p style="font-size:14px;color:#475569;">
    If you submitted applications for multiple wards, please note that each candidate's application is evaluated independently based on class capacity and cohort evaluations. You can review all decisions and track your docket anytime on the Admissions Portal.
</p>

<div style="text-align:center;margin-top:28px;">
    <a href="<?= htmlspecialchars($portalUrl ?? 'https://lms.test/admissions/status') ?>" class="btn" style="background:#475569;color:#ffffff;padding:10px 20px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:bold;">View Admissions Portal</a>
</div>

<p style="font-size:12px;color:#94a3b8;margin-top:24px;text-align:center;">
    We wish <?= htmlspecialchars($wardName ?? 'your ward') ?> all the best in their academic journey. Please do not hesitate to contact our Admissions Office if you require further clarification.
</p>
