<?php
require_once 'functions.php';
exiger_connexion();

$etudiants = lire_etudiants();

// ---- Recherche ----
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $etudiants = array_filter($etudiants, function ($e) use ($q) {
        return stripos($e['nom'], $q) !== false || stripos($e['prenom'], $q) !== false;
    });
}

// ---- Tri ----
$tri = $_GET['tri'] ?? 'ajout';
$tris_valides = ['ajout' => "Ordre d'ajout", 'nom' => 'Nom', 'filiere' => 'Filière', 'note' => 'Note décroissante'];
if (!array_key_exists($tri, $tris_valides)) {
    $tri = 'ajout';
}
switch ($tri) {
    case 'nom':
        usort($etudiants, fn($a, $b) => strcasecmp($a['nom'], $b['nom']));
        break;
    case 'filiere':
        usort($etudiants, fn($a, $b) => strcasecmp($a['filiere'], $b['filiere']));
        break;
    case 'note':
        usort($etudiants, fn($a, $b) => $b['note'] <=> $a['note']);
        break;
    default:
        $etudiants = array_values($etudiants); // ordre d'ajout (par id)
}

// ---- Statistiques ----
$nb = count($etudiants);
$notes = array_column($etudiants, 'note');
$moyenne = $nb > 0 ? array_sum($notes) / $nb : 0;
$meilleure = $nb > 0 ? max($notes) : 0;

// ---- Message flash ----
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$titre = 'Liste des étudiants';
include 'header.php';
?>
<h2>Liste des étudiants</h2>

<?php if ($flash): ?>
    <p class="message <?= h($flash['type']) ?>"><?= h($flash['msg']) ?></p>
<?php endif; ?>

<form method="get" action="liste.php" class="barre-recherche">
    <input type="search" id="recherche" name="q" placeholder="Rechercher un nom ou prénom…" value="<?= h($q) ?>">
    <select name="tri">
        <?php foreach ($tris_valides as $val => $lib): ?>
            <option value="<?= h($val) ?>" <?= $tri === $val ? 'selected' : '' ?>><?= h($lib) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Valider</button>
</form>

<p class="stats">
    <strong><?= $nb ?></strong> étudiant(s) affiché(s) &middot;
    Moyenne : <strong><?= number_format($moyenne, 2, ',', ' ') ?></strong> &middot;
    Meilleure note : <strong><?= number_format((float) $meilleure, 2, ',', ' ') ?></strong>
</p>

<table id="tableau">
    <thead>
        <tr>
            <th>Photo</th><th>Nom</th><th>Prénom</th><th>Filière</th><th>Note</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($nb === 0): ?>
        <tr><td colspan="6" class="vide">Aucun étudiant</td></tr>
    <?php else: ?>
        <?php foreach ($etudiants as $e): ?>
            <tr>
                <td>
                    <?php if ($e['photo'] !== '' && file_exists(DOSSIER_UPLOADS . $e['photo'])): ?>
                        <img class="photo" src="uploads/<?= h($e['photo']) ?>" alt="Photo">
                    <?php else: ?>
                        <span class="photo vide-photo">?</span>
                    <?php endif; ?>
                </td>
                <td><?= h(strtoupper($e['nom'])) ?></td>
                <td><?= h(ucfirst($e['prenom'])) ?></td>
                <td><?= h($e['filiere']) ?></td>
                <td class="<?= $e['note'] >= 10 ? 'note-ok' : 'note-ko' ?>"><?= h((string) $e['note']) ?></td>
                <td>
                    <a href="ajouter.php?id=<?= (int) $e['id'] ?>">Modifier</a> |
                    <a class="lien-suppr" href="supprimer.php?id=<?= (int) $e['id'] ?>">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
<?php include 'footer.php'; ?>
