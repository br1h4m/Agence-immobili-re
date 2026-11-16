<?php
// backend/models/User.php
declare(strict_types=1);

namespace Immolode\Models;

use PDO;
use Immolode\Config\Database;

class User {
    public static function authenticate(string $email, string $password): ?array {
        $pdo = Database::getConnection();
        $email = trim(strtolower($email));

        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = ? AND actif = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        if (password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            return self::formatRow($user);
        }

        return null;
    }

    public static function getAll(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, nom, email, role, poste, phone, actif, created_at FROM users ORDER BY id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([self::class, 'formatRow'], $rows);
    }

    public static function getById(int|string $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, nom, email, role, poste, phone, actif, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::formatRow($row) : null;
    }

    public static function getByEmail(string $email): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, nom, email, role, poste, phone, actif, created_at FROM users WHERE LOWER(email) = ?");
        $stmt->execute([trim(strtolower($email))]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::formatRow($row) : null;
    }

    public static function create(array $data): array {
        $pdo = Database::getConnection();

        $pass = $data['password'] ?? 'admin123';
        $passHash = password_hash($pass, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            INSERT INTO users (nom, email, password_hash, role, poste, phone, actif)
            VALUES (:nom, :email, :password_hash, :role, :poste, :phone, :actif)
        ");

        $stmt->execute([
            ':nom'           => trim((string) ($data['nom'] ?? '')),
            ':email'         => trim(strtolower((string) ($data['email'] ?? ''))),
            ':password_hash' => $passHash,
            ':role'          => $data['role'] ?? 'Agent commercial',
            ':poste'         => $data['poste'] ?? null,
            ':phone'         => $data['phone'] ?? null,
            ':actif'         => isset($data['actif']) ? (int) (bool) $data['actif'] : 1
        ]);

        $newId = (int) $pdo->lastInsertId();
        return self::getById($newId);
    }

    public static function update(int|string $id, array $data): bool {
        $pdo = Database::getConnection();

        $user = self::getById($id);
        if (!$user) return false;

        $sql = "UPDATE users SET nom = :nom, email = :email, role = :role, poste = :poste, phone = :phone, actif = :actif";
        $params = [
            ':id'    => $id,
            ':nom'   => trim((string) ($data['nom'] ?? $user['nom'])),
            ':email' => trim(strtolower((string) ($data['email'] ?? $user['email']))),
            ':role'  => $data['role'] ?? $user['role'],
            ':poste' => $data['poste'] ?? $user['poste'],
            ':phone' => $data['phone'] ?? $user['phone'],
            ':actif' => isset($data['actif']) ? (int) (bool) $data['actif'] : ($user['actif'] ? 1 : 0)
        ];

        if (!empty($data['password'])) {
            $sql .= ", password_hash = :password_hash";
            $params[':password_hash'] = password_hash((string) $data['password'], PASSWORD_BCRYPT);
        }

        $sql .= " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int|string $id): bool {
        $pdo = Database::getConnection();
        // Empêcher la suppression du premier compte administrateur
        if ((int) $id === 1) {
            return false;
        }
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function formatRow(array $row): array {
        return [
            'id'        => (int) $row['id'],
            'nom'       => $row['nom'],
            'email'     => $row['email'],
            'role'      => $row['role'],
            'poste'     => $row['poste'] ?? '',
            'phone'     => $row['phone'] ?? '',
            'actif'     => (bool) ($row['actif'] ?? true),
            'createdAt' => $row['created_at'] ?? null
        ];
    }
}
