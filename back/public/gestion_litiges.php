<?php
session_start();

require_once __DIR__ . '/../dev/vendor/autoload.php';
$pageStyles = ['/EcoRide/back/public/assets/css/espace_employe.css'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../dev/mongo_doublons.php';

use MongoDB\Client;

$mongo = new Client("mongodb://localhost:27017");
$collection = $mongo->ecoride->litiges;



$dateFiltrage = $_GET['date'] ?? null;
$filter = ['statut' => ['$ne' => 'resolu']];

if ($dateFiltrage) {
    $jour = new DateTime($dateFiltrage);
    $debut = clone $jour;
    $fin = clone $jour;
    $debut->setTime(0, 0, 0);
    $fin->setTime(23, 59, 59);
    $filter['date_signalement'] = [
        '$gte' => new MongoDB\BSON\UTCDateTime($debut->getTimestamp() * 1000),
        '$lte' => new MongoDB\BSON\UTCDateTime($fin->getTimestamp() * 1000)
    ];
}

$options = ['sort' => ['date_signalement' => -1], 'limit' => 5];
$litiges = $collection->find($filter, $options)->toArray();
?>

<link rel="stylesheet" href="/EcoRide/back/public/assets/css/espace_employe.css">

<div class="container mt-5 gestion-litiges-wrapper">
    <h1 class="mb-4">Gestion des litiges</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Calendrier de filtrage -->
    <form method="get" class="mb-4">
        <label for="date">Filtrer par date :</label>
        <input type="date" name="date" id="date" value="<?= htmlspecialchars($dateFiltrage ?? '') ?>">
        <button type="submit" class="btn btn-outline-dark btn-sm">Filtrer</button>
        <a href="gestion_litiges.php" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
    </form>

    <?php if (!is_array($litiges) || count($litiges) === 0): ?>
        <p class="text-muted">Aucun litige trouvé pour cette période.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table tableau-avis table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>ID Covoiturage</th>
                        <th>Motif</th>
                        <th>Description</th>
                        <th>Date signalement</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($litiges as $litige): ?>
                        <tr>
                            <td><?= htmlspecialchars($litige['covoiturage_id'] ?? 'Inconnu') ?></td>
                            <td><?= htmlspecialchars($litige['motif'] ?? 'Non précisé') ?></td>
                            <td><?= nl2br(htmlspecialchars($litige['description'] ?? '')) ?></td>
                            <td>
                                <?php
                                if (isset($litige['date_signalement']) && $litige['date_signalement'] instanceof MongoDB\BSON\UTCDateTime) {
                                    echo $litige['date_signalement']->toDateTime()->format('d/m/Y H:i');
                                } else {
                                    echo 'Date inconnue';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark">
                                    <?= htmlspecialchars($litige['statut'] ?? 'En attente') ?>
                                </span>
                            </td>
                            <td>
                                <a href="details_litige.php?id=<?= $litige['covoiturage_id'] ?>" class="btn btn-dark btn-sm">Gérer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
