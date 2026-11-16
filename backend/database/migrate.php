<?php
// backend/database/migrate.php
declare(strict_types=1);

require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../config/Database.php';

use Immolode\Config\Database;

try {
    echo "=== Initialisation de la base de données Immolode (PHP) ===\n";
    $pdo = Database::getConnection();
    $driver = Database::getDriver();
    echo "Connexion réussie sur le moteur : {$driver}\n";
    echo "Schéma et tables vérifiés/initialisés avec succès !\n";

    // Vérification des comptes
    $stmt = $pdo->query("SELECT id, nom, email, role FROM users");
    $users = $stmt->fetchAll();
    echo "\nUtilisateurs enregistrés (" . count($users) . ") :\n";
    foreach ($users as $u) {
        echo " - [{$u['id']}] {$u['nom']} ({$u['email']}) - {$u['role']}\n";
    }

    // Vérification des propriétés
    $countProps = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
    echo "\nNombre de biens en portefeuille : {$countProps}\n";

    // Vérification des demandes
    $countDem = $pdo->query("SELECT COUNT(*) FROM demandes")->fetchColumn();
    echo "Nombre de demandes : {$countDem}\n";

    // Vérification des fiches clients
    $countFiches = $pdo->query("SELECT COUNT(*) FROM client_fiches")->fetchColumn();
    echo "Nombre de fiches client : {$countFiches}\n";

    echo "\n=== Migration terminée avec succès ! ===\n";
} catch (\Throwable $t) {
    echo "\nErreur lors de la migration : " . $t->getMessage() . "\n";
    exit(1);
}
