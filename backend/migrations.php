<?php
/**
 * ============================================================================
 * backend/migrations.php - Migrations de schéma et initialisation CMS
 * ============================================================================
 * Exécute les ajouts de colonnes nécessaires (is_read, avatar) et
 * initialise les clés de paramètres CMS si elles n'existent pas.
 * Utilise MySQLi pur avec requêtes préparées (sans PDO).
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

echo "Exécution des migrations Immolode...\n";

// 1. Ajouter la colonne is_read dans demandes si absente
$checkDemandesCol = $conn->query("SHOW COLUMNS FROM `demandes` LIKE 'is_read'");
if ($checkDemandesCol->num_rows === 0) {
    $conn->query("ALTER TABLE `demandes` ADD COLUMN `is_read` TINYINT(1) DEFAULT 0 AFTER `statut`");
    echo "Colonne is_read ajoutée à la table demandes.\n";
}

// 2. Ajouter la colonne is_read dans fiches_client si absente
$checkFichesCol = $conn->query("SHOW COLUMNS FROM `fiches_client` LIKE 'is_read'");
if ($checkFichesCol->num_rows === 0) {
    $conn->query("ALTER TABLE `fiches_client` ADD COLUMN `is_read` TINYINT(1) DEFAULT 0 AFTER `statut`");
    echo "Colonne is_read ajoutée à la table fiches_client.\n";
}

// 3. Ajouter la colonne avatar dans utilisateurs si absente
$checkUserCol = $conn->query("SHOW COLUMNS FROM `utilisateurs` LIKE 'avatar'");
if ($checkUserCol->num_rows === 0) {
    $conn->query("ALTER TABLE `utilisateurs` ADD COLUMN `avatar` VARCHAR(255) DEFAULT NULL AFTER `actif`");
    echo "Colonne avatar ajoutée à la table utilisateurs.\n";
}

// 4. Initialisation des paramètres CMS dans la table parametres
$cmsKeys = [
    'agency_name' => 'Imolode',
    'tagline' => 'Agence Immobilière Agréée d\'Exception',
    'description' => 'Cabinet d\'expertise et de transactions immobilières haut de gamme.',
    'agence_telephone' => '+213 555 12 34 56',
    'email' => 'contact@immolode.dz',
    'address' => '04 Boulevard des Frères Bouaouina, Béjaïa 06000',
    'whatsapp' => '+213 555 12 34 56',
    'hours' => 'Dimanche – Jeudi : 08h30 – 17h30',
    'hero_title' => 'L\'immobilier d\'exception en Algérie',
    'hero_subtitle' => 'Découvrez une sélection exclusive d\'appartements de haut standing, villas d\'architecte et locaux commerciaux.',
    'hero_image' => 'https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=1200&q=75',
    'about_title' => 'L\'excellence immobilière à votre service depuis 2018',
    'about_text' => 'Fondée à Béjaïa et rayonnant à travers les plus prestigieuses wilayas d\'Algérie, l\'agence Immolode redéfinit les standards de l\'immobilier haut de gamme. Notre équipe met son expertise au service de vos projets de vie et d\'investissement.',
    'about_image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=75',
    'service_achat_titre' => 'Acquisition sur mesure',
    'service_achat_desc' => 'Accédez à des biens d\'exception rigoureusement sélectionnés et certifiés juridiquement (actes notariés et livrets fonciers garantis).',
    'service_location_titre' => 'Location premium',
    'service_location_desc' => 'Des résidences meublées et locaux professionnels prêts à vivre ou travailler, sélectionnés pour leur confort et leur emplacement stratégique.',
    'service_estimation_titre' => 'Estimation & Conseil',
    'service_estimation_desc' => 'Une évaluation rigoureuse et réaliste de votre bien, alignée sur les dynamiques réelles du marché immobilier algérien.'
];

$stmtCheck = $conn->prepare("SELECT `cle` FROM `parametres` WHERE `cle` = ? LIMIT 1");
$stmtInsert = $conn->prepare("INSERT INTO `parametres` (`cle`, `valeur`) VALUES (?, ?)");

foreach ($cmsKeys as $k => $v) {
    $stmtCheck->bind_param("s", $k);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows === 0) {
        $stmtInsert->bind_param("ss", $k, $v);
        $stmtInsert->execute();
        echo "Paramètre CMS initialisé : $k\n";
    }
}

echo "Migrations exécutées avec succès !\n";
