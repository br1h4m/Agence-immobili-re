<?php
// backend/config/Database.php
declare(strict_types=1);

namespace Immolode\Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // 1. Tenter la connexion MySQL
        try {
            $dsnCheck = sprintf(
                'mysql:host=%s;port=%d;charset=%s',
                Config::DB_HOST,
                Config::DB_PORT,
                Config::DB_CHARSET
            );
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 2, // 2 secondes max de test
            ];

            $pdoCheck = new PDO($dsnCheck, Config::DB_USER, Config::DB_PASS, $options);
            // S'assurer que la base existe
            $pdoCheck->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                Config::DB_NAME
            ));

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                Config::DB_HOST,
                Config::DB_PORT,
                Config::DB_NAME,
                Config::DB_CHARSET
            );

            self::$instance = new PDO($dsn, Config::DB_USER, Config::DB_PASS, $options);
            self::$driver = 'mysql';
        } catch (PDOException $e) {
            // 2. Repli automatique et transparent vers SQLite si MySQL est indisponible
            $sqliteDir = dirname(Config::SQLITE_FILE);
            if (!is_dir($sqliteDir)) {
                mkdir($sqliteDir, 0777, true);
            }

            $dsn = 'sqlite:' . Config::SQLITE_FILE;
            self::$instance = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::$instance->exec('PRAGMA foreign_keys = ON;');
            self::$driver = 'sqlite';
        }

        // Vérification et initialisation automatique du schéma si nécessaire
        self::ensureSchema(self::$instance);

        return self::$instance;
    }

    public static function getDriver(): string {
        return self::$driver;
    }

    public static function isSQLite(): bool {
        return self::$driver === 'sqlite';
    }

    private static function ensureSchema(PDO $pdo): void {
        $tableExists = false;
        try {
            if (self::$driver === 'sqlite') {
                $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='properties'");
                $tableExists = (bool) $stmt->fetch();
            } else {
                $stmt = $pdo->query("SHOW TABLES LIKE 'properties'");
                $tableExists = (bool) $stmt->fetch();
            }
        } catch (\Throwable $t) {
            $tableExists = false;
        }

        if (!$tableExists) {
            require_once __DIR__ . '/../database/Schema.php';
            \Immolode\Database\Schema::createTables($pdo, self::$driver);
            \Immolode\Database\Schema::seedInitialData($pdo, self::$driver);
        }
    }
}
