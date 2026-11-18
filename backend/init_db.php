<?php
/**
 * ============================================================================
 * backend/init_db.php - Initialisation des tables MySQL et données de départ
 * ============================================================================
 * Exécute la création des tables MySQL sans PDO (MySQLi pur).
 * Peut être exécuté directement en ligne de commande :
 *    php backend/init_db.php
 * ou dans le navigateur :
 *    http://localhost/backend/init_db.php
 */

require_once __DIR__ . '/db.php';

echo "<h2>Initialisation de la base de données MySQL (Immolode)</h2><pre>";

// ----------------------------------------------------------------------------
// 1. Table des annonces immobilières (properties)
// ----------------------------------------------------------------------------
echo "Création de la table `properties`... ";
$conn->query("
    CREATE TABLE IF NOT EXISTS `properties` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `reference` VARCHAR(50) NOT NULL UNIQUE,
        `titre` VARCHAR(255) NOT NULL,
        `type` VARCHAR(50) NOT NULL,
        `transaction` VARCHAR(50) NOT NULL,
        `prix` DECIMAL(15, 2) NOT NULL,
        `periode_prix` VARCHAR(20) DEFAULT NULL,
        `wilaya` VARCHAR(100) NOT NULL,
        `commune` VARCHAR(100) NOT NULL,
        `quartier` VARCHAR(150) DEFAULT NULL,
        `surface` DECIMAL(10, 2) NOT NULL,
        `surface_terrain` DECIMAL(10, 2) DEFAULT NULL,
        `chambres` INT DEFAULT NULL,
        `salles_de_bain` INT DEFAULT NULL,
        `etage` INT DEFAULT NULL,
        `etages` INT DEFAULT NULL,
        `annee` INT DEFAULT NULL,
        `garage` TINYINT(1) DEFAULT 0,
        `equipements` TEXT DEFAULT NULL,
        `images` TEXT DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `statut` VARCHAR(50) DEFAULT 'Disponible',
        `a_la_une` TINYINT(1) DEFAULT 0,
        `nouveau` TINYINT(1) DEFAULT 0,
        `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "OK\n";

// ----------------------------------------------------------------------------
// 2. Table des demandes de contact et d'estimation
// ----------------------------------------------------------------------------
echo "Création de la table `demandes`... ";
$conn->query("
    CREATE TABLE IF NOT EXISTS `demandes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `type` VARCHAR(50) NOT NULL,
        `nom` VARCHAR(150) NOT NULL,
        `email` VARCHAR(150) NOT NULL,
        `telephone` VARCHAR(50) NOT NULL,
        `sujet` VARCHAR(255) DEFAULT NULL,
        `message` TEXT DEFAULT NULL,
        `bien_id` INT DEFAULT NULL,
        `details_bien` TEXT DEFAULT NULL,
        `statut` VARCHAR(50) DEFAULT 'Nouveau',
        `is_read` TINYINT(1) DEFAULT 0,
        `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "OK\n";

// ----------------------------------------------------------------------------
// 3. Table des fiches client (dossiers de visite et mandats)
// ----------------------------------------------------------------------------
echo "Création de la table `fiches_client`... ";
$conn->query("
    CREATE TABLE IF NOT EXISTS `fiches_client` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `reference` VARCHAR(50) NOT NULL UNIQUE,
        `nom` VARCHAR(100) NOT NULL,
        `prenom` VARCHAR(100) NOT NULL,
        `telephone` VARCHAR(50) NOT NULL,
        `email` VARCHAR(150) NOT NULL,
        `transaction` VARCHAR(50) NOT NULL,
        `type_bien` VARCHAR(50) NOT NULL,
        `id_bien` INT DEFAULT NULL,
        `piece_identite` VARCHAR(255) DEFAULT NULL,
        `signature` MEDIUMTEXT DEFAULT NULL,
        `statut` VARCHAR(50) DEFAULT 'Nouveau',
        `is_read` TINYINT(1) DEFAULT 0,
        `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "OK\n";

// ----------------------------------------------------------------------------
// 4. Table des utilisateurs (Gérant, Administrateurs, Agents)
// ----------------------------------------------------------------------------
echo "Création de la table `utilisateurs`... ";
$conn->query("
    CREATE TABLE IF NOT EXISTS `utilisateurs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nom` VARCHAR(150) NOT NULL,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `mot_de_passe` VARCHAR(255) NOT NULL,
        `role` VARCHAR(50) DEFAULT 'Agent commercial',
        `poste` VARCHAR(100) DEFAULT NULL,
        `telephone` VARCHAR(50) DEFAULT NULL,
        `avatar` VARCHAR(255) DEFAULT NULL,
        `actif` TINYINT(1) DEFAULT 1,
        `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "OK\n";

// ----------------------------------------------------------------------------
// 5. Table des paramètres généraux de l'agence
// ----------------------------------------------------------------------------
echo "Création de la table `parametres`... ";
$conn->query("
    CREATE TABLE IF NOT EXISTS `parametres` (
        `cle` VARCHAR(100) PRIMARY KEY,
        `valeur` TEXT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "OK\n";

// ============================================================================
// INSERTION DES DONNÉES DE DÉPART (si les tables sont vides)
// ============================================================================

// A. Administrateur par défaut
$checkAdmin = $conn->query("SELECT id FROM `utilisateurs` WHERE `email` = 'admin@immolode.dz' OR `email` = 'gahamhocine2@gmail.com'");
if ($checkAdmin->num_rows === 0) {
    echo "Insertion de l'administrateur par défaut... ";
    $passHash = password_hash('admin123', PASSWORD_BCRYPT);
    
    // Requête préparée avec MySQLi
    $stmtAdmin = $conn->prepare("
        INSERT INTO `utilisateurs` (`nom`, `email`, `mot_de_passe`, `role`, `poste`, `telephone`, `actif`)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    $nomAdmin = 'G. Hocine';
    $emailAdmin = 'gahamhocine2@gmail.com';
    $roleAdmin = 'Gérant';
    $posteAdmin = 'Gérant';
    $telAdmin = '+213 550 12 34 56';
    $stmtAdmin->bind_param('ssssss', $nomAdmin, $emailAdmin, $passHash, $roleAdmin, $posteAdmin, $telAdmin);
    $stmtAdmin->execute();
    $stmtAdmin->close();

    // Compte supplémentaire
    $stmtAdmin2 = $conn->prepare("
        INSERT INTO `utilisateurs` (`nom`, `email`, `mot_de_passe`, `role`, `poste`, `telephone`, `actif`)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    $nomAdmin2 = 'Karim Benali';
    $emailAdmin2 = 'admin@immolode.dz';
    $roleAdmin2 = 'Administrateur';
    $posteAdmin2 = 'Directeur général';
    $telAdmin2 = '+213 550 12 34 56';
    $stmtAdmin2->bind_param('ssssss', $nomAdmin2, $emailAdmin2, $passHash, $roleAdmin2, $posteAdmin2, $telAdmin2);
    $stmtAdmin2->execute();
    $stmtAdmin2->close();
    echo "OK\n";
}

// B. Paramètres de l'agence
$checkParams = $conn->query("SELECT cle FROM `parametres` LIMIT 1");
if ($checkParams->num_rows === 0) {
    echo "Insertion des paramètres de l'agence... ";
    $params = [
        'agency_name' => 'Imolode',
        'tagline'     => 'Agence Immobilière Agréée',
        'phone'       => '0555 12 34 56',
        'email'       => 'contact@immolode.dz',
        'address'     => '12 rue Didouche Mourad, Béjaïa 06000, Algérie',
        'manager'     => 'G. Hocine',
        'hours'       => 'Dim – Jeu : 9h00 – 17h30 · Sam : 9h00 – 13h00',
        'whatsapp'    => '213555123456',
        'facebook'    => 'https://facebook.com/imolode'
    ];
    $stmtParam = $conn->prepare("INSERT INTO `parametres` (`cle`, `valeur`) VALUES (?, ?)");
    foreach ($params as $k => $v) {
        $stmtParam->bind_param('ss', $k, $v);
        $stmtParam->execute();
    }
    $stmtParam->close();
    echo "OK\n";
}

// C. Annonces immobilières initiales
$checkProps = $conn->query("SELECT id FROM `properties` LIMIT 1");
if ($checkProps->num_rows === 0) {
    echo "Insertion des biens immobiliers dans MySQL... ";

    $annonces = [
        [
            'reference'        => 'IML-001',
            'titre'            => 'Villa contemporaine avec piscine et jardin',
            'type'             => 'Villa',
            'transaction'      => 'Acheter',
            'prix'             => 25000000.00,
            'periode_prix'     => null,
            'wilaya'           => 'Béjaïa',
            'commune'          => 'Béjaïa',
            'quartier'         => 'Tichy',
            'surface'          => 180.00,
            'surface_terrain'  => 400.00,
            'chambres'         => 4,
            'salles_de_bain'   => 2,
            'etage'            => 0,
            'etages'           => 2,
            'annee'            => 2021,
            'garage'           => 1,
            'equipements'      => json_encode(['Jardin', 'Piscine', 'Terrasse', 'Garage', 'Climatisation'], JSON_UNESCAPED_UNICODE),
            'images'           => json_encode([
                'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80'
            ], JSON_UNESCAPED_SLASHES),
            'description'      => "Située dans le quartier résidentiel et calme de Tichy à Béjaïa, cette magnifique villa de 180 m² habitables propose des finitions modernes et de très grands volumes. Elle offre un salon lumineux ouvert sur terrasse et piscine, 4 chambres confortables et un garage fermé.",
            'statut'           => 'Disponible',
            'a_la_une'         => 1,
            'nouveau'          => 1
        ],
        [
            'reference'        => 'IML-002',
            'titre'            => 'Appartement F4 vue panoramique sur mer',
            'type'             => 'Appartement',
            'transaction'      => 'Louer',
            'prix'             => 85000.00,
            'periode_prix'     => 'mois',
            'wilaya'           => 'Béjaïa',
            'commune'          => 'Béjaïa',
            'quartier'         => 'Boulevard Amirouche',
            'surface'          => 95.00,
            'surface_terrain'  => null,
            'chambres'         => 3,
            'salles_de_bain'   => 1,
            'etage'            => 4,
            'etages'           => 6,
            'annee'            => 2020,
            'garage'           => 0,
            'equipements'      => json_encode(['Vue mer', 'Balcon', 'Ascenseur', 'Climatisation', 'Cuisine équipée'], JSON_UNESCAPED_UNICODE),
            'images'           => json_encode([
                'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80'
            ], JSON_UNESCAPED_SLASHES),
            'description'      => "Très bel appartement F4 refait à neuf, situé en plein centre-ville sur le boulevard Amirouche. Vue imprenable sur le port et le golfe de Béjaïa, double vitrage, cuisine moderne équipée et ascenseur.",
            'statut'           => 'Disponible',
            'a_la_une'         => 1,
            'nouveau'          => 1
        ],
        [
            'reference'        => 'IML-003',
            'titre'            => 'Propriété de maître avec piscine et grand parc',
            'type'             => 'Villa',
            'transaction'      => 'Acheter',
            'prix'             => 42000000.00,
            'periode_prix'     => null,
            'wilaya'           => 'Alger',
            'commune'          => 'Alger',
            'quartier'         => 'Hydra',
            'surface'          => 310.00,
            'surface_terrain'  => 650.00,
            'chambres'         => 5,
            'salles_de_bain'   => 3,
            'etage'            => 0,
            'etages'           => 3,
            'annee'            => 2023,
            'garage'           => 1,
            'equipements'      => json_encode(['Piscine', 'Jardin', 'Terrasse', 'Garage', 'Gardiennage', 'Climatisation'], JSON_UNESCAPED_UNICODE),
            'images'           => json_encode([
                'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=80'
            ], JSON_UNESCAPED_SLASHES),
            'description'      => "Demeure d'exception dans le quartier diplomatique d'Hydra à Alger. Salon cathédrale, suite parentale d'exception, sécurité maximale, piscine chauffée et jardin paysager sans aucun vis-à-vis.",
            'statut'           => 'Disponible',
            'a_la_une'         => 1,
            'nouveau'          => 0
        ],
        [
            'reference'        => 'IML-004',
            'titre'            => 'Duplex haut standing avec terrasse privative',
            'type'             => 'Duplex',
            'transaction'      => 'Acheter',
            'prix'             => 19500000.00,
            'periode_prix'     => null,
            'wilaya'           => 'Sétif',
            'commune'          => 'Sétif',
            'quartier'         => 'Centre',
            'surface'          => 140.00,
            'surface_terrain'  => null,
            'chambres'         => 3,
            'salles_de_bain'   => 2,
            'etage'            => 5,
            'etages'           => 6,
            'annee'            => 2021,
            'garage'           => 1,
            'equipements'      => json_encode(['Terrasse', 'Ascenseur', 'Garage', 'Chauffage central', 'Double vitrage'], JSON_UNESCAPED_UNICODE),
            'images'           => json_encode([
                'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=1200&q=80'
            ], JSON_UNESCAPED_SLASHES),
            'description'      => "Magnifique duplex occupant les derniers niveaux d'une résidence sécurisée. Séjour traversant baigné de lumière, terrasse de 25 m², place de parking en sous-sol.",
            'statut'           => 'Disponible',
            'a_la_une'         => 0,
            'nouveau'          => 0
        ],
        [
            'reference'        => 'IML-005',
            'titre'            => 'Bureaux professionnels de standing',
            'type'             => 'Bureau',
            'transaction'      => 'Louer',
            'prix'             => 110000.00,
            'periode_prix'     => 'mois',
            'wilaya'           => 'Oran',
            'commune'          => 'Oran',
            'quartier'         => 'Millénium',
            'surface'          => 75.00,
            'surface_terrain'  => null,
            'chambres'         => 0,
            'salles_de_bain'   => 1,
            'etage'            => 2,
            'etages'           => 6,
            'annee'            => 2022,
            'garage'           => 0,
            'equipements'      => json_encode(['Fibre optique', 'Ascenseur', 'Climatisation', 'Interphone'], JSON_UNESCAPED_UNICODE),
            'images'           => json_encode([
                'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80'
            ], JSON_UNESCAPED_SLASHES),
            'description'      => "Espace de travail moderne et fonctionnel dans le quartier d'affaires du Millénium à Oran. Idéal pour profession libérale, cabinet médical ou siège d'entreprise.",
            'statut'           => 'Disponible',
            'a_la_une'         => 0,
            'nouveau'          => 0
        ]
    ];

    // Requête préparée MySQLi pour insérer les annonces
    $stmtProp = $conn->prepare("
        INSERT INTO `properties` (
            `reference`, `titre`, `type`, `transaction`, `prix`, `periode_prix`,
            `wilaya`, `commune`, `quartier`, `surface`, `surface_terrain`,
            `chambres`, `salles_de_bain`, `etage`, `etages`, `annee`, `garage`,
            `equipements`, `images`, `description`, `statut`, `a_la_une`, `nouveau`
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?
        )
    ");

    foreach ($annonces as $a) {
        $ref = $a['reference'];
        $titre = $a['titre'];
        $type = $a['type'];
        $trans = $a['transaction'];
        $prix = (float)$a['prix'];
        $periode = $a['periode_prix'];
        $wilaya = $a['wilaya'];
        $commune = $a['commune'];
        $quartier = $a['quartier'];
        $surf = (float)$a['surface'];
        $surfTer = $a['surface_terrain'] !== null ? (float)$a['surface_terrain'] : null;
        $chambres = $a['chambres'];
        $sdb = $a['salles_de_bain'];
        $etage = $a['etage'];
        $etages = $a['etages'];
        $annee = $a['annee'];
        $garage = (int)$a['garage'];
        $equip = $a['equipements'];
        $imgs = $a['images'];
        $desc = $a['description'];
        $statut = $a['statut'];
        $aLaUne = (int)$a['a_la_une'];
        $nouveau = (int)$a['nouveau'];

        $stmtProp->bind_param(
            'ssssdssssddiiiiiissssii',
            $ref, $titre, $type, $trans, $prix, $periode,
            $wilaya, $commune, $quartier, $surf, $surfTer,
            $chambres, $sdb, $etage, $etages, $annee, $garage,
            $equip, $imgs, $desc, $statut, $aLaUne, $nouveau
        );
        $stmtProp->execute();
    }
    $stmtProp->close();
    echo "OK\n";
}

// D. Demandes de démonstration
$checkDem = $conn->query("SELECT id FROM `demandes` LIMIT 1");
if ($checkDem->num_rows === 0) {
    echo "Insertion de demandes d'exemple... ";
    $stmtDem = $conn->prepare("
        INSERT INTO `demandes` (`type`, `nom`, `email`, `telephone`, `sujet`, `message`, `statut`)
        VALUES (?, ?, ?, ?, ?, ?, 'Nouveau')
    ");
    $typeD = 'Contact';
    $nomD = 'Nadir Mansouri';
    $emailD = 'nadir.mansouri@gmail.com';
    $telD = '0550 12 34 56';
    $sujetD = 'Demande de visite - Villa Tichy';
    $msgD = 'Bonjour, je souhaite visiter la villa contemporaine à Tichy ce samedi.';
    $stmtDem->bind_param('ssssss', $typeD, $nomD, $emailD, $telD, $sujetD, $msgD);
    $stmtDem->execute();
    $stmtDem->close();
    echo "OK\n";
}

echo "\nBase de données MySQL `immolode` configurée et prête à l'emploi !\n</pre>";
