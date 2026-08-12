# Claret School LMS

A modern, secure, full-featured Learning Management System built for secondary school administration, instructional management, computer-based testing (CBT), attendance tracking, and parent portal access.

---

## Key Features

- **Academic Session & Term Management**: Multi-term hierarchy, class definitions, and subject assignments.
- **Role-Based Access Control (RBAC)**: Distinct permissions for `super_admin`, `admin`, `teacher`, `student`, and `parent`.
- **Instructional Materials & Assignments**: Teacher lesson uploads, student submissions, and grading workflows.
- **Computer-Based Testing (CBT)**: Question bank management, randomized quiz options, autosave mechanics, and server-side timer enforcement.
- **Gradebook & Report Cards**: Automatic score aggregation, term report card generation, and PDF export.
- **Daily Attendance Tracking**: Class-level attendance marking and oversight reporting.
- **Weekly Timetables**: Visual schedule builder with automatic conflict detection.
- **Parent Portal**: Linked multi-child oversight for assignments, grades, attendance, and timetables.
- **Production-Grade Security**: Global security headers (HSTS, CSP, X-Frame-Options), rate-limiting middleware, CSRF protection, BCRYPT password hashing, and structured JSON audit logging.

---

## Tech Stack & Architecture

- **Backend**: Pure PHP 8.3 (Strict types, MVC pattern, decoupled service-repository architecture, custom router).
- **Database**: MySQL 8.0+ (Production) / SQLite (Integration testing).
- **Frontend**: Responsive Tailwind CSS UI with Vanilla JavaScript micro-interactions.
- **Testing**: PHPUnit 11 with 250+ automated unit and integration tests.

---

## Installation & Setup

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/Therealtomilayo/lms.git
   cd lms
   ```

2. **Install Composer Dependencies**:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Configure Environment**:
   ```bash
   cp .env.example .env
   # Update database credentials and app parameters in .env
   ```

4. **Run Database Migrations**:
   ```bash
   php bin/migrate.php
   ```

5. **Run Seeders (Optional demo data)**:
   ```bash
   php bin/seed.php
   ```

6. **Run Test Suite**:
   ```bash
   vendor/bin/phpunit
   ```

---

## License
Proprietary / All rights reserved.
