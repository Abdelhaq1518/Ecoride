<?php
session_start();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../dev/vendor/autoload.php';

use MongoDB\Client;

if (!isset($_SESSION['utilisateur']['id'])) {
    header('Location: connexion.php');
    exit;
}

// Vérifier que l'utilisateur est un employé (role_id = 4)
require_once __DIR__ . '/../dev/db.php';
$userId = $_SESSION['utilisateur']['id'];
$stmt = $pdo->prepare("SELECT 1 FROM utilisateur_roles WHERE utilisateur_id = :id AND role_id = 4");
$stmt->execute([':id' => $userId]);

if (!$stmt->fetchColumn()) {
    echo "Accès refusé.";
    exit;
}

// Vérifier qu’un id de covoiturage est présent
$covoiturage_id = $_GET['id'] ?? null;
if (!$covoiturage_id) {
    die('ID covoiturage manquant.');
}

// Connexion MongoDB
$mongo = new Client("mongodb://localhost:27017");
$collection = $mongo->ecoride->litiges;

// Récupération du litige lié au covoiturage
$litige = $collection->findOne(['covoiturage_id' => (int)$covoiturage_id]);

if (!$litige) {
    echo "<div class='container mt-5'><h3>Aucun litige trouvé pour le covoiturage #$covoiturage_id</h3></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Récupération des infos du covoiturage concerné (depuis MySQL)
$stmtTrajet = $pdo->prepare("SELECT * FROM covoiturages WHERE covoiturage_id = :id");
$stmtTrajet->execute([':id' => $covoiturage_id]);
$trajet = $stmtTrajet->fetch(PDO::FETCH_ASSOC);

if (!$trajet) {
    echo "<div class='container mt-5'><h3>Trajet introuvable.</h3></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<div class="container mt-5">
    <h2>Litige pour le covoiturage #<?= htmlspecialchars($covoiturage_id) ?></h2>

    <p><strong>Description :</strong> <?= htmlspecialchars($trajet['description'] ?? 'N/A') ?></p>
    <p><strong>Départ :</strong> <?= htmlspecialchars($trajet['adresse_depart']) ?></p>
    <p><strong>Arrivée :</strong> <?= htmlspecialchars($trajet['adresse_arrivee']) ?></p>
    <p><strong>Date :</strong> <?= (new DateTime($trajet['date_depart']))->format('d/m/Y') ?> à <?= htmlspecialchars($trajet['heure_depart']) ?></p>

    <hr>
    <h4>Détails du litige :</h4>
    <p><strong>Note :</strong> <?= htmlspecialchars($litige['note'] ?? 'N/A') ?></p>
    <p><strong>Commentaire :</strong> <?= nl2br(htmlspecialchars($litige['commentaire'] ?? '')) ?></p>
    <p><strong>Date :</strong> <?= $litige['date'] ? $litige['date']->toDateTime()->format('d/m/Y H:i') : 'N/A' ?></p>
    <p><strong>Statut :</strong> <span class="badge bg-<?= ($litige['statut'] === 'en_attente' ? 'warning text-dark' : 'success') ?>"><?= htmlspecialchars($litige['statut']) ?></span></p>

    <a href="liste_litiges.php" class="btn btn-secondary mt-3">Retour</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
