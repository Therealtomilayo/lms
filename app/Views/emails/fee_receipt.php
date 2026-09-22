<h2 style="margin-top:0;color:#0f172a;font-size:20px;font-weight:800;">Official Payment Confirmation & Receipt</h2>

<p>Dear <?= htmlspecialchars($payerName ?? 'Parent / Guardian') ?>,</p>

<p>Thank you for your payment. We confirm that your transaction has been successfully processed and credited to your ward's school billing account.</p>

<div class="info-box" style="border-left:4px solid #10b981;">
    <div style="font-size:12px;color:#64748b;text-transform:uppercase;font-weight:bold;letter-spacing:0.5px;">Amount Paid</div>
    <div style="font-size:28px;font-weight:800;color:#047857;margin:4px 0 12px 0;">
        NGN <?= number_format((float)($amountPaid ?? 0), 2) ?>
    </div>

    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <tr>
            <td style="padding:5px 0;color:#64748b;">Receipt / Reference:</td>
            <td style="padding:5px 0;font-family:monospace;font-weight:bold;color:#0f172a;" align="right"><?= htmlspecialchars($reference ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding:5px 0;color:#64748b;">Student Name:</td>
            <td style="padding:5px 0;font-weight:bold;color:#0f172a;" align="right"><?= htmlspecialchars($studentName ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding:5px 0;color:#64748b;">Invoice Number:</td>
            <td style="padding:5px 0;font-family:monospace;color:#0f172a;" align="right"><?= htmlspecialchars($invoiceNumber ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding:5px 0;color:#64748b;">Academic Term:</td>
            <td style="padding:5px 0;color:#0f172a;" align="right"><?= htmlspecialchars($termName ?? '') ?></td>
        </tr>
        <tr>
            <td style="padding:5px 0;color:#64748b;">Payment Date:</td>
            <td style="padding:5px 0;color:#0f172a;" align="right"><?= htmlspecialchars($paymentDate ?? date('d M Y, H:i')) ?></td>
        </tr>
        <tr>
            <td style="padding:5px 0;color:#64748b;">Outstanding Balance:</td>
            <td style="padding:5px 0;font-weight:bold;color:<?= ((float)($balanceRemaining ?? 0) <= 0) ? '#059669' : '#b91c1c' ?>;" align="right">
                NGN <?= number_format((float)($balanceRemaining ?? 0), 2) ?>
                <?php if ((float)($balanceRemaining ?? 0) <= 0): ?>
                    <span style="font-size:11px;background:#d1fae5;color:#065f46;padding:2px 6px;border-radius:4px;margin-left:4px;">CLEARED</span>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</div>

<p style="font-size:14px;color:#475569;">
    An official signed PDF receipt has been generated and archived in your portal under <strong>School Fees &gt; Payment History</strong>. If all termly fees are fully cleared, your child's terminal report card access has been automatically unlocked.
</p>

<div style="text-align:center;margin-top:28px;">
    <a href="<?= htmlspecialchars($receiptUrl ?? 'https://lms.test/student/fees') ?>" class="btn">View & Download PDF Receipt</a>
</div>
