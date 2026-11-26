<?php
/**
 * admin/login.php - Connexion à l'administration Imolode (HTML brut + PHP Session)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$b = BASE_URL;

// Déconnexion si demandée via ?logout=1
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_user']);
    session_destroy();
    header('Location: ' . $b . '/admin/login.php');
    exit;
}

// Déjà authentifié -> redirection vers tableau de bord
if (!empty($_SESSION['admin_user']['id'])) {
    $dest = !empty($_GET['redirect']) ? (string)$_GET['redirect'] : ($b . '/admin/index.php');
    header('Location: ' . $dest);
    exit;
}

$loginError = '';
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $emailVal = $email;

    if ($email === '' || $password === '') {
        $loginError = 'Veuillez renseigner tous les champs.';
    } else {
        // Raccourci local pratique admin / admin
        if ($email === 'admin' && $password === 'admin') {
            $email = 'admin@immolode.dz';
            $password = 'admin123';
        }

        $cleanEmail = strtolower($email);
        $stmt = $conn->prepare("SELECT * FROM `utilisateurs` WHERE LOWER(`email`) = ? AND `actif` = 1 LIMIT 1");
        $stmt->bind_param("s", $cleanEmail);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            $loginError = 'Identifiants incorrects ou compte inactif.';
        } else {
            $valid = password_verify($password, (string)$user['mot_de_passe']) || ($password === $user['mot_de_passe']);
            if (!$valid) {
                $loginError = 'Mot de passe incorrect.';
            } else {
                $_SESSION['admin_user'] = [
                    'id'        => (int)$user['id'],
                    'nom'       => $user['nom'],
                    'email'     => $user['email'],
                    'role'      => $user['role'],
                    'poste'     => $user['poste'] ?? 'Gestionnaire',
                    'telephone' => $user['telephone'] ?? '',
                    'avatar'    => $user['avatar'] ?? null
                ];
                $redirectPost = trim((string)($_POST['redirect'] ?? $_GET['redirect'] ?? ''));
                $dest = ($redirectPost !== '' && str_starts_with($redirectPost, '/')) ? $redirectPost : ($b . '/admin/index.php');
                header('Location: ' . $dest);
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion — Administration Imolode</title>
  <link rel="icon" type="image/svg+xml" href="<?= $b ?>/assets/images/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
  <link rel="stylesheet" href="<?= $b ?>/admin/assets/css/admin.css">
</head>
<body class="login-page">
  <div class="login-card">
    <div class="login-brand">
      <img src="<?= $b ?>/assets/images/logo.svg" alt="Imolode Logo">
      <h1>Imolode</h1>
      <p>Portail d'administration</p>
    </div>

    <?php if ($loginError !== ''): ?>
      <div class="login-alert"><?= e($loginError) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= $b ?>/admin/login.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode((string)$_GET['redirect']) : '' ?>">
      <input type="hidden" name="redirect" value="<?= e($_GET['redirect'] ?? '') ?>">

      <div class="form-group" style="margin-bottom: 16px;">
        <label class="form-label" for="email">Adresse e-mail</label>
        <input type="text" id="email" name="email" class="form-input" placeholder="admin@immolode.dz" required autofocus autocomplete="username" value="<?= e($emailVal) ?>">
      </div>

      <div class="form-group" style="margin-bottom: 24px;">
        <label class="form-label" for="password">Mot de passe</label>
        <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required autocomplete="current-password">
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%;">
        Se connecter
      </button>
    </form>

    <div class="login-hint-card">
      <p>Compte de démonstration (Gérant) :</p>
      <div>E-mail : <code>admin@immolode.dz</code> (ou <code>gahamhocine2@gmail.com</code>)</div>
      <div>Mot de passe : <code>admin123</code></div>
      <button type="button" class="login-hint-btn" id="btn-fill-demo">Remplir les identifiants</button>
    </div>

    <div style="text-align: center; margin-top: 24px;">
      <a href="<?= $b ?>/client/index.php" style="font-size: 0.82rem; color: var(--admin-text-muted); text-decoration: underline;">
        &larr; Retourner sur le site public
      </a>
    </div>
  </div>

  <script>
    document.getElementById('btn-fill-demo')?.addEventListener('click', function() {
      document.getElementById('email').value = 'gahamhocine2@gmail.com';
      document.getElementById('password').value = 'admin123';
      document.getElementById('email').focus();
    });
  </script>
</body>
</html>
