<?php
session_start();

// Imports
require_once 'config.php'; 
$pageStyles = ['BASE_URL .assets/css/details_covoiturages.css'];
require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../dev/vendor/autoload.php'; // MongoDB

use MongoDB\Client;

// Vérifie que l’ID est bien fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>Identifiant de covoiturage invalide.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$covoiturageId = (int) $_GET['id'];

// Requête SQL : détails du covoiturage
$stmt = $pdo->prepare("
    SELECT 
        covoiturages.covoiturage_id,
        covoiturages.adresse_depart,
        covoiturages.adresse_arrivee,
        covoiturages.date_depart,
        covoiturages.heure_depart,
        covoiturages.heure_arrivee,
        u1.pseudo AS chauffeur_pseudo,
        u1.email AS chauffeur_email,
        u2.pseudo AS passager_pseudo,
        u2.email AS passager_email
    FROM covoiturages
    LEFT JOIN utilisateurs u1 ON covoiturages.createur_id = u1.id
    LEFT JOIN participations ON participations.covoiturage_id = covoiturages.covoiturage_id
    LEFT JOIN utilisateurs u2 ON participations.utilisateur_id = u2.id
    WHERE covoiturages.covoiturage_id = :id
    LIMIT 1
");
$stmt->bindValue(':id', $covoiturageId, PDO::PARAM_INT);
$stmt->execute();
$details = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$details) {
    echo "<div class='alert alert-warning'>Aucun covoiturage trouvé pour cet identifiant.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Connexion à MongoDB pour récupérer le litige
$mongo = new Client("mongodb://localhost:27017");
$collection = $mongo->selectCollection('ecoride', 'litiges');
$litige = $collection->findOne(['covoiturage_id' => $covoiturageId]);
$statutLitige = $litige['statut'] ?? 'inconnu';

?>

<div class="container mt-5">
    <h2>Détails du litige - Covoiturage #<?= htmlspecialchars($details['covoiturage_id']) ?></h2>

    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div style="background-color: #cce5ff; color: #084298; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #b6d4fe;">
            Le litige a été résolu et le chauffeur a bien été crédité.
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card trajet-result">
                <h4>Participants</h4>
                <p><strong>Chauffeur :</strong> <?= htmlspecialchars($details['chauffeur_pseudo']) ?> (<?= htmlspecialchars($details['chauffeur_email']) ?>)</p>
                <p><strong>Passager :</strong> <?= htmlspecialchars($details['passager_pseudo']) ?> (<?= htmlspecialchars($details['passager_email']) ?>)</p>
                <p><strong>Statut du litige :</strong> <?= htmlspecialchars($statutLitige) ?></p>

                <!-- Bouton Contacter -->
                <a href="mailto:<?= htmlspecialchars($details['chauffeur_email']) ?>" class="btn-custom mt-3">Contacter le chauffeur</a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card trajet-result">
                <h4>Détails du trajet</h4>
                <p><strong>Date :</strong> <?= (new DateTime($details['date_depart']))->format('d/m/Y') ?></p>
                <p><strong>Heure de départ :</strong> <?= htmlspecialchars($details['heure_depart']) ?></p>
                <p><strong>Heure d’arrivée :</strong> <?= htmlspecialchars($details['heure_arrivee']) ?></p>
                <p><strong>Adresse de départ :</strong> <?= htmlspecialchars($details['adresse_depart']) ?></p>
                <p><strong>Adresse d’arrivée :</strong> <?= htmlspecialchars($details['adresse_arrivee']) ?></p>

                <!-- Formulaire Créditer -->
                <?php if ($statutLitige !== 'resolu'): ?>
                    <form method="post" action="recredit_chauffeur.php" class="mt-3">
                        <input type="hidden" name="chauffeur_email" value="<?= htmlspecialchars($details['chauffeur_email']) ?>">
                        <input type="hidden" name="covoiturage_id" value="<?= htmlspecialchars($details['covoiturage_id']) ?>">
                        <button type="submit" class="btn-custom">Créditer le chauffeur</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-secondary mt-3">Le litige a déjà été résolu. Le chauffeur a été crédité.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>