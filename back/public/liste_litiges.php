<?php
session_start();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../dev/vendor/autoload.php';

use MongoDB\Client;

$mongo = new Client("mongodb://localhost:27017");
$collection = $mongo->ecoride->litiges_covoiturage;

// Récupération des litiges (tri par date descendante)
$litiges = $collection->find([], ['sort' => ['statut' => 1, 'date' => -1]])->toArray();
?>

<link rel="stylesheet" href="/EcoRide/back/public/assets/css/espace_employe.css">

<div class="container mt-5 gestion-avis-wrapper">
    <h1>Gestion des litiges</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (empty($litiges)): ?>
        <p>Aucun litige trouvé.</p>
    <?php else: ?>
        <table class="tableau-avis">
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
                <?php foreach ($litiges as $litige): ?>
                    <tr>
                        <td><?= htmlspecialchars($litige['note'] ?? '') ?></td>
                        <td><?= nl2br(htmlspecialchars($litige['commentaire'] ?? '')) ?></td>
                        <td><?= isset($litige['date']) ? $litige['date']->toDateTime()->format('d/m/Y H:i') : '' ?></td>
                        <td>
                            <?php if (($litige['statut'] ?? '') === 'en_attente'): ?>
                                <span class="badge bg-warning text-dark">En attente</span>
                            <?php else: ?>
                                <span class="badge bg-success">Résolu</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($litige['statut'] ?? '') === 'en_attente'): ?>
                                <form method="POST" action="gerer_litiges.php" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?= $litige['_id'] ?>">
                                    <input type="hidden" name="action" value="resoudre">
                                    <button type="submit" class="btn btn-success btn-sm">Résoudre</button>
                                </form>
                            <?php else: ?>
                                <a href="gestion_litiges.php?id=<?= htmlspecialchars($litige['covoiturage_id']) ?>" class="btn btn-info btn-sm">Voir</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
