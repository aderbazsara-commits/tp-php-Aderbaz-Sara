<?php
require_once 'functions.php';

if (connecte()) {
    header('Location: liste.php');
    exit;
}

$erreur = '';
$login = $_COOKIE['dernier_user'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $mdp   = $_POST['mdp'] ?? '';

    if (isset(UTILISATEURS[$login]) && UTILISATEURS[$login] === $mdp) {
        $_SESSION['login'] = $login;
        setcookie('dernier_user', $login, time() + 7 * 24 * 3600, '/');
        header('Location: liste.php');
        exit;
    }
    $erreur = 'Login ou mot de passe incorrect.';
}

$titre = 'Connexion';
include 'header.php';
?>
<div class="carte">
    <h2>Connexion</h2>
    <?php if ($erreur): ?>
        <p class="message erreur"><?= h($erreur) ?></p>
    <?php endif; ?>
    <form method="post" action="index.php">
        <label for="login">Login</label>
        <input type="text" id="login" name="login" value="<?= h($login) ?>" required>
        <label for="mdp">Mot de passe</label>
        <input type="password" id="mdp" name="mdp" required>
        <button type="submit">Se connecter</button>
    </form>
</div>
<?php include 'footer.php'; ?>
