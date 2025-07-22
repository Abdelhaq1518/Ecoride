<?php
session_start();
require_once __DIR__ . '/../dev/vendor/autoload.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../dev/mongo_doublons.php';

use MongoDB\Client;

$mongo = new Client("mongodb://localhost:27017");
$collection = $mongo->ecoride->avis_covoiturage;

// Supprimer les doublons
supprimerDoublonsAvis($collection);

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$skip = ($page - 1) * $limit;

// Filtre par date
$filter = [];

if (!empty($_GET['filtre_date'])) {
    $date = new DateTime($_GET['filtre_date']);
    $start = clone $date;
    $end = clone $date;
    $start->setTime(0, 0, 0);
    $end->setTime(23, 59, 59);
    $filter['date'] = [
        '$gte' => new MongoDB\BSON\UTCDateTime($start),
        '$lte' => new MongoDB\BSON\UTCDateTime($end)
    ];
}

// Récupération des avis
$avisCursor = $collection->find(
    $filter,
    [
        'sort' => ['est_valide' => 1, 'date' => -1],
        'limit' => $limit,
        'skip' => $skip
    ]
);
$avis = $avisCursor->toArray();
?>
<body>
<link rel="stylesheet" href="/EcoRide/back/public/assets/css/espace_employe.css">

<div class="container mt-5 gestion-avis-wrapper">
    <h1>Gestion des avis</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Formulaire de filtre par date -->
    <form method="GET" class="d-flex align-items-center gap-2 my-4">
        <label for="filtre_date">Filtrer par date :</label>
        <input type="date" name="filtre_date" id="filtre_date" class="form-control"
               value="<?= htmlspecialchars($_GET['filtre_date'] ?? '') ?>">
        <button type="submit" class="btn btn-dark">Rechercher</button>
    </form>

    <?php if (empty($avis)): ?>
        <p>Aucun avis trouvé.</p>
    <?php else: ?>
        <table class="tableau-beige">
            <thead>
                <tr>
                    <th>Note</th>
                    <th>Commentaire</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($avis as $avisItem): ?>
                    <tr>
                        <td><?= htmlspecialchars($avisItem['note'] ?? '') ?></td>
                        <td><?= nl2br(htmlspecialchars($avisItem['commentaire'] ?? '')) ?></td>
                        <td><?= isset($avisItem['date']) ? $avisItem['date']->toDateTime()->format('d/m/Y H:i') : '' ?></td>
                        <td>
                            <?= $avisItem['est_valide']
                                ? '<span class="badge bg-secondary">Validé</span>'
                                : '<span class="badge bg-warning text-dark">En attente</span>' ?>
                        </td>
                        <td>
                            <?php if (!$avisItem['est_valide']): ?>
                                <form method="POST" action="gerer_avis.php" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?= $avisItem['_id'] ?>">
                                    <input type="hidden" name="action" value="valider">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-outline-success btn-sm">Valider</button>
                                </form>
                                <form method="POST" action="gerer_avis.php" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?= $avisItem['_id'] ?>">
                                    <input type="hidden" name="action" value="refuser">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Refuser</button>
                                </form>
                            <?php else: ?>
                                <em>Aucune action</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="text-center mt-4">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&filtre_date=<?= $_GET['filtre_date'] ?? '' ?>" class="btn btn-outline-dark">← Précédent</a>
            <?php endif; ?>
            <?php if (count($avis) === $limit): ?>
                <a href="?page=<?= $page + 1 ?>&filtre_date=<?= $_GET['filtre_date'] ?? '' ?>" class="btn btn-outline-dark">Suivant →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>