<?php
// backend/models/Property.php
declare(strict_types=1);

namespace Immolode\Models;

use PDO;
use Immolode\Config\Database;

class Property {
    public static function getAll(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "SELECT * FROM properties WHERE 1=1";
        $params = [];

        // Filtre transaction
        if (!empty($filters['transaction'])) {
            $sql .= " AND `transaction` = :transaction";
            $params[':transaction'] = $filters['transaction'];
        }

        // Filtre type
        if (!empty($filters['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $filters['type'];
        }

        // Filtre statut
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        // Filtre wilaya
        if (!empty($filters['wilaya'])) {
            $sql .= " AND wilaya = :wilaya";
            $params[':wilaya'] = $filters['wilaya'];
        }

        // Filtre commune / ville
        if (!empty($filters['city'])) {
            $sql .= " AND city = :city";
            $params[':city'] = $filters['city'];
        }

        // Filtre à la une
        if (isset($filters['is_featured']) && $filters['is_featured'] !== '') {
            $sql .= " AND is_featured = :is_featured";
            $params[':is_featured'] = (int) $filters['is_featured'];
        }

        // Filtre nouveauté
        if (isset($filters['is_new']) && $filters['is_new'] !== '') {
            $sql .= " AND is_new = :is_new";
            $params[':is_new'] = (int) $filters['is_new'];
        }

        // Prix min / max
        if (!empty($filters['minPrice'])) {
            $sql .= " AND price >= :minPrice";
            $params[':minPrice'] = (float) $filters['minPrice'];
        }
        if (!empty($filters['maxPrice'])) {
            $sql .= " AND price <= :maxPrice";
            $params[':maxPrice'] = (float) $filters['maxPrice'];
        }

        // Surface min / max
        if (!empty($filters['minSurface'])) {
            $sql .= " AND surface >= :minSurface";
            $params[':minSurface'] = (float) $filters['minSurface'];
        }
        if (!empty($filters['maxSurface'])) {
            $sql .= " AND surface <= :maxSurface";
            $params[':maxSurface'] = (float) $filters['maxSurface'];
        }

        // Chambres
        if (!empty($filters['bedrooms'])) {
            $sql .= " AND bedrooms >= :bedrooms";
            $params[':bedrooms'] = (int) $filters['bedrooms'];
        }

        // Salles de bain
        if (!empty($filters['bathrooms'])) {
            $sql .= " AND bathrooms >= :bathrooms";
            $params[':bathrooms'] = (int) $filters['bathrooms'];
        }

        // Recherche textuelle
        if (!empty($filters['search']) || !empty($filters['q'])) {
            $query = trim((string) ($filters['search'] ?? $filters['q']));
            $sql .= " AND (title LIKE :q OR reference LIKE :q OR city LIKE :q OR wilaya LIKE :q OR district LIKE :q)";
            $params[':q'] = "%{$query}%";
        }

        // Tri
        $sort = $filters['sort'] ?? 'recent';
        switch ($sort) {
            case 'price-asc':
                $sql .= " ORDER BY price ASC";
                break;
            case 'price-desc':
                $sql .= " ORDER BY price DESC";
                break;
            case 'surface-desc':
                $sql .= " ORDER BY surface DESC";
                break;
            case 'surface-asc':
                $sql .= " ORDER BY surface ASC";
                break;
            case 'recent':
            default:
                $sql .= " ORDER BY created_at DESC, id DESC";
                break;
        }

        // Pagination
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
        $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::formatRow($row) : null;
    }

    public static function getByReference(string $reference): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM properties WHERE reference = ?");
        $stmt->execute([$reference]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::formatRow($row) : null;
    }

    public static function create(array $data): array {
        $pdo = Database::getConnection();

        // Générer une référence si manquante
        if (empty($data['reference'])) {
            $maxId = (int) $pdo->query("SELECT MAX(id) FROM properties")->fetchColumn();
            $data['reference'] = 'IML-' . str_pad((string) ($maxId + 1), 3, '0', STR_PAD_LEFT);
        }

        $features = is_array($data['features'] ?? null)
            ? json_encode($data['features'], JSON_UNESCAPED_UNICODE)
            : ($data['features'] ?? '[]');

        $images = is_array($data['images'] ?? null)
            ? json_encode($data['images'], JSON_UNESCAPED_SLASHES)
            : ($data['images'] ?? '[]');

        // Décomposer la localisation si transmise sous forme d'objet
        $wilaya = $data['wilaya'] ?? ($data['location']['wilaya'] ?? 'Béjaïa');
        $city = $data['city'] ?? ($data['location']['city'] ?? 'Béjaïa');
        $district = $data['district'] ?? ($data['location']['district'] ?? null);

        $stmt = $pdo->prepare("
            INSERT INTO properties (
                reference, title, type, `transaction`, price, price_period,
                wilaya, city, district, surface, land_surface,
                bedrooms, bathrooms, floor, floors, year, garage,
                features, images, description, status, is_featured, is_new
            ) VALUES (
                :reference, :title, :type, :transaction, :price, :price_period,
                :wilaya, :city, :district, :surface, :land_surface,
                :bedrooms, :bathrooms, :floor, :floors, :year, :garage,
                :features, :images, :description, :status, :is_featured, :is_new
            )
        ");

        $stmt->execute([
            ':reference'    => $data['reference'],
            ':title'        => $data['title'] ?? 'Nouveau bien',
            ':type'         => $data['type'] ?? 'Villa',
            ':transaction'  => $data['transaction'] ?? 'Acheter',
            ':price'        => (float) ($data['price'] ?? 0),
            ':price_period' => $data['price_period'] ?? ($data['pricePeriod'] ?? null),
            ':wilaya'       => $wilaya,
            ':city'         => $city,
            ':district'     => $district,
            ':surface'      => (float) ($data['surface'] ?? 0),
            ':land_surface' => isset($data['land_surface']) && $data['land_surface'] !== '' ? (float) $data['land_surface'] : (isset($data['landSurface']) && $data['landSurface'] !== '' ? (float) $data['landSurface'] : null),
            ':bedrooms'     => isset($data['bedrooms']) && $data['bedrooms'] !== '' ? (int) $data['bedrooms'] : null,
            ':bathrooms'    => isset($data['bathrooms']) && $data['bathrooms'] !== '' ? (int) $data['bathrooms'] : null,
            ':floor'        => isset($data['floor']) && $data['floor'] !== '' ? (int) $data['floor'] : null,
            ':floors'       => isset($data['floors']) && $data['floors'] !== '' ? (int) $data['floors'] : null,
            ':year'         => isset($data['year']) && $data['year'] !== '' ? (int) $data['year'] : null,
            ':garage'       => !empty($data['garage']) ? 1 : 0,
            ':features'     => $features,
            ':images'       => $images,
            ':description'  => $data['description'] ?? '',
            ':status'       => $data['status'] ?? 'Disponible',
            ':is_featured'  => !empty($data['is_featured'] ?? $data['isFeatured']) ? 1 : 0,
            ':is_new'       => !empty($data['is_new'] ?? $data['isNew']) ? 1 : 0
        ]);

        $newId = (int) $pdo->lastInsertId();
        return self::getById($newId);
    }

    public static function update(int|string $id, array $data): bool {
        $pdo = Database::getConnection();

        $existing = self::getById($id);
        if (!$existing) {
            return false;
        }

        $wilaya = $data['wilaya'] ?? ($data['location']['wilaya'] ?? $existing['location']['wilaya']);
        $city = $data['city'] ?? ($data['location']['city'] ?? $existing['location']['city']);
        $district = $data['district'] ?? ($data['location']['district'] ?? $existing['location']['district']);

        $features = isset($data['features'])
            ? (is_array($data['features']) ? json_encode($data['features'], JSON_UNESCAPED_UNICODE) : $data['features'])
            : json_encode($existing['features'], JSON_UNESCAPED_UNICODE);

        $images = isset($data['images'])
            ? (is_array($data['images']) ? json_encode($data['images'], JSON_UNESCAPED_SLASHES) : $data['images'])
            : json_encode($existing['images'], JSON_UNESCAPED_SLASHES);

        $stmt = $pdo->prepare("
            UPDATE properties SET
                reference = :reference,
                title = :title,
                type = :type,
                `transaction` = :transaction,
                price = :price,
                price_period = :price_period,
                wilaya = :wilaya,
                city = :city,
                district = :district,
                surface = :surface,
                land_surface = :land_surface,
                bedrooms = :bedrooms,
                bathrooms = :bathrooms,
                floor = :floor,
                floors = :floors,
                year = :year,
                garage = :garage,
                features = :features,
                images = :images,
                description = :description,
                status = :status,
                is_featured = :is_featured,
                is_new = :is_new,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id'           => $id,
            ':reference'    => $data['reference'] ?? $existing['reference'],
            ':title'        => $data['title'] ?? $existing['title'],
            ':type'         => $data['type'] ?? $existing['type'],
            ':transaction'  => $data['transaction'] ?? $existing['transaction'],
            ':price'        => (float) ($data['price'] ?? $existing['price']),
            ':price_period' => $data['price_period'] ?? ($data['pricePeriod'] ?? $existing['pricePeriod']),
            ':wilaya'       => $wilaya,
            ':city'         => $city,
            ':district'     => $district,
            ':surface'      => (float) ($data['surface'] ?? $existing['surface']),
            ':land_surface' => isset($data['land_surface']) && $data['land_surface'] !== '' ? (float) $data['land_surface'] : (isset($data['landSurface']) && $data['landSurface'] !== '' ? (float) $data['landSurface'] : null),
            ':bedrooms'     => isset($data['bedrooms']) && $data['bedrooms'] !== '' ? (int) $data['bedrooms'] : null,
            ':bathrooms'    => isset($data['bathrooms']) && $data['bathrooms'] !== '' ? (int) $data['bathrooms'] : null,
            ':floor'        => isset($data['floor']) && $data['floor'] !== '' ? (int) $data['floor'] : null,
            ':floors'       => isset($data['floors']) && $data['floors'] !== '' ? (int) $data['floors'] : null,
            ':year'         => isset($data['year']) && $data['year'] !== '' ? (int) $data['year'] : null,
            ':garage'       => !empty($data['garage']) ? 1 : 0,
            ':features'     => $features,
            ':images'       => $images,
            ':description'  => $data['description'] ?? $existing['description'],
            ':status'       => $data['status'] ?? $existing['status'],
            ':is_featured'  => !empty($data['is_featured'] ?? $data['isFeatured']) ? 1 : 0,
            ':is_new'       => !empty($data['is_new'] ?? $data['isNew']) ? 1 : 0
        ]);
    }

    public static function updateStatus(int|string $id, string $status): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE properties SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public static function delete(int|string $id): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function formatRow(array $row): array {
        $features = [];
        if (!empty($row['features'])) {
            $decoded = json_decode($row['features'], true);
            $features = is_array($decoded) ? $decoded : [];
        }

        $images = [];
        if (!empty($row['images'])) {
            $decoded = json_decode($row['images'], true);
            $images = is_array($decoded) ? $decoded : [];
        }

        return [
            'id'           => (int) $row['id'],
            'reference'    => $row['reference'],
            'title'        => $row['title'],
            'type'         => $row['type'],
            'transaction'  => $row['transaction'],
            'price'        => (float) $row['price'],
            'pricePeriod'  => $row['price_period'],
            'location'     => [
                'wilaya'   => $row['wilaya'],
                'city'     => $row['city'],
                'district' => $row['district']
            ],
            'surface'      => (float) $row['surface'],
            'landSurface'  => isset($row['land_surface']) && $row['land_surface'] !== null ? (float) $row['land_surface'] : null,
            'bedrooms'     => isset($row['bedrooms']) && $row['bedrooms'] !== null ? (int) $row['bedrooms'] : null,
            'bathrooms'    => isset($row['bathrooms']) && $row['bathrooms'] !== null ? (int) $row['bathrooms'] : null,
            'floor'        => isset($row['floor']) && $row['floor'] !== null ? (int) $row['floor'] : null,
            'floors'       => isset($row['floors']) && $row['floors'] !== null ? (int) $row['floors'] : null,
            'year'         => isset($row['year']) && $row['year'] !== null ? (int) $row['year'] : null,
            'garage'       => (bool) $row['garage'],
            'features'     => $features,
            'images'       => $images,
            'description'  => $row['description'],
            'status'       => $row['status'],
            'isFeatured'   => (bool) $row['is_featured'],
            'isNew'        => (bool) $row['is_new'],
            'createdAt'    => $row['created_at'],
            'updatedAt'    => $row['updated_at'] ?? $row['created_at']
        ];
    }
}
