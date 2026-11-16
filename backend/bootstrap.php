<?php
// backend/bootstrap.php
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'Immolode\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $parts = explode('\\', $relative);

    // Mappage des dossiers
    $folder = strtolower($parts[0]);
    $filename = $parts[1] ?? '';

    $path = __DIR__ . '/' . $folder . '/' . $filename . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

// Initialisation de la session
require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/helpers/Auth.php';
\Immolode\Helpers\Auth::startSession();
