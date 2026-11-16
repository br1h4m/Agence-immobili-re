<?php
// backend/helpers/Auth.php
declare(strict_types=1);

namespace Immolode\Helpers;

use Immolode\Config\Config;

class Auth {
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.gc_maxlifetime', (string) Config::SESSION_LIFETIME);
            session_name(Config::SESSION_NAME);
            session_start();
        }
    }

    public static function login(array $user): void {
        self::startSession();
        // Ne jamais stocker le hash du mot de passe en session
        unset($user['password_hash']);
        session_regenerate_id(true);
        $_SESSION['admin_user'] = $user;
        $_SESSION['logged_at'] = time();
    }

    public static function logout(): void {
        self::startSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function check(): bool {
        self::startSession();
        return !empty($_SESSION['admin_user']) && is_array($_SESSION['admin_user']);
    }

    public static function user(): ?array {
        self::startSession();
        return $_SESSION['admin_user'] ?? null;
    }

    public static function requireAuth(bool $isApi = true): ?array {
        if (!self::check()) {
            if ($isApi) {
                Response::unauthorized('Session expirée ou non authentifié. Veuillez vous connecter.');
            } else {
                $currentUri = $_SERVER['REQUEST_URI'] ?? '/admin/';
                $redirect = urlencode($currentUri);
                header("Location: /admin/login.php?redirect={$redirect}");
                exit;
            }
        }
        return self::user();
    }

    public static function isAdmin(): bool {
        $user = self::user();
        return $user && ($user['role'] ?? '') === 'Administrateur';
    }
}
