document.addEventListener('DOMContentLoaded', function () {
    // Confirmation avant suppression
    document.querySelectorAll('.lien-suppr').forEach(function (lien) {
        lien.addEventListener('click', function (e) {
            if (!confirm('Voulez-vous vraiment supprimer cet étudiant ?')) {
                e.preventDefault();
            }
        });
    });

    // Recherche instantanée
    var champ = document.getElementById('recherche');
    if (champ) {
        champ.addEventListener('input', function () {
            var texte = champ.value.trim().toLowerCase();
            document.querySelectorAll('#tableau tbody tr').forEach(function (ligne) {
                if (ligne.querySelector('.vide')) return;
                var nom = ligne.cells[1].textContent.toLowerCase();
                var prenom = ligne.cells[2].textContent.toLowerCase();
                var visible = nom.includes(texte) || prenom.includes(texte);
                ligne.style.display = visible ? '' : 'none';
            });
        });
    }
});
