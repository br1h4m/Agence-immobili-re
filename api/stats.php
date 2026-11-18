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

    $rForSale = $conn->query("SELECT COUNT(*) AS total FROM `properties` WHERE `transaction` = 'Acheter'");
    $forSale = (int)($rForSale->fetch_assoc()['total'] ?? 0);

    $rForRent = $conn->query("SELECT COUNT(*) AS total FROM `properties` WHERE `transaction` = 'Louer'");
    $forRent = (int)($rForRent->fetch_assoc()['total'] ?? 0);

    // 2. Demandes et contacts
    $rDemandesTotal = $conn->query("SELECT COUNT(*) AS total FROM `demandes`");
    $totalDemandes = (int)($rDemandesTotal->fetch_assoc()['total'] ?? 0);

    // Demandes non lues (ou nouveau)
    $rDemandesUnread = $conn->query("SELECT COUNT(*) AS total FROM `demandes` WHERE `is_read` = 0");
    $demandesUnread = (int)($rDemandesUnread->fetch_assoc()['total'] ?? 0);

    // 3. Fiches clients
    $rFichesTotal = $conn->query("SELECT COUNT(*) AS total FROM `fiches_client`");
    $totalFiches = (int)($rFichesTotal->fetch_assoc()['total'] ?? 0);

    // Fiches non lues
    $rFichesUnread = $conn->query("SELECT COUNT(*) AS total FROM `fiches_client` WHERE `is_read` = 0");
    $fichesUnread = (int)($rFichesUnread->fetch_assoc()['total'] ?? 0);

    echo json_encode([
        'success'             => true,
        'biens_total'         => $totalBiens,
        'totalProperties'     => $totalBiens,
        'biens_disponibles'   => $biensDispo,
        'available'           => $biensDispo,
        'biens_finalises'     => $biensVendus,
        'soldOrRented'        => $biensVendus,
        'forSale'             => $forSale,
        'forRent'             => $forRent,
        'demandes_total'      => $totalDemandes,
        'totalDemandes'       => $totalDemandes,
        'demandes_nouvelles'  => $demandesUnread,
        'pendingDemandes'     => $demandesUnread,
        'unreadDemandes'      => $demandesUnread,
        'fiches_total'        => $totalFiches,
        'totalFichesClient'   => $totalFiches,
        'fiches_nouvelles'    => $fichesUnread,
        'pendingFichesClient' => $fichesUnread,
        'unreadFichesClient'  => $fichesUnread
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
