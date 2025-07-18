<?php
session_start();
require_once __DIR__ . '/../dev/vendor/autoload.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../dev/mongo_doublons.php';
supprimerDoublonsAvis($collection);
?>

<link rel="stylesheet" href="/EcoRide/back/public/assets/css/espace_employe.css">

<?php
use MongoDB\Client;

$mongo = new Client("mongodb://localhost:27017");
$collection = $mongo->ecoride->avis_covoiturage;

// Tous les avis triés (non validés en premier)
$avis = $collection->find([], ['sort' => ['est_valide' => 1, 'date' => -1]])->toArray();
?>

<div class="container mt-5 gestion-avis-wrapper">
    <h1>Gestion des avis</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (empty($avis)): ?>
        <p>Aucun avis trouvé.</p>
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
                <?php foreach ($avis as $avisItem): ?>
                    <tr>
                        <td><?= htmlspecialchars($avisItem['note'] ?? '') ?></td>
                        <td><?= nl2br(htmlspecialchars($avisItem['commentaire'] ?? '')) ?></td>
                        <td><?= isset($avisItem['date']) ? $avisItem['date']->toDateTime()->format('d/m/Y H:i') : '' ?></td>
                        <td>
                            <?= $avisItem['est_valide'] ? '<span class="badge bg-success">Validé</span>' : '<span class="badge bg-warning text-dark">En attente</span>' ?>
                        </td>
                        <td>
                            <?php if (!$avisItem['est_valide']): ?>
                                <form method="POST" action="gerer_avis.php" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?= $avisItem['_id'] ?>">
                                    <input type="hidden" name="action" value="valider">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm">Valider</button>
                                </form>
                                <form method="POST" action="gerer_avis.php" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?= $avisItem['_id'] ?>">
                                    <input type="hidden" name="action" value="refuser">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Refuser</button>
                                </form>
                            <?php else: ?>
                                <em>Aucune action</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
