<?php
/**
 * config/db.php - Connexion MySQLi à la base de données immolode + fonctions utilitaires de base
 */
declare(strict_types=1);

if (!defined('BASE_URL')) {
    define('BASE_URL', '/immolode-php');
}
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'immolode';
$db_port = 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, '', $db_port);
    $conn->query("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($db_name);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die("<div style='font-family:sans-serif;padding:20px;background:#FFF0F0;border:1px solid #FFC0C0;color:#900;border-radius:8px;'>
            <h3>Erreur de connexion à la base de données MySQL</h3>
            <p>Vérifiez que MySQL est démarré dans XAMPP.</p>
            <p><strong>Détail :</strong> " . htmlspecialchars($e->getMessage()) . "</p>
         </div>");
}

function e($str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function format_price($price, ?string $period = null): string {
    if ($price === null || $price === '' || !is_numeric($price)) {
        return 'Prix sur demande';
    }
    $formatted = number_format((float)$price, 0, ',', ' ') . ' DA';
    if (!empty($period)) {
        $formatted .= ' / ' . e($period);
    }
    return $formatted;
}

function format_surface($surface): string {
    if ($surface === null || $surface === '' || !is_numeric($surface) || (float)$surface <= 0) {
        return '—';
    }
    return number_format((float)$surface, 0, ',', ' ') . ' m²';
}

function format_date(?string $dateStr): string {
    if (empty($dateStr)) return '—';
    $ts = strtotime($dateStr);
    if (!$ts) return e($dateStr);
    $mois = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    return date('j', $ts) . ' ' . $mois[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

function format_datetime(?string $dateStr): string {
    if (empty($dateStr)) return '—';
    $ts = strtotime($dateStr);
    if (!$ts) return e($dateStr);
    return format_date($dateStr) . ' à ' . date('H:i', $ts);
}

/**
 * Normalise une URL d'image/document stockée en BDD pour qu'elle fonctionne dans /immolode-php/
 */
function asset_url(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
        return $path;
    }
    if (str_starts_with($path, BASE_URL . '/')) {
        return $path;
    }
    if (str_starts_with($path, '/')) {
        return BASE_URL . $path;
    }
    return BASE_URL . '/uploads/' . ltrim($path, '/');
}

/**
 * Récupère tous les paramètres de l'agence depuis la table `parametres`
 */
function load_site_settings(mysqli $conn): array {
    $settings = [];
    $res = $conn->query("SELECT `cle`, `valeur` FROM `parametres`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $settings[$row['cle']] = $row['valeur'];
        }
    }
    return $settings;
}
