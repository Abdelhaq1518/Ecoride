<?php
session_start();
require_once 'config.php'; 
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/../dev/vendor/autoload.php';



use MongoDB\Client;

$covoiturage_id = $_GET['id'] ?? null;
?>

<main class="container py-5">
<?php
if ($covoiturage_id && is_numeric($covoiturage_id)) {
    $stmt = $pdo->prepare("
        SELECT 
            c.*, 
            u.pseudo, u.nom, u.prenom, u.photo, u.note_moyenne AS note_moyenne_conducteur,
            v.marque, v.modele, v.energie, v.date_immatriculation, v.couleur
        FROM covoiturages c
        JOIN utilisateurs u ON u.id = c.createur_id
        LEFT JOIN voiture v ON c.vehicule_id = v.voiture_id
        WHERE c.covoiturage_id = :id
    ");
    $stmt->bindValue(':id', $covoiturage_id, PDO::PARAM_INT);
    $stmt->execute();
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($trajet):
        $type_raw = $trajet['type_trajet'];
        $type = strtolower($type_raw);
        $type = str_replace('é', 'e', $type);
        $classe_badge = ($type === 'ecologique') ? 'badge-eco' : 'badge-standard';

        $user_id = $_SESSION['utilisateur']['id'] ?? null;
        $user_credits = 0;
        $a_deja_participe = false;

        if ($user_id) {
            $stmtCredits = $pdo->prepare("SELECT credits FROM utilisateurs WHERE id = :id");
            $stmtCredits->bindValue(':id', $user_id, PDO::PARAM_INT);
            $stmtCredits->execute();
            $user = $stmtCredits->fetch(PDO::FETCH_ASSOC);
            $user_credits = $user ? (int)$user['credits'] : 0;

            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM participations WHERE utilisateur_id = :uid AND covoiturage_id = :cid");
            $stmtCheck->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmtCheck->bindValue(':cid', $covoiturage_id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $a_deja_participe = $stmtCheck->fetchColumn() > 0;
        }

        // Connexion à MongoDB
        $mongo = new Client("mongodb://localhost:27017");
        $collection = $mongo->ecoride->avis_covoiturage;

        // Récupérer les avis validés pour ce covoiturage
        $avis_valides = $collection->find([
            'covoiturage_id' => (int)$covoiturage_id,
            'est_valide' => true
        ])->toArray();

        // Récupérer les infos des utilisateurs ayant laissé un avis
        $idsUtilisateurs = array_unique(array_column($avis_valides, 'utilisateur_id'));

        $mapUtilisateurs = [];
if (count($idsUtilisateurs) > 0) {
    $idsUtilisateurs = array_values($idsUtilisateurs); // ✅ Corrige l'erreur
    $in = str_repeat('?,', count($idsUtilisateurs) - 1) . '?';
    $stmtUsers = $pdo->prepare("SELECT id, pseudo, photo FROM utilisateurs WHERE id IN ($in)");
    $stmtUsers->execute($idsUtilisateurs);
    $utilisateurs = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    foreach ($utilisateurs as $u) {
        $mapUtilisateurs[$u['id']] = $u;
    }
}
?>
    <div class="trajet-result mx-auto">
        <div class="trajet-infos">
            <div>
                <h5><?= htmlspecialchars($trajet['adresse_depart']) ?> → <?= htmlspecialchars($trajet['adresse_arrivee']) ?></h5>
                <p>Départ le <?= htmlspecialchars($trajet['date_depart']) ?> à <?= htmlspecialchars($trajet['heure_depart']) ?></p>
                <p>Arrivée estimée à <?= htmlspecialchars($trajet['heure_arrivee']) ?></p>
                <div class="badges-info d-flex flex-wrap gap-2 mt-2">
                    <span class="badge <?= $classe_badge ?>"><?= ucfirst(htmlspecialchars($type_raw)) ?></span>
                    <span class="badge-custom badge-places"><?= (int)$trajet['places_disponibles'] ?> place(s)</span>
                    <span class="badge-custom badge-credits"><?= (int)$trajet['cout_credits'] ?> crédits</span>
                    <?php if ((int)$trajet['places_disponibles'] <= 0): ?>
                        <span class="badge-custom badge-complet">Complet</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="conducteur mt-2">
                <img src="assets/img/<?= htmlspecialchars($trajet['photo'] ?? 'default-user.webp') ?>" class="conducteur-img" alt="Photo de <?= htmlspecialchars($trajet['pseudo']) ?>">
                <p class="conducteur-pseudo"><strong><?= htmlspecialchars($trajet['pseudo']) ?></strong></p>
                <p class="conducteur-note"><small>Note : <?= htmlspecialchars($trajet['note_moyenne_conducteur']) ?>/5</small></p>
            </div>
        </div>

        <div class="mt-4">
            <h5 class="fw-semibold">Conducteur</h5>
            <p><?= htmlspecialchars($trajet['prenom']) ?> <?= htmlspecialchars($trajet['nom']) ?></p>
        </div>

        <div class="card-vehicule mt-4 p-3 rounded-4">
            <h5 class="fw-semibold mb-3 text-success-emphasis">Véhicule</h5>
            <ul class="list-unstyled mb-0">
                <li><strong>Marque :</strong> <?= htmlspecialchars($trajet['marque'] ?? 'N/A') ?></li>
                <li><strong>Modèle :</strong> <?= htmlspecialchars($trajet['modele'] ?? 'N/A') ?></li>
                <li><strong>Couleur :</strong> <?= htmlspecialchars($trajet['couleur'] ?? 'N/A') ?></li>
                <li><strong>Énergie :</strong> <?= htmlspecialchars($trajet['energie'] ?? 'N/A') ?></li>
                <li><strong>Mise en circulation :</strong> <?= $trajet['date_immatriculation'] ? date('d/m/Y', strtotime($trajet['date_immatriculation'])) : 'N/A' ?></li>
            </ul>
        </div>

        <?php
        $stmtPref = $pdo->prepare("
            SELECT fumeur, animaux, musique, discussion
            FROM preferences_conducteur
            WHERE utilisateur_id = :id
        ");
        $stmtPref->bindValue(':id', $trajet['createur_id'], PDO::PARAM_INT);
        $stmtPref->execute();
        $prefs = $stmtPref->fetch(PDO::FETCH_ASSOC);

        if ($prefs): ?>
            <div class="preferences mt-4">
                <h5 class="fw-semibold">Préférences du conducteur</h5>
                <ul class="list-unstyled">
                    <li>Fumeur : <?= $prefs['fumeur'] ? "Oui" : "Non" ?></li>
                    <li>Animaux : <?= $prefs['animaux'] ? "Oui" : "Non" ?></li>
                    <li>Musique : <?= $prefs['musique'] ? "Oui" : "Non" ?></li>
                    <li>Discussion : <?= ucfirst(htmlspecialchars($prefs['discussion'])) ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="participation-section mt-4">
            <?php if ((int)$trajet['places_disponibles'] <= 0): ?>
                <button class="btn btn-secondary" disabled>Complet</button>
            <?php elseif (!$user_id): ?>
                <a href="connexion.php" class="btn-custom">Se connecter / S'inscrire</a>
            <?php elseif ($a_deja_participe): ?>
                <button class="btn btn-outline-success" disabled>Participation prise en compte</button>
            <?php else: ?>
                <button id="btn-participer" 
                    class="btn btn-success"
                    data-covoiturage-id="<?= htmlspecialchars($trajet['covoiturage_id']) ?>"
                    data-cout-credits="<?= (int)$trajet['cout_credits'] ?>"
                    data-user-credits="<?= $user_credits ?>"
                >Participer</button>
            <?php endif; ?>
        </div>

   <?php if (!empty($avis_valides)): ?>
    <div class="avis-section mt-5">
        <h5 class="fw-semibold">Avis des passagers</h5>
        <?php
            $avisAffiches = [];
            foreach ($avis_valides as $avis):
                $cle = $avis['utilisateur_id'] . '-' . $avis['covoiturage_id'];
                if (isset($avisAffiches[$cle])) {
                    continue; // On a déjà affiché un avis pour ce duo utilisateur-trajet
                }
                $avisAffiches[$cle] = true;

                $user = $mapUtilisateurs[$avis['utilisateur_id']] ?? null;
                $photo = $user['photo'] ?? 'default-user.webp';
                $pseudo = $user['pseudo'] ?? 'Utilisateur';
        ?>
            <div class="avis-passager">
                <div class="d-flex align-items-center mb-2">
                    <img src="assets/img/<?= htmlspecialchars($photo) ?>" class="avatar-avis me-3" alt="Photo de <?= htmlspecialchars($pseudo) ?>">
                    <strong><?= htmlspecialchars($pseudo) ?></strong>
                    <span class="ms-auto"><?= $avis['note'] ?>/5 ★</span>
                </div>
                <p class="mb-0"><?= nl2br(htmlspecialchars($avis['commentaire'] ?? '')) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


        <!-- Modal -->
        <div class="modal fade" id="participationModal" tabindex="-1" aria-labelledby="participationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="participationModalLabel">Confirmation</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body" id="participationModalBody"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-primary" id="confirmParticipationBtn">Confirmer</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('btn-participer');
            if (!btn) return;

            const modalElement = document.getElementById('participationModal');
            const modal = new bootstrap.Modal(modalElement);
            const confirmBtn = document.getElementById('confirmParticipationBtn');
            const modalBody = document.getElementById('participationModalBody');

            btn.addEventListener('click', function () {
                const covoiturageId = this.dataset.covoiturageId;
                const coutCredits = parseInt(this.dataset.coutCredits, 10);
                const userCredits = parseInt(this.dataset.userCredits, 10);

                if (userCredits < coutCredits) {
                    modalBody.textContent = "Crédits insuffisants pour ce trajet.";
                    confirmBtn.style.display = "none";
                    modal.show();
                    return;
                }

                modalBody.textContent = `Êtes-vous sûr de vouloir utiliser ${coutCredits} crédits pour ce trajet ?`;
                confirmBtn.style.display = "inline-block";

                confirmBtn.onclick = function () {
                    fetch('participer.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ covoiturage_id: covoiturageId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        modalBody.textContent = data.message;
                        confirmBtn.style.display = "none";
                        if (data.success) {
                            btn.disabled = true;
                            btn.textContent = "Participation prise en compte";
                        }
                        setTimeout(() => modal.hide(), 1500);
                    })
                    .catch(() => {
                        modalBody.textContent = "Une erreur est survenue. Veuillez réessayer.";
                        confirmBtn.style.display = "none";
                    });
                };

                modal.show();
            });
        });
        </script>
    </div>
<?php
    else:
        echo "<p>Trajet non trouvé.</p>";
    endif;
} else {
    echo "<p>Identifiant de trajet manquant ou invalide.</p>";
}
?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
