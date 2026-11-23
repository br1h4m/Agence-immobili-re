<?php
/**
 * ============================================================================
 * api/stats.php - Statistiques du tableau de bord d'administration
 * ============================================================================
 * Calcul direct et simple depuis la base de données MySQL via MySQLi (sans PDO).
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    // 1. Biens immobiliers
    $rBiensTotal = $conn->query("SELECT COUNT(*) AS total FROM `properties`");
    $totalBiens = (int)($rBiensTotal->fetch_assoc()['total'] ?? 0);

    $rBiensDispo = $conn->query("SELECT COUNT(*) AS total FROM `properties` WHERE `statut` = 'Disponible'");
    $biensDispo = (int)($rBiensDispo->fetch_assoc()['total'] ?? 0);

    $rBiensVendus = $conn->query("SELECT COUNT(*) AS total FROM `properties` WHERE `statut` IN ('Vendu', 'Loué')");
    $biensVendus = (int)($rBiensVendus->fetch_assoc()['total'] ?? 0);

    // 2. Demandes et contacts
    $rDemandesTotal = $conn->query("SELECT COUNT(*) AS total FROM `demandes`");
    $totalDemandes = (int)($rDemandesTotal->fetch_assoc()['total'] ?? 0);

    $rDemandesNouvelles = $conn->query("SELECT COUNT(*) AS total FROM `demandes` WHERE `statut` = 'Nouveau'");
    $demandesNouvelles = (int)($rDemandesNouvelles->fetch_assoc()['total'] ?? 0);

    // 3. Fiches clients
    $rFichesTotal = $conn->query("SELECT COUNT(*) AS total FROM `fiches_client`");
    $totalFiches = (int)($rFichesTotal->fetch_assoc()['total'] ?? 0);

    $rFichesNouvelles = $conn->query("SELECT COUNT(*) AS total FROM `fiches_client` WHERE `statut` = 'Nouveau'");
    $fichesNouvelles = (int)($rFichesNouvelles->fetch_assoc()['total'] ?? 0);

    echo json_encode([
        'biens_total' => $totalBiens,
        'biens_disponibles' => $biensDispo,
        'biens_finalises' => $biensVendus,
        'demandes_total' => $totalDemandes,
        'demandes_nouvelles' => $demandesNouvelles,
        'fiches_total' => $totalFiches,
        'fiches_nouvelles' => $fichesNouvelles
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
