<?php
// backend/models/Stats.php
declare(strict_types=1);

namespace Immolode\Models;

use PDO;
use Immolode\Config\Database;

class Stats {
    public static function getDashboardStats(): array {
        $pdo = Database::getConnection();

        // 1. Statistiques des propriétés
        $totalProps = (int) $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
        $forSale    = (int) $pdo->query("SELECT COUNT(*) FROM properties WHERE `transaction` = 'Acheter'")->fetchColumn();
        $forRent    = (int) $pdo->query("SELECT COUNT(*) FROM properties WHERE `transaction` = 'Louer'")->fetchColumn();
        $available  = (int) $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'Disponible'")->fetchColumn();

        // 2. Statistiques des demandes
        $totalDemandes   = (int) $pdo->query("SELECT COUNT(*) FROM demandes")->fetchColumn();
        $pendingDemandes = (int) $pdo->query("SELECT COUNT(*) FROM demandes WHERE status IN ('Nouveau', 'En cours')")->fetchColumn();

        // 3. Statistiques des fiches clients
        $totalFiches     = (int) $pdo->query("SELECT COUNT(*) FROM client_fiches")->fetchColumn();
        $pendingFiches   = (int) $pdo->query("SELECT COUNT(*) FROM client_fiches WHERE status IN ('Nouveau', 'En cours')")->fetchColumn();

        // 4. Biens récents
        $recentProperties = Property::getAll(['limit' => 5, 'sort' => 'recent']);

        // 5. Demandes récentes
        $recentDemandes = Demande::getAll(['limit' => 5]);

        // 6. Fiches clients récentes
        $recentFiches = ClientFiche::getAll(['limit' => 5]);

        return [
            'totalProperties'  => $totalProps,
            'forSale'          => $forSale,
            'forRent'          => $forRent,
            'available'        => $available,
            'totalDemandes'    => $totalDemandes,
            'pendingDemandes'  => $pendingDemandes,
            'totalFiches'      => $totalFiches,
            'pendingFiches'    => $pendingFiches,
            'recentProperties' => $recentProperties,
            'recentDemandes'   => $recentDemandes,
            'recentFiches'     => $recentFiches
        ];
    }
}
