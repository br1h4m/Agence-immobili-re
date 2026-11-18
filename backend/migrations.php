<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

echo "Connexion MySQL réussie.\n";

// 1. Colonnes is_read pour demandes et fiches_client
$conn->query("ALTER TABLE `demandes` ADD COLUMN IF NOT EXISTS `is_read` TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE `fiches_client` ADD COLUMN IF NOT EXISTS `is_read` TINYINT(1) DEFAULT 0");

// 2. Colonne avatar pour utilisateurs
$conn->query("ALTER TABLE `utilisateurs` ADD COLUMN IF NOT EXISTS `avatar` VARCHAR(255) DEFAULT NULL");

// 3. Mettre à jour les anciennes demandes lues/non-lues selon statut
$conn->query("UPDATE `demandes` SET `is_read` = 1 WHERE `statut` NOT IN ('Nouveau', 'En attente')");
$conn->query("UPDATE `fiches_client` SET `is_read` = 1 WHERE `statut` NOT IN ('Nouveau', 'En attente')");

// 4. Initialisation des paramètres CMS s'ils n'existent pas
$cmsKeys = [
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
