<?php
require_once 'functions.php';
exiger_connexion();

$etudiants = lire_etudiants();
$id = (int) ($_GET['id'] ?? 0);
$modif = $id > 0 && isset($etudiants[$id]);

if ($id > 0 && !$modif) {
    flash('Étudiant introuvable.', 'erreur');
    header('Location: liste.php');
    exit;
}

// Valeurs par défaut (formulaire vide ou pré-rempli)
$val = $modif
    ? $etudiants[$id]
    : ['nom' => '', 'prenom' => '', 'filiere' => '', 'note' => '', 'photo' => ''];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $val['nom']     = nettoyer($_POST['nom'] ?? '');
    $val['prenom']  = nettoyer($_POST['prenom'] ?? '');
    $val['filiere'] = $_POST['filiere'] ?? '';
    $val['note']    = trim($_POST['note'] ?? '');

    if ($val['nom'] === '')    $erreurs[] = 'Le nom est obligatoire.';
    if ($val['prenom'] === '') $erreurs[] = 'Le prénom est obligatoire.';
    if (!in_array($val['filiere'], FILIERES, true)) $erreurs[] = 'Filière invalide.';

    $note_txt = str_replace(',', '.', $val['note']);
    if (!is_numeric($note_txt) || $note_txt < 0 || $note_txt > 20) {
        $erreurs[] = 'La note doit être un nombre entre 0 et 20.';
    }

    // Bonus : refuser un doublon nom + prénom
    foreach ($etudiants as $e) {
        if ($e['id'] !== $id
            && strcasecmp($e['nom'], $val['nom']) === 0
            && strcasecmp($e['prenom'], $val['prenom']) === 0) {
            $erreurs[] = 'Un étudiant avec ce nom et ce prénom existe déjà.';
            break;
        }
    }

    // Photo (facultative)
    $ancienne = $modif ? $etudiants[$id]['photo'] : '';
    $nouvelle = '';
    if (!$erreurs) {
        [$nouvelle, $err_photo] = traiter_photo($_FILES['photo'] ?? []);
        if ($err_photo) {
            $erreurs[] = $err_photo;
        }
    } elseif (($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $erreurs[] = 'Veuillez sélectionner à nouveau la photo.';
    }

    if (!$erreurs) {
        if (!$modif) {
            $id = $etudiants ? max(array_keys($etudiants)) + 1 : 1;
        }
        if ($nouvelle !== '' && $ancienne !== '') {
            supprimer_photo($ancienne);
        }
        $etudiants[$id] = [
            'id'      => $id,
            'nom'     => $val['nom'],
            'prenom'  => $val['prenom'],
            'filiere' => $val['filiere'],
            'note'    => (float) $note_txt,
            'photo'   => $nouvelle !== '' ? $nouvelle : $ancienne,
        ];
        sauver_etudiants($etudiants);
        flash($modif ? 'Étudiant modifié avec succès.' : 'Étudiant ajouté avec succès.');
        header('Location: liste.php');
        exit;
    }
    $val['photo'] = $modif ? $etudiants[$id]['photo'] : '';
}

$titre = $modif ? 'Modifier un étudiant' : 'Ajouter un étudiant';
include 'header.php';
?>
<div class="carte">
    <h2><?= h($titre) ?></h2>

    <?php if ($erreurs): ?>
        <div class="message erreur">
            <ul>
                <?php foreach ($erreurs as $err): ?><li><?= h($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data"
          action="ajouter.php<?= $modif ? '?id=' . (int) $id : '' ?>">
        <label for="nom">Nom</label>
        <input type="text" id="nom" name="nom" value="<?= h($val['nom']) ?>">

        <label for="prenom">Prénom</label>
        <input type="text" id="prenom" name="prenom" value="<?= h($val['prenom']) ?>">

        <label for="filiere">Filière</label>
        <select id="filiere" name="filiere">
            <option value="">-- Choisir --</option>
            <?php foreach (FILIERES as $f): ?>
                <option value="<?= h($f) ?>" <?= $val['filiere'] === $f ? 'selected' : '' ?>><?= h($f) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="note">Note (0 à 20)</label>
        <input type="text" id="note" name="note" value="<?= h((string) $val['note']) ?>">

        <label for="photo">Photo (jpg, png – 1 Mo max, facultatif)</label>
        <?php if ($modif && $val['photo'] !== '' && file_exists(DOSSIER_UPLOADS . $val['photo'])): ?>
            <img class="photo" src="uploads/<?= h($val['photo']) ?>" alt="Photo actuelle">
        <?php endif; ?>
        <input type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png">

        <button type="submit"><?= $modif ? 'Enregistrer' : 'Ajouter' ?></button>
        <a class="bouton-lien" href="liste.php">Annuler</a>
    </form>
</div>
<?php include 'footer.php'; ?>
