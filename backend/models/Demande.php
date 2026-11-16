<?php
// backend/models/Demande.php
declare(strict_types=1);

namespace Immolode\Models;

use PDO;
use Immolode\Config\Database;

class Demande {
    public static function getAll(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "SELECT * FROM demandes WHERE 1=1";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search']) || !empty($filters['q'])) {
            $q = trim((string) ($filters['search'] ?? $filters['q']));
            $sql .= " AND (nom LIKE :q OR email LIKE :q OR phone LIKE :q OR subject LIKE :q)";
            $params[':q'] = "%{$q}%";
        }

        $sql .= " ORDER BY created_at DESC, id DESC";

        if (!empty($filters['limit'])) {
            $limit = (int) $filters['limit'];
            $offset = (int) ($filters['offset'] ?? 0);
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([self::class, 'formatRow'], $rows);
    }

    public static function getById(int|string $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM demandes WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::formatRow($row) : null;
    }

    public static function create(array $data): array {
        $pdo = Database::getConnection();

        $details = null;
        if (isset($data['details'])) {
            $details = is_array($data['details'])
                ? json_encode($data['details'], JSON_UNESCAPED_UNICODE)
                : (string) $data['details'];
        }

        $nom = trim((string) ($data['nom'] ?? ''));
        if (empty($nom) && (!empty($data['firstname']) || !empty($data['lastname']))) {
            $nom = trim(($data['firstname'] ?? '') . ' ' . ($data['lastname'] ?? ''));
        }
        if (empty($nom)) {
            $nom = 'Visiteur';
        }

        $stmt = $pdo->prepare("
            INSERT INTO demandes (type, nom, email, phone, subject, message, details, status)
            VALUES (:type, :nom, :email, :phone, :subject, :message, :details, :status)
        ");

        $stmt->execute([
            ':type'    => $data['type'] ?? 'Contact',
            ':nom'     => $nom,
            ':email'   => $data['email'] ?? '',
            ':phone'   => $data['phone'] ?? '',
            ':subject' => $data['subject'] ?? 'Prise de contact',
            ':message' => $data['message'] ?? '',
            ':details' => $details,
            ':status'  => $data['status'] ?? 'Nouveau'
        ]);

        $newId = (int) $pdo->lastInsertId();
        return self::getById($newId);
    }

    public static function updateStatus(int|string $id, string $status): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE demandes SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public static function delete(int|string $id): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM demandes WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function formatRow(array $row): array {
        $details = null;
        if (!empty($row['details'])) {
            $decoded = json_decode($row['details'], true);
            $details = is_array($decoded) ? $decoded : $row['details'];
        }

        return [
            'id'        => (int) $row['id'],
            'type'      => $row['type'],
            'nom'       => $row['nom'],
            'email'     => $row['email'],
            'phone'     => $row['phone'],
            'subject'   => $row['subject'],
            'message'   => $row['message'],
            'details'   => $details,
            'status'    => $row['status'],
            'createdAt' => $row['created_at']
        ];
    }
}
