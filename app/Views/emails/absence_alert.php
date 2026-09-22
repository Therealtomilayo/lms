<h2 style="margin-top:0;color:#0f172a;font-size:20px;font-weight:800;">Notice of Student Absence</h2>

<p>Dear Parent / Guardian,</p>

<p>This is an automated attendance notification to inform you that your ward has been recorded as <strong>ABSENT</strong> during today's morning homeroom roll call.</p>

<div class="info-box">
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <tr>
            <td style="padding:6px 0;color:#64748b;">Student Name:</td>
            <td style="padding:6px 0;font-weight:bold;color:#0f172a;" align="right"><?= htmlspecialchars($studentName ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding:6px 0;color:#64748b;">Admission Number:</td>
            <td style="padding:6px 0;font-family:monospace;font-weight:bold;color:#0f172a;" align="right"><?= htmlspecialchars($admissionNumber ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding:6px 0;color:#64748b;">Class Arm:</td>
            <td style="padding:6px 0;font-weight:bold;color:#0f172a;" align="right"><?= htmlspecialchars($className ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding:6px 0;color:#64748b;">Date Recorded:</td>
            <td style="padding:6px 0;font-weight:bold;color:#b91c1c;" align="right"><?= htmlspecialchars($date ?? date('d M Y')) ?></td>
        </tr>
        <?php if (!empty($remarks)): ?>
        <tr>
            <td style="padding:6px 0;color:#64748b;">Homeroom Remark:</td>
            <td style="padding:6px 0;font-style:italic;color:#334155;" align="right"><?= htmlspecialchars($remarks) ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<p style="font-size:14px;color:#475569;">
    If this absence was pre-planned or medical, kindly reply with a doctor's note or medical excuse. If this absence is unexpected, please contact the school administrative desk or the class form teacher immediately to verify your child's safety.
</p>

<div style="text-align:center;margin-top:28px;">
    <a href="<?= htmlspecialchars($portalUrl ?? 'https://lms.test/login') ?>" class="btn">View Attendance Record</a>
</div>
