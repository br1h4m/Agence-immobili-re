<?php
// backend/models/ClientFiche.php
declare(strict_types=1);

namespace Immolode\Models;

use PDO;
use Immolode\Config\Database;
use Immolode\Helpers\Uploader;

class ClientFiche {
    public static function getAll(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "SELECT * FROM client_fiches WHERE 1=1";
        $params = [];

        if (!empty($filters['transaction'])) {
            $sql .= " AND `transaction` = :transaction";
            $params[':transaction'] = $filters['transaction'];
        }

        if (!empty($filters['typeBien']) || !empty($filters['type_bien'])) {
            $sql .= " AND type_bien = :type_bien";
            $params[':type_bien'] = $filters['typeBien'] ?? $filters['type_bien'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search']) || !empty($filters['q'])) {
            $q = trim((string) ($filters['search'] ?? $filters['q']));
            $sql .= " AND (reference LIKE :q OR nom LIKE :q OR prenom LIKE :q OR telephone LIKE :q OR email LIKE :q)";
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
        $stmt = $pdo->prepare("SELECT * FROM client_fiches WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::formatRow($row) : null;
    }

    public static function getByReference(string $ref): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM client_fiches WHERE reference = ?");
        $stmt->execute([$ref]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::formatRow($row) : null;
    }

    public static function create(array $data): array {
        $pdo = Database::getConnection();

        // Référence unique
        $year = date('Y');
        $random = random_int(1000, 9999);
        $reference = $data['reference'] ?? "IML-DOS-{$year}-{$random}";

        // Traitement de la signature (Base64 sauvegardé sous forme d'image sur disque si possible)
        $signatureData = $data['signature'] ?? ($data['signature_data'] ?? null);
        if ($signatureData && str_starts_with($signatureData, 'data:image')) {
            $savedSignaturePath = Uploader::saveBase64Image($signatureData, 'signatures', 'sig');
            if ($savedSignaturePath) {
                $signatureData = $savedSignaturePath;
            }
        }

        // Pièce d'identité
        $pieceName = $data['pieceIdentite']['name'] ?? ($data['piece_identite_name'] ?? null);
        $pieceSize = $data['pieceIdentite']['size'] ?? ($data['piece_identite_size'] ?? null);
        $pieceType = $data['pieceIdentite']['type'] ?? ($data['piece_identite_type'] ?? null);
        $piecePath = $data['pieceIdentite']['dataUrl'] ?? ($data['piece_identite_path'] ?? null);

        // Si la pièce d'identité est un DataURL base64 volumineux, on l'écrit également sur disque
        if ($piecePath && str_starts_with($piecePath, 'data:')) {
            $savedDocPath = Uploader::saveBase64Image($piecePath, 'documents', 'doc');
            if ($savedDocPath) {
                $piecePath = $savedDocPath;
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO client_fiches (
                reference, nom, prenom, telephone, email, `transaction`, type_bien, property_id,
                piece_identite_name, piece_identite_size, piece_identite_type, piece_identite_path,
                signature_data, engagement_accepte, status, notes
            ) VALUES (
                :reference, :nom, :prenom, :telephone, :email, :transaction, :type_bien, :property_id,
                :piece_identite_name, :piece_identite_size, :piece_identite_type, :piece_identite_path,
                :signature_data, :engagement_accepte, :status, :notes
            )
        ");

        $stmt->execute([
            ':reference'           => $reference,
            ':nom'                 => trim((string) ($data['nom'] ?? '')),
            ':prenom'              => trim((string) ($data['prenom'] ?? '')),
            ':telephone'           => trim((string) ($data['telephone'] ?? '')),
            ':email'               => trim((string) ($data['email'] ?? '')),
            ':transaction'         => $data['transaction'] ?? 'Demande',
            ':type_bien'           => $data['typeBien'] ?? ($data['type_bien'] ?? 'Appartement'),
            ':property_id'         => !empty($data['property_id'] ?? ($data['propertyId'] ?? null)) ? (int) ($data['property_id'] ?? $data['propertyId']) : null,
            ':piece_identite_name' => $pieceName,
            ':piece_identite_size' => $pieceSize ? (int) $pieceSize : null,
            ':piece_identite_type' => $pieceType,
            ':piece_identite_path' => $piecePath,
            ':signature_data'      => $signatureData,
            ':engagement_accepte'  => !empty($data['engagementAccepte'] ?? true) ? 1 : 0,
            ':status'              => $data['status'] ?? 'Nouveau',
            ':notes'               => $data['notes'] ?? null
        ]);

        $newId = (int) $pdo->lastInsertId();
        return self::getById($newId);
    }

    public static function updateStatus(int|string $id, string $status): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE client_fiches SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public static function delete(int|string $id): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM client_fiches WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function formatRow(array $row): array {
        return [
            'id'                => (int) $row['id'],
            'reference'         => $row['reference'],
            'nom'               => $row['nom'],
            'prenom'            => $row['prenom'],
            'telephone'         => $row['telephone'],
            'email'             => $row['email'],
            'transaction'       => $row['transaction'],
            'typeBien'          => $row['type_bien'],
            'propertyId'        => $row['property_id'] ? (int) $row['property_id'] : null,
            'pieceIdentite'     => [
                'name'    => $row['piece_identite_name'],
                'size'    => $row['piece_identite_size'] ? (int) $row['piece_identite_size'] : null,
                'type'    => $row['piece_identite_type'],
                'dataUrl' => $row['piece_identite_path']
            ],
            'signature'         => $row['signature_data'],
            'engagementAccepte' => (bool) $row['engagement_accepte'],
            'status'            => $row['status'],
            'notes'             => $row['notes'],
            'createdAt'         => $row['created_at']
        ];
    }
}
