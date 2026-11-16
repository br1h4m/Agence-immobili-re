<?php
// backend/config/Config.php
declare(strict_types=1);

namespace Immolode\Config;

class Config {
    // Chemins système
    public const ROOT_PATH = __DIR__ . '/../..';
    public const BACKEND_PATH = __DIR__ . '/..';
    public const UPLOADS_PATH = __DIR__ . '/../../uploads';

    // Base de données MySQL (XAMPP standard)
    public const DB_HOST = '127.0.0.1';
    public const DB_PORT = 3306;
    public const DB_NAME = 'immolode';
    public const DB_USER = 'root';
    public const DB_PASS = '';
    public const DB_CHARSET = 'utf8mb4';

    // Fichier SQLite de secours (si MySQL n'est pas actif)
    public const SQLITE_FILE = __DIR__ . '/../data/immolode.sqlite';

    // Sécurité et session
    public const SESSION_NAME = 'immolode_admin_sid';
    public const SESSION_LIFETIME = 604800; // 7 jours

    // URLs publiques
    public const UPLOADS_URL = '/uploads';

    // Admin par défaut
    public const DEFAULT_ADMIN_EMAIL = 'admin@immolode.dz';
    public const DEFAULT_ADMIN_PASS = 'admin123';
    public const DEFAULT_ADMIN_NAME = 'Karim Benali';
    public const DEFAULT_ADMIN_ROLE = 'Administrateur';
}
