<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\FeeCategory;
use App\Models\FeeInvoice;
use App\Models\FeeInvoiceItem;
use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\Payment;
use PDO;

class FeeRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /* ----------------------------------------------------------------------
     * 1. FEE CATEGORIES
     * ---------------------------------------------------------------------- */

    /**
     * @return FeeCategory[]
     */
    public function getAllCategories(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM `fee_categories`';
        if ($activeOnly) {
            $sql .= ' WHERE `is_active` = 1';
        }
        $sql .= ' ORDER BY `name` ASC';

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $categories = [];
        foreach ($rows as $row) {
            $categories[] = FeeCategory::fromArray($row);
        }
        return $categories;
    }

    public function findCategoryById(int $id): ?FeeCategory
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `fee_categories` WHERE `id` = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? FeeCategory::fromArray($row) : null;
    }

    public function createCategory(string $name, ?string $description = null): FeeCategory
    {
        $stmt = $this->pdo->prepare('INSERT INTO `fee_categories` (`name`, `description`, `is_active`) VALUES (:name, :desc, 1)');
        $stmt->execute([
            ':name' => trim($name),
            ':desc' => $description !== null ? trim($description) : null,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->findCategoryById($id);
    }

    /* ----------------------------------------------------------------------
     * 2. FEE STRUCTURES & SCHEDULES
     * ---------------------------------------------------------------------- */

    /**
     * @return FeeStructure[]
     */
    public function getAllStructures(?int $sessionId = null, ?int $termId = null): array
    {
        $sql = 'SELECT fs.*, 
                       s.name as session_name, 
                       t.name as term_name, 
                       al.name as level_name, 
                       c.name as class_name
                FROM `fee_structures` fs
                JOIN `sessions` s ON s.id = fs.session_id
                JOIN `terms` t ON t.id = fs.term_id
                LEFT JOIN `academic_levels` al ON al.id = fs.academic_level_id
                LEFT JOIN `classes` c ON c.id = fs.class_id
                WHERE 1=1';

        $params = [];
        if ($sessionId !== null) {
            $sql .= ' AND fs.session_id = :session_id';
            $params[':session_id'] = $sessionId;
        }
        if ($termId !== null) {
            $sql .= ' AND fs.term_id = :term_id';
            $params[':term_id'] = $termId;
        }

        $sql .= ' ORDER BY fs.created_at DESC, fs.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $structures = [];
        foreach ($rows as $row) {
            $items = $this->getItemsForStructure((int)$row['id']);
            $structures[] = FeeStructure::fromArray($row, $items);
        }

        return $structures;
    }

    public function findStructureById(int $id): ?FeeStructure
    {
        $sql = 'SELECT fs.*, 
                       s.name as session_name, 
                       t.name as term_name, 
                       al.name as level_name, 
                       c.name as class_name
                FROM `fee_structures` fs
                JOIN `sessions` s ON s.id = fs.session_id
                JOIN `terms` t ON t.id = fs.term_id
                LEFT JOIN `academic_levels` al ON al.id = fs.academic_level_id
                LEFT JOIN `classes` c ON c.id = fs.class_id
                WHERE fs.id = :id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $items = $this->getItemsForStructure($id);
        return FeeStructure::fromArray($row, $items);
    }

    /**
     * @return FeeStructureItem[]
     */
    public function getItemsForStructure(int $structureId): array
    {
        $sql = 'SELECT fsi.*, fc.name as category_name
                FROM `fee_structure_items` fsi
                LEFT JOIN `fee_categories` fc ON fc.id = fsi.fee_category_id
                WHERE fsi.fee_structure_id = :id
                ORDER BY fsi.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $structureId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = [];
        foreach ($rows as $row) {
            $items[] = FeeStructureItem::fromArray($row);
        }
        return $items;
    }

    /**
     * Find the best matching fee structure for a class or level in an active session/term.
     * Specific class assignment takes precedence over level-wide fee structure.
     */
    public function findMatchingStructure(int $sessionId, int $termId, ?int $levelId = null, ?int $classId = null): ?FeeStructure
    {
        // 1. Check class-specific structure
        if ($classId !== null) {
            $stmt = $this->pdo->prepare('SELECT id FROM `fee_structures` WHERE session_id = :s AND term_id = :t AND class_id = :c AND is_active = 1 ORDER BY id DESC LIMIT 1');
            $stmt->execute([':s' => $sessionId, ':t' => $termId, ':c' => $classId]);
            $foundId = $stmt->fetchColumn();
            if ($foundId) {
                return $this->findStructureById((int)$foundId);
            }
        }

        // 2. Check academic level structure
        if ($levelId !== null) {
            $stmt = $this->pdo->prepare('SELECT id FROM `fee_structures` WHERE session_id = :s AND term_id = :t AND academic_level_id = :l AND class_id IS NULL AND is_active = 1 ORDER BY id DESC LIMIT 1');
            $stmt->execute([':s' => $sessionId, ':t' => $termId, ':l' => $levelId]);
            $foundId = $stmt->fetchColumn();
            if ($foundId) {
                return $this->findStructureById((int)$foundId);
            }
        }

        // 3. Check general school-wide structure (level and class are null)
        $stmt = $this->pdo->prepare('SELECT id FROM `fee_structures` WHERE session_id = :s AND term_id = :t AND academic_level_id IS NULL AND class_id IS NULL AND is_active = 1 ORDER BY id DESC LIMIT 1');
        $stmt->execute([':s' => $sessionId, ':t' => $termId]);
        $foundId = $stmt->fetchColumn();
        if ($foundId) {
            return $this->findStructureById((int)$foundId);
        }

        return null;
    }

    public function createStructure(array $data, array $items): FeeStructure
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('INSERT INTO `fee_structures` 
            (`session_id`, `term_id`, `academic_level_id`, `class_id`, `title`, `currency`, `due_date`, `is_active`, `created_by`, `created_at`, `updated_at`)
            VALUES (:session_id, :term_id, :level_id, :class_id, :title, :currency, :due_date, :is_active, :created_by, :created_at, :updated_at)');

        $stmt->execute([
            ':session_id' => (int)$data['session_id'],
            ':term_id' => (int)$data['term_id'],
            ':level_id' => !empty($data['academic_level_id']) ? (int)$data['academic_level_id'] : null,
            ':class_id' => !empty($data['class_id']) ? (int)$data['class_id'] : null,
            ':title' => trim((string)$data['title']),
            ':currency' => trim((string)($data['currency'] ?? 'NGN')),
            ':due_date' => !empty($data['due_date']) ? (string)$data['due_date'] : null,
            ':is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
            ':created_by' => (int)$data['created_by'],
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $structureId = (int)$this->pdo->lastInsertId();

        // Insert items
        $itemStmt = $this->pdo->prepare('INSERT INTO `fee_structure_items`
            (`fee_structure_id`, `fee_category_id`, `name`, `amount`, `is_compulsory`, `created_at`)
            VALUES (:fs_id, :cat_id, :name, :amount, :is_compulsory, :created_at)');

        foreach ($items as $item) {
            $amount = (float)($item['amount'] ?? 0.0);
            if ($amount <= 0 && empty($item['name'])) {
                continue;
            }
            $itemStmt->execute([
                ':fs_id' => $structureId,
                ':cat_id' => (int)($item['fee_category_id'] ?? 1),
                ':name' => trim((string)($item['name'] ?? 'Fee Component')),
                ':amount' => $amount,
                ':is_compulsory' => isset($item['is_compulsory']) ? (int)$item['is_compulsory'] : 1,
                ':created_at' => $now,
            ]);
        }

        return $this->findStructureById($structureId);
    }

    public function toggleStructureStatus(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE `fee_structures` SET `is_active` = 1 - `is_active`, `updated_at` = :now WHERE `id` = :id');
        return $stmt->execute([
            ':now' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }

    /* ----------------------------------------------------------------------
     * 3. FEE INVOICES & SNAPSHOT ITEMS
     * ---------------------------------------------------------------------- */

    public function generateInvoiceNumber(int $sessionId): string
    {
        $year = date('Y');
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM `fee_invoices`');
        $seq = ((int)$stmt->fetchColumn()) + 1;

        return sprintf('INV-%s-%05d', $year, $seq);
    }

    public function createInvoice(array $data, array $items): FeeInvoice
    {
        $now = date('Y-m-d H:i:s');
        $subtotal = 0.0;
        foreach ($items as $it) {
            $subtotal += (float)($it['amount'] ?? 0.0);
        }

        $discount = (float)($data['discount_amount'] ?? 0.0);
        $total = max(0.0, $subtotal - $discount);
        $amountPaid = (float)($data['amount_paid'] ?? 0.0);
        $balance = max(0.0, $total - $amountPaid);

        $status = FeeInvoice::STATUS_UNPAID;
        if ($balance <= 0.0 && $total > 0.0) {
            $status = FeeInvoice::STATUS_PAID;
        } elseif ($amountPaid > 0.0) {
            $status = FeeInvoice::STATUS_PARTIALLY_PAID;
        }

        $stmt = $this->pdo->prepare('INSERT INTO `fee_invoices` (
            `invoice_number`, `student_id`, `parent_id`, `class_id`, `session_id`, `term_id`,
            `subtotal`, `discount_amount`, `total_amount`, `amount_paid`, `balance_due`,
            `status`, `due_date`, `notes`, `created_by`, `created_at`, `updated_at`
        ) VALUES (
            :invoice_num, :student_id, :parent_id, :class_id, :session_id, :term_id,
            :subtotal, :discount, :total, :amount_paid, :balance,
            :status, :due_date, :notes, :created_by, :created_at, :updated_at
        )');

        $stmt->execute([
            ':invoice_num' => (string)$data['invoice_number'],
            ':student_id' => (int)$data['student_id'],
            ':parent_id' => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
            ':class_id' => (int)$data['class_id'],
            ':session_id' => (int)$data['session_id'],
            ':term_id' => (int)$data['term_id'],
            ':subtotal' => $subtotal,
            ':discount' => $discount,
            ':total' => $total,
            ':amount_paid' => $amountPaid,
            ':balance' => $balance,
            ':status' => $status,
            ':due_date' => !empty($data['due_date']) ? (string)$data['due_date'] : null,
            ':notes' => !empty($data['notes']) ? (string)$data['notes'] : null,
            ':created_by' => (int)$data['created_by'],
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $invoiceId = (int)$this->pdo->lastInsertId();

        // Insert line items
        $itemStmt = $this->pdo->prepare('INSERT INTO `fee_invoice_items`
            (`invoice_id`, `fee_category_id`, `name`, `amount`, `created_at`)
            VALUES (:inv_id, :cat_id, :name, :amount, :created_at)');

        foreach ($items as $it) {
            $itemStmt->execute([
                ':inv_id' => $invoiceId,
                ':cat_id' => !empty($it['fee_category_id']) ? (int)$it['fee_category_id'] : null,
                ':name' => trim((string)$it['name']),
                ':amount' => (float)$it['amount'],
                ':created_at' => $now,
            ]);
        }

        return $this->findInvoiceById($invoiceId);
    }

    public function findInvoiceById(int $id): ?FeeInvoice
    {
        $sql = 'SELECT fi.*,
                       s.admission_number,
                       u_st.name as student_name,
                       u_p.name as parent_name,
                       u_p.email as parent_email,
                       u_p.phone as parent_phone,
                       c.name as class_name,
                       ses.name as session_name,
                       t.name as term_name
                FROM `fee_invoices` fi
                JOIN `students` s ON s.id = fi.student_id
                JOIN `users` u_st ON u_st.id = s.user_id
                LEFT JOIN `parents` p ON p.id = fi.parent_id
                LEFT JOIN `users` u_p ON u_p.id = p.user_id
                JOIN `classes` c ON c.id = fi.class_id
                JOIN `sessions` ses ON ses.id = fi.session_id
                JOIN `terms` t ON t.id = fi.term_id
                WHERE fi.id = :id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $items = $this->getItemsForInvoice($id);
        $payments = $this->getPaymentsForInvoice($id);

        return FeeInvoice::fromArray($row, $items, $payments);
    }

    public function findInvoiceForStudentTerm(int $studentId, int $sessionId, int $termId): ?FeeInvoice
    {
        $stmt = $this->pdo->prepare('SELECT id FROM `fee_invoices` WHERE student_id = :s AND session_id = :ses AND term_id = :t LIMIT 1');
        $stmt->execute([':s' => $studentId, ':ses' => $sessionId, ':t' => $termId]);
        $id = $stmt->fetchColumn();

        return $id ? $this->findInvoiceById((int)$id) : null;
    }

    /**
     * @return FeeInvoiceItem[]
     */
    public function getItemsForInvoice(int $invoiceId): array
    {
        $sql = 'SELECT fii.*, fc.name as category_name
                FROM `fee_invoice_items` fii
                LEFT JOIN `fee_categories` fc ON fc.id = fii.fee_category_id
                WHERE fii.invoice_id = :id
                ORDER BY fii.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $invoiceId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = [];
        foreach ($rows as $row) {
            $items[] = FeeInvoiceItem::fromArray($row);
        }
        return $items;
    }

    /**
     * @return Payment[]
     */
    public function getPaymentsForInvoice(int $invoiceId): array
    {
        $sql = 'SELECT p.*,
                       u.name as payer_name,
                       u.email as payer_email,
                       s.admission_number as student_admission_number,
                       u_st.name as student_name
                FROM `payments` p
                JOIN `users` u ON u.id = p.user_id
                LEFT JOIN `students` s ON s.id = p.student_id
                LEFT JOIN `users` u_st ON u_st.id = s.user_id
                WHERE p.invoice_id = :id
                ORDER BY p.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $invoiceId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $payments = [];
        foreach ($rows as $row) {
            $payments[] = Payment::fromArray($row);
        }
        return $payments;
    }

    /**
     * @return FeeInvoice[]
     */
    public function getInvoicesForStudent(int $studentId): array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM `fee_invoices` WHERE student_id = :id ORDER BY created_at DESC');
        $stmt->execute([':id' => $studentId]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $invoices = [];
        foreach ($ids as $id) {
            $inv = $this->findInvoiceById((int)$id);
            if ($inv) {
                $invoices[] = $inv;
            }
        }
        return $invoices;
    }

    /**
     * @return FeeInvoice[]
     */
    public function getInvoicesForParent(int $parentId): array
    {
        // Finds all invoices where parent_id matches OR where student is linked to this parent in parent_student
        $sql = 'SELECT fi.id
                FROM `fee_invoices` fi
                WHERE fi.parent_id = :p1 
                   OR fi.student_id IN (SELECT student_id FROM `parent_student` WHERE parent_id = :p2)
                ORDER BY fi.created_at DESC, fi.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':p1' => $parentId, ':p2' => $parentId]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $invoices = [];
        foreach ($ids as $id) {
            $inv = $this->findInvoiceById((int)$id);
            if ($inv) {
                $invoices[] = $inv;
            }
        }
        return $invoices;
    }

    /**
     * Filter invoices for administrative ledger.
     * @return FeeInvoice[]
     */
    public function filterInvoices(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT fi.id
                FROM `fee_invoices` fi
                JOIN `students` s ON s.id = fi.student_id
                JOIN `users` u_st ON u_st.id = s.user_id
                LEFT JOIN `classes` c ON c.id = fi.class_id
                WHERE 1=1';

        $params = [];

        if (!empty($filters['session_id'])) {
            $sql .= ' AND fi.session_id = :session_id';
            $params[':session_id'] = (int)$filters['session_id'];
        }

        if (!empty($filters['term_id'])) {
            $sql .= ' AND fi.term_id = :term_id';
            $params[':term_id'] = (int)$filters['term_id'];
        }

        if (!empty($filters['class_id'])) {
            $sql .= ' AND fi.class_id = :class_id';
            $params[':class_id'] = (int)$filters['class_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND fi.status = :status';
            $params[':status'] = (string)$filters['status'];
        }

        if (!empty($filters['query'])) {
            $sql .= ' AND (u_st.name LIKE :q OR s.admission_number LIKE :q OR fi.invoice_number LIKE :q)';
            $params[':q'] = '%' . trim((string)$filters['query']) . '%';
        }

        $sql .= ' ORDER BY fi.created_at DESC, fi.id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $invoices = [];
        foreach ($ids as $id) {
            $inv = $this->findInvoiceById((int)$id);
            if ($inv) {
                $invoices[] = $inv;
            }
        }
        return $invoices;
    }

    public function countFilteredInvoices(array $filters = []): int
    {
        $sql = 'SELECT COUNT(fi.id)
                FROM `fee_invoices` fi
                JOIN `students` s ON s.id = fi.student_id
                JOIN `users` u_st ON u_st.id = s.user_id
                LEFT JOIN `classes` c ON c.id = fi.class_id
                WHERE 1=1';

        $params = [];

        if (!empty($filters['session_id'])) {
            $sql .= ' AND fi.session_id = :session_id';
            $params[':session_id'] = (int)$filters['session_id'];
        }

        if (!empty($filters['term_id'])) {
            $sql .= ' AND fi.term_id = :term_id';
            $params[':term_id'] = (int)$filters['term_id'];
        }

        if (!empty($filters['class_id'])) {
            $sql .= ' AND fi.class_id = :class_id';
            $params[':class_id'] = (int)$filters['class_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND fi.status = :status';
            $params[':status'] = (string)$filters['status'];
        }

        if (!empty($filters['query'])) {
            $sql .= ' AND (u_st.name LIKE :q OR s.admission_number LIKE :q OR fi.invoice_number LIKE :q)';
            $params[':q'] = '%' . trim((string)$filters['query']) . '%';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function updateInvoiceFinancials(int $id, float $amountPaid, float $balanceDue, string $status): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE `fee_invoices` 
            SET `amount_paid` = :paid, `balance_due` = :balance, `status` = :status, `updated_at` = :now
            WHERE `id` = :id');

        return $stmt->execute([
            ':paid' => $amountPaid,
            ':balance' => $balanceDue,
            ':status' => $status,
            ':now' => $now,
            ':id' => $id,
        ]);
    }

    /**
     * Aggregate Bursary metrics for dashboard & reporting
     */
    public function getBursarySummary(?int $sessionId = null, ?int $termId = null): array
    {
        $sql = 'SELECT 
                    COUNT(*) as total_invoices,
                    COALESCE(SUM(total_amount), 0.0) as total_billed,
                    COALESCE(SUM(amount_paid), 0.0) as total_collected,
                    COALESCE(SUM(balance_due), 0.0) as total_outstanding,
                    SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_count,
                    SUM(CASE WHEN status = "partially_paid" THEN 1 ELSE 0 END) as partial_count,
                    SUM(CASE WHEN status = "unpaid" THEN 1 ELSE 0 END) as unpaid_count,
                    SUM(CASE WHEN status = "overdue" THEN 1 ELSE 0 END) as overdue_count
                FROM `fee_invoices`
                WHERE 1=1';

        $params = [];
        if ($sessionId !== null) {
            $sql .= ' AND session_id = :session_id';
            $params[':session_id'] = $sessionId;
        }
        if ($termId !== null) {
            $sql .= ' AND term_id = :term_id';
            $params[':term_id'] = $termId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_invoices' => (int)($res['total_invoices'] ?? 0),
            'total_billed' => (float)($res['total_billed'] ?? 0.0),
            'total_collected' => (float)($res['total_collected'] ?? 0.0),
            'total_outstanding' => (float)($res['total_outstanding'] ?? 0.0),
            'paid_count' => (int)($res['paid_count'] ?? 0),
            'partial_count' => (int)($res['partial_count'] ?? 0),
            'unpaid_count' => (int)($res['unpaid_count'] ?? 0),
            'overdue_count' => (int)($res['overdue_count'] ?? 0),
        ];
    }
}
