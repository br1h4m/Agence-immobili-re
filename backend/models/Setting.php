<?php
// backend/models/Setting.php
declare(strict_types=1);

namespace Immolode\Models;

use PDO;
use Immolode\Config\Database;

class Setting {
    public static function getAll(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'agencyName'         => $rows['agency_name'] ?? 'Immolode',
            'tagline'            => $rows['tagline'] ?? 'Agence immobilière d\'excellence',
            'description'        => $rows['description'] ?? '',
            'phone'              => $rows['phone'] ?? '+213 5XX XX XX XX',
            'phoneRaw'           => $rows['phone_raw'] ?? '+2135XXXXXXXX',
            'email'              => $rows['email'] ?? 'contact@immolode.dz',
            'address'            => $rows['address'] ?? '12 rue Didouche Mourad, Béjaïa 06000, Algérie',
            'whatsapp'           => $rows['whatsapp'] ?? '2135XXXXXXXX',
            'hours'              => $rows['hours'] ?? 'Dim – Jeu : 9h00 – 17h30 · Sam : 9h00 – 13h00',
            'currency'           => $rows['currency'] ?? 'DA',
            'propertiesPerPage'  => (int) ($rows['properties_per_page'] ?? 9),
            'facebook'           => $rows['facebook'] ?? '#',
            'instagram'          => $rows['instagram'] ?? '#'
        ];
    }

    public static function updateAll(array $data): bool {
        $pdo = Database::getConnection();
        $driver = Database::getDriver();

        $map = [
            'agencyName'        => 'agency_name',
            'tagline'           => 'tagline',
            'description'       => 'description',
            'phone'             => 'phone',
            'phoneRaw'          => 'phone_raw',
            'email'             => 'email',
            'address'           => 'address',
            'whatsapp'          => 'whatsapp',
            'hours'             => 'hours',
            'currency'          => 'currency',
            'propertiesPerPage' => 'properties_per_page',
            'facebook'          => 'facebook',
            'instagram'         => 'instagram'
        ];

        $stmt = ($driver === 'mysql')
            ? $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
            : $pdo->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");

        foreach ($map as $camel => $snake) {
            if (isset($data[$camel])) {
                $stmt->execute([$snake, (string) $data[$camel]]);
            } elseif (isset($data[$snake])) {
                $stmt->execute([$snake, (string) $data[$snake]]);
            }
        }

        return true;
    }
}
