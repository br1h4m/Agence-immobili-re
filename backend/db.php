<?php
/**
 * ============================================================================
 * backend/db.php - Connexion à la base de données MySQL avec MySQLi
 * ============================================================================
 * Ce fichier établit la connexion avec la base de données MySQL de XAMPP.
 * Utilise l'extension MySQLi (sans PDO) avec gestion simple des erreurs.
 * Modifiable facilement en cas de changement d'hébergeur ou d'identifiants.
 */

// Paramètres de connexion MySQL (Configuration standard XAMPP)
$db_host = '127.0.0.1';     // Adresse du serveur MySQL (localhost)
$db_user = 'root';          // Utilisateur MySQL par défaut sous XAMPP
$db_pass = '';              // Mot de passe vide par défaut sous XAMPP
$db_name = 'immolode';       // Nom de la base de données du projet
$db_port = 3306;            // Port standard MySQL

// Activer le rapport d'erreurs MySQLi sous forme d'exceptions pour la clarté
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 1. Connexion au serveur MySQL
    $conn = new mysqli($db_host, $db_user, $db_pass, '', $db_port);

    // 2. Création automatique de la base de données si elle n'existe pas encore
    $conn->query("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // 3. Sélection de la base de données immolode
    $conn->select_db($db_name);

    // 4. Encodage des échanges en UTF-8 (support des accents français et caractères spéciaux)
    $conn->set_charset('utf8mb4');

} catch (mysqli_sql_exception $e) {
    // En cas d'erreur de connexion, affichage d'un message clair et arrêt
    die("<div style='font-family:sans-serif;padding:20px;background:#FFF0F0;border:1px solid #FFC0C0;color:#900;border-radius:8px;'>
            <h3>Erreur de connexion à la base de données MySQL</h3>
            <p>Impossible de se connecter au serveur MySQL (XAMPP). Vérifiez que le module MySQL est bien démarré dans le panneau de contrôle XAMPP.</p>
            <p><strong>Détail technique :</strong> " . htmlspecialchars($e->getMessage()) . "</p>
         </div>");
}

/**
 * Fonction utilitaire simple pour échapper les sorties HTML (évite les failles XSS)
 *
 * @param mixed $str
 * @return string
 */
function e($str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Fonction utilitaire simple pour formater un prix en Dinars Algériens (DA)
 *
 * @param float|int|string $price
 * @param string|null $period
 * @return string
 */
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
