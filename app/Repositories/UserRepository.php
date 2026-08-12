<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;
use PDO;

/**
 * Data Access Layer for User Identity, Multi-Role, Sessions and Password Tokens
 */
class UserRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `users` WHERE `id` = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $roles = $this->getRolesForUser($id);

        return User::fromArray($row, $roles);
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `users` WHERE LOWER(`email`) = LOWER(:email) LIMIT 1');
        $stmt->execute([':email' => trim($email)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $roles = $this->getRolesForUser((int)$row['id']);

        return User::fromArray($row, $roles);
    }

    public function findByUuid(string $uuid): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `users` WHERE `uuid` = :uuid LIMIT 1');
        $stmt->execute([':uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $roles = $this->getRolesForUser((int)$row['id']);

        return User::fromArray($row, $roles);
    }

    /**
     * @return string[]
     */
    public function getRolesForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT `role` FROM `user_roles` WHERE `user_id` = :user_id AND `is_active` = 1 ORDER BY `id` ASC'
        );
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function create(array $data, array $roles = []): User
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO `users` (`uuid`, `name`, `email`, `phone`, `password_hash`, `status`, `must_change_password`, `created_at`, `updated_at`)
                VALUES (:uuid, :name, :email, :phone, :password_hash, :status, :must_change_password, :created_at, :updated_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':uuid' => $data['uuid'],
            ':name' => $data['name'],
            ':email' => strtolower(trim($data['email'])),
            ':phone' => $data['phone'] ?? null,
            ':password_hash' => $data['password_hash'],
            ':status' => $data['status'] ?? 'active',
            ':must_change_password' => !empty($data['must_change_password']) ? 1 : 0,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $userId = (int)$this->pdo->lastInsertId();

        if (!empty($roles)) {
            $roleStmt = $this->pdo->prepare(
                'INSERT INTO `user_roles` (`user_id`, `role`, `is_active`, `created_at`) VALUES (:user_id, :role, 1, :created_at)'
            );
            foreach ($roles as $role) {
                $roleStmt->execute([
                    ':user_id' => $userId,
                    ':role' => $role,
                    ':created_at' => $now,
                ]);
            }
        }

        return $this->findById($userId);
    }

    public function createSession(
        int $userId,
        string $sessionHash,
        string $expiresAt,
        ?string $userAgentHash = null,
        ?string $ipHash = null
    ): bool {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO `user_sessions` (`user_id`, `session_hash`, `expires_at`, `last_seen_at`, `user_agent_hash`, `ip_hash`)
                VALUES (:user_id, :session_hash, :expires_at, :last_seen_at, :user_agent_hash, :ip_hash)';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':session_hash' => $sessionHash,
            ':expires_at' => $expiresAt,
            ':last_seen_at' => $now,
            ':user_agent_hash' => $userAgentHash,
            ':ip_hash' => $ipHash,
        ]);
    }

    public function findSession(string $sessionHash): ?array
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'SELECT s.*, u.status as user_status, u.must_change_password
                FROM `user_sessions` s
                JOIN `users` u ON u.id = s.user_id
                WHERE s.session_hash = :session_hash
                  AND s.revoked_at IS NULL
                  AND s.expires_at > :now
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':session_hash' => $sessionHash,
            ':now' => $now,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function updateSessionLastSeen(string $sessionHash): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE `user_sessions` SET `last_seen_at` = :now WHERE `session_hash` = :session_hash');
        $stmt->execute([
            ':now' => $now,
            ':session_hash' => $sessionHash,
        ]);
    }

    public function revokeSession(string $sessionHash): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE `user_sessions` SET `revoked_at` = :now WHERE `session_hash` = :session_hash');
        return $stmt->execute([
            ':now' => $now,
            ':session_hash' => $sessionHash,
        ]);
    }

    public function revokeAllSessionsForUser(int $userId): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE `user_sessions` SET `revoked_at` = :now WHERE `user_id` = :user_id AND `revoked_at` IS NULL');
        return $stmt->execute([
            ':now' => $now,
            ':user_id' => $userId,
        ]);
    }

    public function createPasswordResetToken(int $userId, string $tokenHash, string $expiresAt, ?string $requestedIp = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO `password_reset_tokens` (`user_id`, `token_hash`, `expires_at`, `requested_ip`, `created_at`)
                VALUES (:user_id, :token_hash, :expires_at, :requested_ip, :created_at)';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
            ':requested_ip' => $requestedIp,
            ':created_at' => $now,
        ]);
    }

    public function findValidPasswordResetToken(string $tokenHash): ?array
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'SELECT prt.*, u.id as user_id, u.email, u.name, u.status
                FROM `password_reset_tokens` prt
                JOIN `users` u ON u.id = prt.user_id
                WHERE prt.token_hash = :token_hash
                  AND prt.used_at IS NULL
                  AND prt.expires_at > :now
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':token_hash' => $tokenHash,
            ':now' => $now,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function markPasswordResetTokenUsed(int $tokenId): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE `password_reset_tokens` SET `used_at` = :now WHERE `id` = :id');
        return $stmt->execute([
            ':now' => $now,
            ':id' => $tokenId,
        ]);
    }

    public function updatePassword(int $userId, string $newPasswordHash, bool $mustChangePassword = false): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `users` SET `password_hash` = :password_hash, `must_change_password` = :must_change_password, `updated_at` = :now WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $userId,
            ':password_hash' => $newPasswordHash,
            ':must_change_password' => $mustChangePassword ? 1 : 0,
            ':now' => $now,
        ]);
    }

    public function updateStatus(int $userId, string $status): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE `users` SET `status` = :status, `updated_at` = :now WHERE `id` = :id');
        return $stmt->execute([
            ':id' => $userId,
            ':status' => $status,
            ':now' => $now,
        ]);
    }

    public function update(int $userId, array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $fields = ['`updated_at` = :updated_at'];
        $params = [
            ':id' => $userId,
            ':updated_at' => $now,
        ];

        if (isset($data['name'])) {
            $fields[] = '`name` = :name';
            $params[':name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $fields[] = '`email` = :email';
            $params[':email'] = strtolower(trim($data['email']));
        }
        if (array_key_exists('phone', $data)) {
            $fields[] = '`phone` = :phone';
            $params[':phone'] = $data['phone'];
        }
        if (isset($data['status'])) {
            $fields[] = '`status` = :status';
            $params[':status'] = $data['status'];
        }
        if (isset($data['must_change_password'])) {
            $fields[] = '`must_change_password` = :must_change_password';
            $params[':must_change_password'] = $data['must_change_password'] ? 1 : 0;
        }

        $sql = 'UPDATE `users` SET ' . implode(', ', $fields) . ' WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function syncRoles(int $userId, array $roles): void
    {
        $now = date('Y-m-d H:i:s');
        // Delete current roles
        $delStmt = $this->pdo->prepare('DELETE FROM `user_roles` WHERE `user_id` = :user_id');
        $delStmt->execute([':user_id' => $userId]);

        // Insert new roles
        if (!empty($roles)) {
            $insStmt = $this->pdo->prepare(
                'INSERT INTO `user_roles` (`user_id`, `role`, `is_active`, `created_at`) VALUES (:user_id, :role, 1, :created_at)'
            );
            foreach ($roles as $role) {
                $insStmt->execute([
                    ':user_id' => $userId,
                    ':role' => $role,
                    ':created_at' => $now,
                ]);
            }
        }
    }

    /**
     * @return User[]
     */
    public function getAllUsers(
        int $limit = 50,
        int $offset = 0,
        ?string $role = null,
        ?string $status = null,
        ?string $search = null
    ): array {
        $where = [];
        $params = [];

        if (!empty($role)) {
            $where[] = 'u.id IN (SELECT user_id FROM user_roles WHERE role = :role AND is_active = 1)';
            $params[':role'] = $role;
        }

        if (!empty($status)) {
            $where[] = 'u.status = :status';
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $where[] = '(u.name LIKE :search_name OR u.email LIKE :search_email OR u.phone LIKE :search_phone)';
            $params[':search_name'] = '%' . $search . '%';
            $params[':search_email'] = '%' . $search . '%';
            $params[':search_phone'] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT u.* FROM `users` u {$whereClause} ORDER BY u.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $users = [];

        foreach ($rows as $row) {
            $roles = $this->getRolesForUser((int)$row['id']);
            $users[] = User::fromArray($row, $roles);
        }

        return $users;
    }

    public function countUsers(?string $role = null, ?string $status = null, ?string $search = null): int
    {
        $where = [];
        $params = [];

        if (!empty($role)) {
            $where[] = 'u.id IN (SELECT user_id FROM user_roles WHERE role = :role AND is_active = 1)';
            $params[':role'] = $role;
        }

        if (!empty($status)) {
            $where[] = 'u.status = :status';
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $where[] = '(u.name LIKE :search_name OR u.email LIKE :search_email OR u.phone LIKE :search_phone)';
            $params[':search_name'] = '%' . $search . '%';
            $params[':search_email'] = '%' . $search . '%';
            $params[':search_phone'] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) FROM `users` u {$whereClause}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }
}

