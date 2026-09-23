<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Database;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\UserContext;
use PDO;

/**
 * Controller for Managing School Institutional Settings & Report Card Endorsement Credentials
 */
class SettingsController extends Controller
{
    private PDO $pdo;

    public function __construct(?AuthenticatorInterface $authenticator = null, ?PDO $pdo = null)
    {
        parent::__construct($authenticator);
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * Show institutional settings dashboard.
     * Route: GET /admin/settings
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
        }

        $stmt = $this->pdo->query('SELECT setting_key, setting_value FROM system_settings');
        $rawSettings = $stmt ? $stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];

        $settings = [
            'school_name' => $rawSettings['school_name'] ?? 'Claret International School',
            'school_motto' => $rawSettings['school_motto'] ?? 'Discipline, Integrity & Ardour',
            'school_address' => $rawSettings['school_address'] ?? 'Plot 700 Gitto Street, After Zeus Paradise Hotel & Mall, Mabushi, Abuja, Nigeria',
            'school_phone' => $rawSettings['school_phone'] ?? '+234(0)8123574983',
            'school_email' => $rawSettings['school_email'] ?? 'claretschs@gmail.com',
            'school_website' => $rawSettings['school_website'] ?? 'www.claretschools.org',
            'head_teacher_name' => $rawSettings['head_teacher_name'] ?? 'Mrs. N. Okon',
            'head_teacher_title' => $rawSettings['head_teacher_title'] ?? 'Head of School',
            'head_teacher_signature_url' => $rawSettings['head_teacher_signature_url'] ?? '/assets/img/teacher-signature.png',
            'school_stamp_url' => $rawSettings['school_stamp_url'] ?? '/assets/img/claret-stamp.png',
            'school_logo_url' => $rawSettings['school_logo_url'] ?? '/assets/img/logo.png',
        ];

        return Response::html($this->render('admin/settings/index', [
            'title' => 'School Settings & Identity — Claret Administration',
            'headerTitle' => 'Institutional Identity & Settings',
            'user' => $userContext,
            'settings' => $settings,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
        ], 'layouts/admin'));
    }

    /**
     * Update institutional settings.
     * Route: POST /admin/settings
     */
    public function update(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
        }

        $allowedKeys = [
            'school_name',
            'school_motto',
            'school_address',
            'school_phone',
            'school_email',
            'school_website',
            'head_teacher_name',
            'head_teacher_title',
            'head_teacher_signature_url',
            'school_stamp_url',
            'school_logo_url',
        ];

        $now = date('Y-m-d H:i:s');
        $upsertStmt = $this->pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value, is_secret, updated_by, updated_at)
            VALUES (:key, :value, 0, :admin_id, :updated_at)
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by),
                updated_at = VALUES(updated_at)
        ");

        foreach ($allowedKeys as $key) {
            $val = $request->input($key);
            if ($val !== null) {
                $upsertStmt->execute([
                    ':key' => $key,
                    ':value' => trim((string)$val),
                    ':admin_id' => $userContext->id,
                    ':updated_at' => $now,
                ]);
            }
        }

        // Handle file uploads if signature or stamp was uploaded
        $files = $request->files();
        $uploadDir = dirname(__DIR__, 3) . '/public/assets/uploads';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        if (!empty($files['signature_file']) && $files['signature_file']['error'] === UPLOAD_ERR_OK) {
            $f = $files['signature_file'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true)) {
                $destName = 'head_signature_' . time() . '.' . $ext;
                if (move_uploaded_file($f['tmp_name'], $uploadDir . '/' . $destName)) {
                    $upsertStmt->execute([
                        ':key' => 'head_teacher_signature_url',
                        ':value' => '/assets/uploads/' . $destName,
                        ':admin_id' => $userContext->id,
                        ':updated_at' => $now,
                    ]);
                }
            }
        }

        if (!empty($files['stamp_file']) && $files['stamp_file']['error'] === UPLOAD_ERR_OK) {
            $f = $files['stamp_file'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true)) {
                $destName = 'school_stamp_' . time() . '.' . $ext;
                if (move_uploaded_file($f['tmp_name'], $uploadDir . '/' . $destName)) {
                    $upsertStmt->execute([
                        ':key' => 'school_stamp_url',
                        ':value' => '/assets/uploads/' . $destName,
                        ':admin_id' => $userContext->id,
                        ':updated_at' => $now,
                    ]);
                }
            }
        }

        if (!empty($files['logo_file']) && $files['logo_file']['error'] === UPLOAD_ERR_OK) {
            $f = $files['logo_file'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true)) {
                $destName = 'school_logo_' . time() . '.' . $ext;
                if (move_uploaded_file($f['tmp_name'], $uploadDir . '/' . $destName)) {
                    $upsertStmt->execute([
                        ':key' => 'school_logo_url',
                        ':value' => '/assets/uploads/' . $destName,
                        ':admin_id' => $userContext->id,
                        ':updated_at' => $now,
                    ]);
                }
            }
        }

        Session::setFlash('success', 'Institutional settings and report card credentials updated successfully.');
        return Response::redirect('/admin/settings');
    }
}
