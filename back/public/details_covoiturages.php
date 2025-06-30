<?php
require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/../includes/header.php';

$covoiturage_id = $_GET['id'] ?? null;
?>

<!-- Feuille de style spécifique -->
<link rel="stylesheet" href="assets/css/details_covoiturages.css">

<main class="container py-5">

<?php
if ($covoiturage_id) {
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
    $stmt->execute(['id' => $covoiturage_id]);
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($trajet):
?>
    <div class="trajet-result mx-auto">
      <div class="trajet-infos">
        <div>
          <h5><?= htmlspecialchars($trajet['adresse_depart']) ?> → <?= htmlspecialchars($trajet['adresse_arrivee']) ?></h5>
          <p>Départ le <?= htmlspecialchars($trajet['date_depart']) ?> à <?= htmlspecialchars($trajet['heure_depart']) ?></p>
          <p>Arrivée estimée à <?= htmlspecialchars($trajet['heure_arrivee']) ?></p>
          <div class="badges-info d-flex flex-wrap gap-2 mt-2">
            <span class="badge type-trajet"><?= ucfirst($trajet['type_trajet']) ?></span>
            <span class="badge badge-places"><?= $trajet['places_disponibles'] ?> place(s)</span>
            <span class="badge badge-credits"><?= $trajet['cout_credits'] ?> crédits</span>
          </div>
        </div>

        <div class="conducteur mt-2">
          <img src="assets/img/<?= $trajet['photo'] ?? 'default-user.webp' ?>" class="conducteur-img" alt="Photo de <?= $trajet['pseudo'] ?>">
          <p class="conducteur-pseudo"><strong><?= $trajet['pseudo'] ?></strong></p>
          <p class="conducteur-note"><small>Note : <?= $trajet['note_moyenne_conducteur'] ?>/5</small></p>
        </div>
      </div>

      <div class="mt-4">
        <h5 class="fw-semibold">Conducteur</h5>
        <p><?= htmlspecialchars($trajet['prenom']) ?> <?= htmlspecialchars($trajet['nom']) ?></p>
      </div>

      <!-- Bloc véhicule stylisé -->
      <div class="card-vehicule mt-4 p-3 rounded-4">
        <h5 class="fw-semibold mb-3 text-success-emphasis">Véhicule</h5>
        <ul class="list-unstyled mb-0">
          <li><strong>Marque :</strong> <?= htmlspecialchars($trajet['marque'] ?? 'N/A') ?></li>
          <li><strong>Modèle :</strong> <?= htmlspecialchars($trajet['modele'] ?? 'N/A') ?></li>
          <li><strong>Couleur :</strong> <?= htmlspecialchars($trajet['couleur'] ?? 'N/A') ?></li>
          <li><strong>Énergie :</strong> <?= htmlspecialchars($trajet['energie'] ?? 'N/A') ?></li>
          <li><strong>Mise en circulation :</strong> 
            <?= $trajet['date_immatriculation'] 
                ? date('d/m/Y', strtotime($trajet['date_immatriculation'])) 
                : 'N/A' ?>
          </li>
        </ul>
      </div>

      <?php
      $stmtPref = $pdo->prepare("
          SELECT fumeur, animaux, musique, discussion
          FROM preferences_conducteur
          WHERE utilisateur_id = :id
      ");
      $stmtPref->execute(['id' => $trajet['createur_id']]);
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
      <?php else: ?>
        <p class="mt-4">Aucune préférence trouvée.</p>
      <?php endif; ?>
    </div>

<?php
    else:
        echo "<p>Trajet non trouvé.</p>";
    endif;
} else {
    echo "<p>Identifiant de trajet manquant.</p>";
}
?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
