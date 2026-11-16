<?php
// backend/database/Schema.php
declare(strict_types=1);

namespace Immolode\Database;

use PDO;
use Immolode\Config\Config;

class Schema {
    public static function createTables(PDO $pdo, string $driver = 'mysql'): void {
        $isSqlite = ($driver === 'sqlite');

        $autoInc = $isSqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
        $currTime = 'CURRENT_TIMESTAMP';

        // 1. Table properties
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS properties (
                id {$autoInc},
                reference VARCHAR(50) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                `transaction` VARCHAR(50) NOT NULL,
                price DECIMAL(15, 2) NOT NULL,
                price_period VARCHAR(20) DEFAULT NULL,
                wilaya VARCHAR(100) NOT NULL,
                city VARCHAR(100) NOT NULL,
                district VARCHAR(150) DEFAULT NULL,
                surface DECIMAL(10, 2) NOT NULL,
                land_surface DECIMAL(10, 2) DEFAULT NULL,
                bedrooms INT DEFAULT NULL,
                bathrooms INT DEFAULT NULL,
                floor INT DEFAULT NULL,
                floors INT DEFAULT NULL,
                year INT DEFAULT NULL,
                garage INT DEFAULT 0,
                features TEXT DEFAULT NULL,
                images TEXT DEFAULT NULL,
                description TEXT DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'Disponible',
                is_featured INT DEFAULT 0,
                is_new INT DEFAULT 0,
                created_at {$currTime},
                updated_at {$currTime}
            )
        ");

        // 2. Table demandes
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS demandes (
                id {$autoInc},
                type VARCHAR(50) NOT NULL,
                nom VARCHAR(150) NOT NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                subject VARCHAR(255) DEFAULT NULL,
                message TEXT DEFAULT NULL,
                details TEXT DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'Nouveau',
                created_at {$currTime}
            )
        ");

        // 3. Table client_fiches
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS client_fiches (
                id {$autoInc},
                reference VARCHAR(50) NOT NULL UNIQUE,
                nom VARCHAR(100) NOT NULL,
                prenom VARCHAR(100) NOT NULL,
                telephone VARCHAR(50) NOT NULL,
                email VARCHAR(150) NOT NULL,
                `transaction` VARCHAR(50) NOT NULL,
                type_bien VARCHAR(50) NOT NULL,
                property_id INT DEFAULT NULL,
                piece_identite_name VARCHAR(255) DEFAULT NULL,
                piece_identite_size INT DEFAULT NULL,
                piece_identite_type VARCHAR(100) DEFAULT NULL,
                piece_identite_path TEXT DEFAULT NULL,
                signature_data TEXT DEFAULT NULL,
                engagement_accepte INT DEFAULT 1,
                status VARCHAR(50) DEFAULT 'Nouveau',
                notes TEXT DEFAULT NULL,
                created_at {$currTime}
            )
        ");

        // 4. Table users
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id {$autoInc},
                nom VARCHAR(150) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'Agent commercial',
                poste VARCHAR(100) DEFAULT NULL,
                phone VARCHAR(50) DEFAULT NULL,
                actif INT DEFAULT 1,
                created_at {$currTime}
            )
        ");

        // 5. Table settings
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT DEFAULT NULL,
                updated_at {$currTime}
            )
        ");
    }

    public static function seedInitialData(PDO $pdo, string $driver = 'mysql'): void {
        // 1. Initialiser l'administrateur
        $stmtUser = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmtUser->execute([Config::DEFAULT_ADMIN_EMAIL]);
        if ((int) $stmtUser->fetchColumn() === 0) {
            $insertUser = $pdo->prepare("
                INSERT INTO users (nom, email, password_hash, role, poste, phone, actif)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $passHash = password_hash(Config::DEFAULT_ADMIN_PASS, PASSWORD_BCRYPT);
            $insertUser->execute([
                Config::DEFAULT_ADMIN_NAME,
                Config::DEFAULT_ADMIN_EMAIL,
                $passHash,
                Config::DEFAULT_ADMIN_ROLE,
                'Directeur général',
                '+213 550 12 34 56'
            ]);

            // Agent supplémentaire pour l'équipe
            $insertUser->execute([
                'Sarah Hadj',
                'sarah.hadj@immolode.dz',
                password_hash('agent123', PASSWORD_BCRYPT),
                'Agent commercial',
                'Conseillère senior',
                '+213 661 22 33 44'
            ]);
        }

        // 2. Initialiser les paramètres du site
        $defaultSettings = [
            'agency_name'        => 'Immolode',
            'tagline'            => 'Agence immobilière d\'excellence',
            'description'        => 'Immolode, agence immobilière : découvrez des propriétés sélectionnées avec soin pour vos projets d\'achat et de location en Algérie.',
            'phone'              => '+213 5XX XX XX XX',
            'phone_raw'          => '+2135XXXXXXXX',
            'email'              => 'contact@immolode.dz',
            'address'            => '12 rue Didouche Mourad, Béjaïa 06000, Algérie',
            'whatsapp'           => '2135XXXXXXXX',
            'hours'              => 'Dim – Jeu : 9h00 – 17h30 · Sam : 9h00 – 13h00',
            'currency'           => 'DA',
            'properties_per_page'=> '9',
            'facebook'           => 'https://facebook.com',
            'instagram'          => 'https://instagram.com'
        ];

        $stmtSet = $pdo->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        if ($driver === 'mysql') {
            $stmtSet = $pdo->prepare("
                INSERT INTO settings (setting_key, setting_value)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
        }

        foreach ($defaultSettings as $k => $v) {
            $stmtSet->execute([$k, $v]);
        }

        // 3. Initialiser les propriétés (Biens)
        $stmtPropCount = $pdo->query("SELECT COUNT(*) FROM properties");
        if ((int) $stmtPropCount->fetchColumn() === 0) {
            self::seedProperties($pdo);
        }

        // 4. Initialiser les demandes
        $stmtDemCount = $pdo->query("SELECT COUNT(*) FROM demandes");
        if ((int) $stmtDemCount->fetchColumn() === 0) {
            self::seedDemandes($pdo);
        }

        // 5. Initialiser les fiches client
        $stmtFicheCount = $pdo->query("SELECT COUNT(*) FROM client_fiches");
        if ((int) $stmtFicheCount->fetchColumn() === 0) {
            self::seedFichesClient($pdo);
        }
    }

    private static function seedProperties(PDO $pdo): void {
        $img = fn($id, $w = 1200) => "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w={$w}&q=80";

        $properties = [
            [
                'reference' => 'IML-001',
                'title' => 'Villa contemporaine',
                'type' => 'Villa',
                'transaction' => 'Acheter',
                'price' => 25000000,
                'price_period' => null,
                'wilaya' => 'Béjaïa',
                'city' => 'Béjaïa',
                'district' => 'Tichy',
                'surface' => 180,
                'land_surface' => 400,
                'bedrooms' => 4,
                'bathrooms' => 2,
                'floor' => 0,
                'floors' => 2,
                'year' => 2021,
                'garage' => 1,
                'features' => json_encode(['Jardin', 'Piscine', 'Terrasse', 'Garage', 'Climatisation'], JSON_UNESCAPED_UNICODE),
                'images' => json_encode([
                    $img('1613490493576-7fde63acd811'),
                    $img('1600585154340-be6161a56a0c'),
                    $img('1600607687939-ce8a6c25118c'),
                    $img('1600566753190-17f0baa2a6c3')
                ], JSON_UNESCAPED_SLASHES),
                'description' => "Située dans le quartier résidentiel de Tichy, à quelques minutes de la plage, cette villa contemporaine de 180 m² allie lignes épurées et grands volumes lumineux. Le rez-de-chaussée s'ouvre sur un vaste séjour, une cuisine équipée et une terrasse qui donne sur le jardin et la piscine. À l'étage, quatre chambres dont une suite parentale avec dressing.",
                'status' => 'Disponible',
                'is_featured' => 1,
                'is_new' => 0,
                'created_at' => '2026-09-15 10:00:00'
            ],
            [
                'reference' => 'IML-002',
                'title' => 'Appartement vue sur mer',
                'type' => 'Appartement',
                'transaction' => 'Louer',
                'price' => 85000,
                'price_period' => 'mois',
                'wilaya' => 'Béjaïa',
                'city' => 'Béjaïa',
                'district' => 'Boulevard Amirouche',
                'surface' => 95,
                'land_surface' => null,
                'bedrooms' => 2,
                'bathrooms' => 1,
                'floor' => 4,
                'floors' => 6,
                'year' => 2019,
                'garage' => 0,
                'features' => json_encode(['Balcon', 'Ascenseur', 'Climatisation', 'Cuisine équipée'], JSON_UNESCAPED_UNICODE),
                'images' => json_encode([
                    $img('1545324418-cc1a3fa10c00'),
                    $img('1512917774080-9991f1c4c750'),
                    $img('1502672260266-1c1ef2d93688')
                ], JSON_UNESCAPED_SLASHES),
                'description' => "Bel appartement de 95 m² bénéficiant d'une vue panoramique sur le golfe de Béjaïa. Composé d'un séjour lumineux avec balcon, d'une cuisine séparée entièrement aménagée et de deux chambres avec placards intégrés. Proximité immédiate des commerces et transports.",
                'status' => 'Disponible',
                'is_featured' => 1,
                'is_new' => 1,
                'created_at' => '2026-09-20 11:30:00'
            ],
            [
                'reference' => 'IML-003',
                'title' => 'Maison d\'architecte avec piscine',
                'type' => 'Villa',
                'transaction' => 'Acheter',
                'price' => 42000000,
                'price_period' => null,
                'wilaya' => 'Alger',
                'city' => 'Alger',
                'district' => 'Hydra',
                'surface' => 310,
                'land_surface' => 650,
                'bedrooms' => 5,
                'bathrooms' => 3,
                'floor' => 0,
                'floors' => 3,
                'year' => 2023,
                'garage' => 1,
                'features' => json_encode(['Piscine', 'Jardin', 'Terrasse', 'Garage', 'Climatisation', 'Interphone', 'Gardiennage'], JSON_UNESCAPED_UNICODE),
                'images' => json_encode([
                    $img('1600596542815-ffad4c1539a9'),
                    $img('1600585154526-990dced4db0d'),
                    $img('1600573472591-ee6b68d14c68')
                ], JSON_UNESCAPED_SLASHES),
                'description' => "Propriété d'exception au cœur d'Hydra. Prestations très haut de gamme, salon cathédrale de 80 m², cuisine ouverte sur terrasse ombragée, suite de maître avec terrasse privative et piscine chauffée. Sécurité optimale dans un secteur calme et recherché.",
                'status' => 'Disponible',
                'is_featured' => 1,
                'is_new' => 1,
                'created_at' => '2026-09-25 14:00:00'
            ],
            [
                'reference' => 'IML-004',
                'title' => 'Duplex moderne centre-ville',
                'type' => 'Duplex',
                'transaction' => 'Acheter',
                'price' => 19500000,
                'price_period' => null,
                'wilaya' => 'Sétif',
                'city' => 'Sétif',
                'district' => 'Centre',
                'surface' => 140,
                'land_surface' => null,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'floor' => 5,
                'floors' => 6,
                'year' => 2020,
                'garage' => 1,
                'features' => json_encode(['Terrasse', 'Ascenseur', 'Garage', 'Climatisation', 'Double vitrage'], JSON_UNESCAPED_UNICODE),
                'images' => json_encode([
                    $img('1600607687920-4e2a09cf159d'),
                    $img('1600566753086-00f18fb6b3ea')
                ], JSON_UNESCAPED_SLASHES),
                'description' => "Duplex contemporain aux derniers étages d'un immeuble moderne. Grande pièce de vie traversante avec accès direct à une terrasse de 25 m². Matériaux nobles, finitions irréprochables et place de parking en sous-sol.",
                'status' => 'Disponible',
                'is_featured' => 0,
                'is_new' => 0,
                'created_at' => '2026-09-10 09:00:00'
            ],
            [
                'reference' => 'IML-005',
                'title' => 'Bureau professionnel standing',
                'type' => 'Bureau',
                'transaction' => 'Louer',
                'price' => 110000,
                'price_period' => 'mois',
                'wilaya' => 'Oran',
                'city' => 'Oran',
                'district' => 'Millénium',
                'surface' => 75,
                'land_surface' => null,
                'bedrooms' => 0,
                'bathrooms' => 1,
                'floor' => 2,
                'floors' => 6,
                'year' => 2021,
                'garage' => 0,
                'features' => json_encode(['Ascenseur', 'Climatisation', 'Fibre optique', 'Interphone'], JSON_UNESCAPED_UNICODE),
                'images' => json_encode([
                    $img('1486406146926-c627a92ad1ab'),
                    $img('1497215728101-856f4ea42174')
                ], JSON_UNESCAPED_SLASHES),
                'description' => "Bureau de 75 m² dans un immeuble récent du quartier d'affaires du Millénium. Belles prestations, climatisation, fibre optique et ascenseur. Un investissement sûr dans un secteur en pleine croissance.",
                'status' => 'Disponible',
                'is_featured' => 0,
                'is_new' => 0,
                'created_at' => '2026-08-12 16:00:00'
            ]
        ];

        $stmt = $pdo->prepare("
            INSERT INTO properties (
                reference, title, type, `transaction`, price, price_period,
                wilaya, city, district, surface, land_surface,
                bedrooms, bathrooms, floor, floors, year, garage,
                features, images, description, status, is_featured, is_new, created_at
            ) VALUES (
                :reference, :title, :type, :transaction, :price, :price_period,
                :wilaya, :city, :district, :surface, :land_surface,
                :bedrooms, :bathrooms, :floor, :floors, :year, :garage,
                :features, :images, :description, :status, :is_featured, :is_new, :created_at
            )
        ");

        foreach ($properties as $p) {
            $stmt->execute($p);
        }
    }

    private static function seedDemandes(PDO $pdo): void {
        $demandes = [
            [
                'type' => 'Contact',
                'nom' => 'Nadir Mansouri',
                'email' => 'nadir.mansouri@gmail.com',
                'phone' => '+213 550 12 34 56',
                'subject' => "Demande d'information — Villa contemporaine",
                'message' => "Bonjour, je souhaiterais visiter la villa contemporaine à Tichy ce week-end si possible. Merci de me recontacter.",
                'details' => null,
                'status' => 'Nouveau',
                'created_at' => '2026-09-30 10:15:00'
            ],
            [
                'type' => 'Estimation',
                'nom' => 'Farida Ould Ali',
                'email' => 'farida.ouldali@yahoo.fr',
                'phone' => '+213 661 78 90 12',
                'subject' => 'Estimation : Appartement à Alger',
                'message' => 'Appartement F4 situé dans une résidence fermée à Chéraga. Bon état général.',
                'details' => json_encode([
                    'type' => 'Appartement',
                    'wilaya' => 'Alger',
                    'commune' => 'Chéraga',
                    'quartier' => 'Bouchaoui',
                    'surface' => '120',
                    'land' => '',
                    'bedrooms' => '3',
                    'bathrooms' => '2',
                    'garage' => 'Oui'
                ], JSON_UNESCAPED_UNICODE),
                'status' => 'En cours',
                'created_at' => '2026-09-29 14:30:00'
            ],
            [
                'type' => 'Contact',
                'nom' => 'Rachid Belkacem',
                'email' => 'rachid.belkacem@pro-dz.com',
                'phone' => '+213 770 45 67 89',
                'subject' => 'Recherche bureau professionnel',
                'message' => "Nous sommes à la recherche d'une surface de bureau d'au moins 100 m² à Béjaïa centre pour installer notre société.",
                'details' => null,
                'status' => 'Traité',
                'created_at' => '2026-09-28 09:00:00'
            ]
        ];

        $stmt = $pdo->prepare("
            INSERT INTO demandes (type, nom, email, phone, subject, message, details, status, created_at)
            VALUES (:type, :nom, :email, :phone, :subject, :message, :details, :status, :created_at)
        ");

        foreach ($demandes as $d) {
            $stmt->execute($d);
        }
    }

    private static function seedFichesClient(PDO $pdo): void {
        $fiches = [
            [
                'reference' => 'IML-DOS-2026-1042',
                'nom' => 'Benali',
                'prenom' => 'Karim',
                'telephone' => '0550 12 34 56',
                'email' => 'karim.benali@gmail.com',
                'transaction' => 'Demande',
                'type_bien' => 'Appartement',
                'property_id' => 1,
                'piece_identite_name' => 'cni_karim_benali.pdf',
                'piece_identite_size' => 450000,
                'piece_identite_type' => 'application/pdf',
                'piece_identite_path' => null,
                'signature_data' => 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="80"><path d="M 20 50 Q 60 10 90 40 T 170 30" fill="none" stroke="%231C1C1C" stroke-width="2.5"/></svg>',
                'engagement_accepte' => 1,
                'status' => 'Nouveau',
                'notes' => 'Client intéressé par une visite le week-end.',
                'created_at' => '2026-10-14 09:30:00'
            ],
            [
                'reference' => 'IML-DOS-2026-1043',
                'nom' => 'Amrani',
                'prenom' => 'Samira',
                'telephone' => '0661 88 99 22',
                'email' => 'samira.amrani@outlook.com',
                'transaction' => 'Offre',
                'type_bien' => 'Villa',
                'property_id' => null,
                'piece_identite_name' => 'passeport_amrani.jpg',
                'piece_identite_size' => 1250000,
                'piece_identite_type' => 'image/jpeg',
                'piece_identite_path' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=400&q=80',
                'signature_data' => 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="80"><path d="M 30 45 Q 70 20 110 50 T 165 40" fill="none" stroke="%231C1C1C" stroke-width="2.5"/></svg>',
                'engagement_accepte' => 1,
                'status' => 'En cours',
                'notes' => 'Pièce d\'identité vérifiée.',
                'created_at' => '2026-10-13 15:20:00'
            ]
        ];

        $stmt = $pdo->prepare("
            INSERT INTO client_fiches (
                reference, nom, prenom, telephone, email, `transaction`, type_bien, property_id,
                piece_identite_name, piece_identite_size, piece_identite_type, piece_identite_path,
                signature_data, engagement_accepte, status, notes, created_at
            ) VALUES (
                :reference, :nom, :prenom, :telephone, :email, :transaction, :type_bien, :property_id,
                :piece_identite_name, :piece_identite_size, :piece_identite_type, :piece_identite_path,
                :signature_data, :engagement_accepte, :status, :notes, :created_at
            )
        ");

        foreach ($fiches as $f) {
            $stmt->execute($f);
        }
    }
}
