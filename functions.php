<?php
// ---------- Constantes ----------
const FICHIER    = __DIR__ . '/data/etudiants.txt';
const DOSSIER_UPLOADS = __DIR__ . '/uploads/';
const FILIERES   = ['GI', 'IDIA', 'GE', 'GC'];
const UTILISATEURS = [
    'admin' => 'admin123',
    'sara' => 'sara123',
];

// ---------- Session ----------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- Affichage sécurisé ----------
function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ---------- Authentification ----------
function connecte(): bool
{
    return isset($_SESSION['login']);
}

function exiger_connexion(): void
{
    if (!connecte()) {
        header('Location: index.php');
        exit;
    }
}

// ---------- Stockage fichier texte ----------
function nettoyer(string $s): string
{
    $s = str_replace([";", "\r", "\n"], ' ', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
}

function lire_etudiants(): array
{
    $liste = [];
    if (!file_exists(FICHIER)) {
        return $liste;
    }
    $lignes = file(FICHIER, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lignes as $ligne) {
        $p = explode(';', $ligne);
        if (count($p) < 5) {
            continue;
        }
        $id = (int) $p[0];
        $liste[$id] = [
            'id'      => $id,
            'nom'     => $p[1],
            'prenom'  => $p[2],
            'filiere' => $p[3],
            'note'    => (float) $p[4],
            'photo'   => $p[5] ?? '',
        ];
    }
    return $liste;
}

function sauver_etudiants(array $liste): void
{
    $lignes = [];
    foreach ($liste as $e) {
        $lignes[] = implode(';', [
            $e['id'], $e['nom'], $e['prenom'], $e['filiere'], $e['note'], $e['photo'],
        ]);
    }
    file_put_contents(FICHIER, implode("\n", $lignes) . (count($lignes) ? "\n" : ''), LOCK_EX);
}

// ---------- Messages flash ----------
function flash(string $msg, string $type = 'succes'): void
{
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

// ---------- Upload de photo ----------
/**
 * Traite l'upload. Retourne [nom_fichier|'' , erreur|null].
 */
function traiter_photo(array $f): array
{
    if (!isset($f['error']) || $f['error'] === UPLOAD_ERR_NO_FILE) {
        return ['', null];
    }
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return ['', "Erreur lors de l'envoi de la photo (code {$f['error']})."];
    }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        return ['', 'Photo : extension autorisée jpg, jpeg ou png.'];
    }
    if ($f['size'] > 1024 * 1024) {
        return ['', 'Photo : taille maximale 1 Mo.'];
    }
    $nom = uniqid('etu_') . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], DOSSIER_UPLOADS . $nom)) {
        return ['', "Impossible d'enregistrer la photo."];
    }
    return [$nom, null];
}

function supprimer_photo(string $nom): void
{
    $chemin = DOSSIER_UPLOADS . basename($nom);
    if ($nom !== '' && file_exists($chemin)) {
        unlink($chemin);
    }
}
