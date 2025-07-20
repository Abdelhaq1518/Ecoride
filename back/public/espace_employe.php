<?php
session_start();
require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/../dev/vendor/autoload.php'; // MongoDB client
require_once __DIR__ . '/../includes/verify_csrf.php';

use MongoDB\Client;

$userId = $_SESSION['utilisateur']['id'] ?? null;

if (!$userId) {
    header('Location: connexion.php');
    exit;
}

// Vérif rôle employé (role_id = 4)
$stmtRoles = $pdo->prepare("
    SELECT 1 FROM utilisateur_roles ur
    WHERE ur.utilisateur_id = :id AND ur.role_id = 4
");
$stmtRoles->execute([':id' => $userId]);
if (!$stmtRoles->fetchColumn()) {
    require_once __DIR__ . '/../includes/header.php';
    echo "
    <div class='container mt-5'>
        <div class='alert alert-danger'>
            <h4> Accès interdit</h4>
            <p>Seuls les employés peuvent accéder à cette page.</p>
        </div>
    </div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$mongo = new Client("mongodb://localhost:27017");
$collection = $mongo->ecoride->avis_covoiturage;

// Récupérer les avis non validés (est_valide = false)
$avisNonValides = $collection->find(['est_valide' => false])->toArray();

// Récupérer les covoiturages signalés en litige (MySQL)
$stmtLitiges = $pdo->prepare("
    SELECT 
        c.covoiturage_id, 
        c.adresse_depart, 
        c.adresse_arrivee, 
        c.date_depart, 
        c.heure_depart, 
        c.heure_arrivee
    FROM covoiturages c
    INNER JOIN statuts_trajet st ON c.statut_trajet = st.code
    WHERE st.code = 'litige'
    ORDER BY c.date_depart DESC
");
$stmtLitiges->execute();
$trajetsLitiges = $stmtLitiges->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="full-height-container d-flex">
    <nav class="sidebar_espace p-3" style="min-width: 220px; min-height: 100vh;">
        <h5 class="mb-4">Espace Employé</h5>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="espace_employe.php" class="nav-link">Accueil employé</a>
            </li>
            <li class="nav-item">
                <a href="gestion_avis.php" class="nav-link">Gestion des avis</a>
            </li>
            <li class="nav-item">
                <a href="gestion_litiges.php" class="nav-link">Gestion des litiges</a>
            </li>
            <li class="nav-item">
                <a href="deconnexion.php" class="nav-link text-dark">Se déconnecter</a>
            </li>
        </ul>
    </nav>

    <main class="p-4 bg-eco-light flex-grow-1">
        <h1>Espace Employé</h1>

        <section>
            <h2>Avis en attente de validation</h2>
            <?php if (empty($avisNonValides)): ?>
                <p>Aucun avis en attente.</p>
            <?php else: ?>
                <table class="tableau-beige">
                    <thead>
                        <tr>
                            <th>Note</th>
                            <th>Commentaire</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($avisNonValides as $avis): ?>
                            <tr>
                                <td><?= htmlspecialchars($avis['note'] ?? '') ?></td>
                                <td><?= nl2br(htmlspecialchars($avis['commentaire'] ?? '')) ?></td>
                                <td><?= $avis['est_valide'] ? 'Validé' : 'En attente' ?></td>
                                <td><?= isset($avis['date']) ? $avis['date']->toDateTime()->format('d/m/Y H:i') : '' ?></td>
                                <td>
                                    <form method="post" action="gerer_avis.php" style="display:inline-block;">
                                        <input type="hidden" name="id" value="<?= $avis['_id'] ?>">
                                        <input type="hidden" name="action" value="valider">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Valider</button>
                                    </form>
                                    <form method="post" action="gerer_avis.php" style="display:inline-block;">
                                        <input type="hidden" name="id" value="<?= $avis['_id'] ?>">
                                        <input type="hidden" name="action" value="refuser">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Refuser</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <hr>

        <section>
            <h2>Covoiturages litigieux</h2>
            <?php if (empty($trajetsLitiges)): ?>
                <p>Aucun covoiturage litigieux.</p>
            <?php else: ?>
                <table class="tableau-beige">
                    <thead>
                        <tr>
                            <th>ID Trajet</th>
                            <th>Adresse départ</th>
                            <th>Adresse arrivée</th>
                            <th>Date départ</th>
                            <th>Heure départ</th>
                            <th>Heure arrivée</th>
                            <th>Détails</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trajetsLitiges as $trajet): ?>
                            <tr>
                                <td><?= $trajet['covoiturage_id'] ?></td>
                                <td><?= htmlspecialchars($trajet['adresse_depart']) ?></td>
                                <td><?= htmlspecialchars($trajet['adresse_arrivee']) ?></td>
                                <td><?= (new DateTime($trajet['date_depart']))->format('d/m/Y') ?></td>
                                <td><?= htmlspecialchars($trajet['heure_depart']) ?></td>
                                <td><?= htmlspecialchars($trajet['heure_arrivee']) ?></td>
                                <td>
                                    <a href="details_litige.php?id=<?= $trajet['covoiturage_id'] ?>" class="btn btn-primary btn-sm">
                                        Voir détails
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
