<?php
require_once __DIR__ . '/../database/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Používateľ s týmto e-mailom neexistuje (skúste jan.novak@stuba.sk).';
    }
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prihlásenie · dochádzka</title>
    <link rel="stylesheet" href="vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
<main class="login">
    <form class="panel" method="POST" action="prihlasenie.php" novalidate>
        <div>
            <div class="brand">dochádzka</div>
            <p>Evidencia dochádzky – prístup iba pre učiteľov.</p>
        </div>

        <?php if ($error): ?>
            <div class="notice danger"><div><?= h($error) ?></div></div>
        <?php endif; ?>

        <div>
            <label class="form-label" for="email">E-mail</label>
            <input class="form-control" type="email" id="email" name="email"
                value="jan.novak@stuba.sk" autocomplete="username" required autofocus>
        </div>

        <div>
            <label class="form-label" for="password">Heslo</label>
            <input class="form-control" type="password" id="password" name="password"
                value="heslo123" autocomplete="current-password" required>
        </div>

        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
            <label class="form-check-label" for="remember">Zapamätať prihlásenie</label>
        </div>

        <button class="btn btn-primary" type="submit">Prihlásiť sa</button>
    </form>
</main>
<script src="js/app.js"></script>
</body>
</html>
