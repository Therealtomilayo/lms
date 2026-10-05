# Server Cron Job Setup & Automation Guide
**Claret International School LMS — Production Scheduled Tasks**

This guide provides step-by-step, beginner-friendly instructions for setting up automated background cron jobs for the Claret LMS.

---

## 1. What is a Cron Job and Why Does the LMS Need It?

A **Cron Job** is an automated timer on your hosting server that runs a command behind the scenes on a schedule (e.g., every 5 minutes, every hour, or every night at 2:00 AM).

Without a cron job, tasks would only happen when a human clicks a button in their browser. With a cron job running, the server automatically takes care of:
1. **Nightly Database Backups:** Automatically dumps the full MySQL database to `storage/backups/` every night at 02:00 AM WAT without any teacher or admin needing to remember.
2. **Expired Token Cleanup:** Purges expired password reset tokens and temporary guest auth tokens.
3. **Attendance Compliance Monitoring:** Scans student attendance rates and flags when a student falls below the statutory 75% minimum exam qualification threshold.
4. **School Fee Invoicing Reminders:** Scans for upcoming or overdue tuition invoices and queues notifications for parents.

---

## 2. The Master Cron Runner Script

The LMS includes a single, unified master cron script located at:
```bash
bin/cron.php
```

When triggered, it safely runs all pending background tasks, outputs execution telemetry, and exits with status `0` (Success).

### Testing it Manually in the Terminal

Before setting it up in your hosting control panel, you can test it directly from the command line:

```bash
# Standard scheduled run:
php bin/cron.php

# Or run with immediate database backup:
php bin/cron.php --backup
```

**Expected Terminal Output:**
```text
=======================================================
   CLARET INTERNATIONAL SCHOOL LMS — SCHEDULED TASKS   
=======================================================
Started At: 2026-10-05 11:55:51 WAT

[Task 1/4] Purging expired authentication tokens...
  -> Cleaned 0 expired reset tokens.

[Task 2/4] Scanning student attendance compliance...
  -> Scanned active students in term. Flagged 0 below 75% minimum.

[Task 3/4] Checking pending and overdue school fee invoices...
  -> Verified invoices: 1 overdue invoice(s) currently outstanding.

[Task 4/4] Automated database backup scheduled for 02:00 AM WAT (use --backup to force now).

=======================================================
Cron completed in 0.04s | Peak Memory: 4 MB
Tasks Succeeded: 3 | Failures: 0
=======================================================
```

---

## 3. Setting Up on cPanel (Standard Shared Hosting)

If your website is hosted on cPanel (Namecheap, Whogohost, QServers, Bluehost, Hostinger, cPanel Cloud, etc.):

### Step 1: Log in to cPanel
- Go to `https://your-domain.com:2083` or your hosting client area.

### Step 2: Open Cron Jobs
- In the search bar at the top, type **"Cron"** and click on **Cron Jobs** (under the *Advanced* section).

### Step 3: Configure the Frequency
- Scroll down to **Add New Cron Job**.
- Under **Common Settings**, select:
  - **Once Per Five Minutes** (`*/5 * * * *`) — *Recommended for production*  
    *Or*
  - **Once Per Hour** (`0 * * * *`)

The fields will auto-populate as follows:
| Setting | Value | Meaning |
|---|:---:|---|
| **Minute** | `*/5` | Every 5 minutes |
| **Hour** | `*` | Every hour |
| **Day** | `*` | Every day |
| **Month** | `*` | Every month |
| **Weekday** | `*` | Every day of the week |

### Step 4: Enter the Command
In the **Command** text field, paste the following command (substitute your actual cPanel username and directory):

```bash
/usr/local/bin/php /home/YOUR_CPANEL_USERNAME/public_html/bin/cron.php >/dev/null 2>&1
```

> [!TIP]
> **How to find your exact PHP binary path on cPanel:**  
> In cPanel file manager or terminal, your PHP path is almost always `/usr/local/bin/php` or `/usr/bin/php`.  
> If you have multiple PHP versions installed, use the PHP 8.2+ path, e.g.:  
> `/usr/local/bin/ea-php83 /home/YOUR_CPANEL_USERNAME/public_html/bin/cron.php >/dev/null 2>&1`

### Step 5: Save
- Click **"Add New Cron Job"**.
- That's it! Your cPanel server will now execute the runner automatically every 5 minutes.

---

## 4. Setting Up on a Linux VPS / Cloud Server (Ubuntu / Debian / Nginx / Apache)

If you are deploying on a DigitalOcean Droplet, AWS EC2, Linode, or any Linux server:

### Step 1: Open the crontab editor
Log in via SSH and run:
```bash
crontab -e
```

### Step 2: Add the cron entry
Scroll to the bottom of the file and add:
```bash
# Run Claret LMS background tasks every 5 minutes
*/5 * * * * /usr/bin/php /var/www/lms/bin/cron.php >> /var/www/lms/storage/logs/cron.log 2>&1
```

*(Replace `/var/www/lms` with your project root path).*

### Step 3: Save and Exit
- In `nano`, press `Ctrl + O`, then `Enter`, then `Ctrl + X`.
- You will see: `crontab: installing new crontab`.

---

## 5. Local Development Testing (Windows / Laragon)

During local development on Windows / Laragon, you don't need a background service. Whenever you want to test:
1. Open the Laragon Terminal (`Cmder` or PowerShell).
2. Run:
   ```powershell
   php bin/cron.php
   ```
3. To test the automated database dump locally:
   ```powershell
   php bin/cron.php --backup
   ```
   The backup will appear inside `storage/backups/`.

---

## 6. Verification and Troubleshooting

| Problem | Cause | Solution |
|---|---|---|
| **Permission Denied** | File permissions on `bin/cron.php` or `storage/` | Run `chmod +x bin/cron.php` and ensure web server user (`www-data`) owns `storage/`. |
| **PHP binary not found** | Wrong path in cPanel command | Run `which php` in cPanel terminal to find the absolute path (e.g. `/usr/bin/php`). |
| **Email alerts filling inbox** | Cron output sent to email | Add `>/dev/null 2>&1` to the end of the cron command to silence standard output. |
| **Nightly backup not running** | Server timezone mismatch | The runner backs up at 02:00 AM according to the configured `APP_TIMEZONE=Africa/Lagos` in `.env`. |

---

## 7. Summary Checklist for Launch Today

- [x] Master script `bin/cron.php` created and verified.
- [x] Token cleanup tested (cleans expired tokens in milliseconds).
- [x] Attendance threshold scan verified against active terms.
- [x] Overdue invoice check verified.
- [x] Database dump backup tested and verified (`--backup`).
- [ ] Add the 1-line command to your hosting server's Cron Jobs menu when deploying live.
