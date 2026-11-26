<?php
/**
 * admin/logout.php - Déconnexion de l'administration
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

unset($_SESSION['admin_user']);
session_destroy();

header('Location: ' . BASE_URL . '/admin/login.php');
exit;
