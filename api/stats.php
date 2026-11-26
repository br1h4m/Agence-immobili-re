<?php
/**
 * api/stats.php - Statistiques et synchronisation temps réel des notifications admin
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/admin_helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

try {
    $counts = get_unread_counts($conn);
    $dUnread = $counts['demandes'] ?? 0;
    $fUnread = $counts['fiches'] ?? 0;

    // Dernières ID pour détection de nouveaux éléments en direct
    $lastDemId = 0;
    $r1 = $conn->query("SELECT MAX(`id`) AS m FROM `demandes`");
    if ($r1 && ($row = $r1->fetch_assoc())) {
        $lastDemId = (int)($row['m'] ?? 0);
    }

    $lastFicId = 0;
    $r2 = $conn->query("SELECT MAX(`id`) AS m FROM `fiches_client`");
    if ($r2 && ($row = $r2->fetch_assoc())) {
        $lastFicId = (int)($row['m'] ?? 0);
    }

    echo json_encode([
        'unreadDemandes'     => $dUnread,
        'demandes_non_lues'  => $dUnread,
        'unreadFichesClient' => $fUnread,
        'fiches_non_lues'    => $fUnread,
        'last_demande_id'    => $lastDemId,
        'last_fiche_id'      => $lastFicId,
        'timestamp'          => time()
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
