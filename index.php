<?php
// index.php
declare(strict_types=1);

require_once __DIR__ . '/backend/bootstrap.php';

header('Location: /client/', true, 302);
exit;
