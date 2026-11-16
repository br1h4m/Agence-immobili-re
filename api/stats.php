<?php
// api/stats.php
declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Immolode\Helpers\Response;
use Immolode\Models\Stats;

Response::handlePreflight();

$stats = Stats::getDashboardStats();
Response::json($stats);
