<?php
session_start();
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="/EcoRide/back/public/assets/css/espace_utilisateur.css">';
require_once __DIR__ . '/../dev/db.php';

// Vérification de connexion
if (!isset($_SESSION['utilisateur']['id'])) {
    echo "<p>Vous devez être connecté pour voir votre historique.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$userId = $_SESSION['utilisateur']['id'];
$dateFilter = $_GET['date_filter'] ?? null;

// Rôles
$stmtRoles = $pdo->prepare("
    SELECT r.libelle 
    FROM utilisateur_roles ur
    JOIN roles r ON ur.role_id = r.role_id
    WHERE ur.utilisateur_id = :id
");
$stmtRoles->bindValue(':id', $userId, PDO::PARAM_INT);
$stmtRoles->execute();
$roles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);

$isChauffeur = in_array('chauffeur', $roles) || in_array('combo', $roles);
$isPassager = in_array('passager', $roles) || in_array('combo', $roles);

// Pagination
$limit = 5;
$pageChauffeur = (int)($_GET['page_chauffeur'] ?? 1);
$pagePassager = (int)($_GET['page_passager'] ?? 1);
$offsetChauffeur = ($pageChauffeur - 1) * $limit;
$offsetPassager = ($pagePassager - 1) * $limit;

// Trajets chauffeur
$trajetsChauffeur = [];
$totalChauffeur = 0;
if ($isChauffeur) {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM covoiturages WHERE createur_id = :id" . ($dateFilter ? " AND date_depart = :date" : ""));
    $stmtCount->bindValue(':id', $userId, PDO::PARAM_INT);
    if ($dateFilter) $stmtCount->bindValue(':date', $dateFilter);
    $stmtCount->execute();
    $totalChauffeur = (int)$stmtCount->fetchColumn();

    $query = "SELECT * FROM covoiturages WHERE createur_id = :id";
    if ($dateFilter) $query .= " AND date_depart = :date";
    $query .= " ORDER BY date_depart ASC, heure_depart ASC LIMIT :limit OFFSET :offset";

    $stmtChauffeur = $pdo->prepare($query);
    $stmtChauffeur->bindValue(':id', $userId, PDO::PARAM_INT);
    if ($dateFilter) $stmtChauffeur->bindValue(':date', $dateFilter);
    $stmtChauffeur->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtChauffeur->bindValue(':offset', $offsetChauffeur, PDO::PARAM_INT);
    $stmtChauffeur->execute();
    $trajetsChauffeur = $stmtChauffeur->fetchAll(PDO::FETCH_ASSOC);
}

// Trajets passager
$trajetsPassager = [];
$totalPassager = 0;
if ($isPassager) {
    $stmtCountP = $pdo->prepare("SELECT COUNT(*) FROM participations p JOIN covoiturages c ON p.covoiturage_id = c.covoiturage_id WHERE p.utilisateur_id = :id" . ($dateFilter ? " AND c.date_depart = :date" : ""));
    $stmtCountP->bindValue(':id', $userId, PDO::PARAM_INT);
    if ($dateFilter) $stmtCountP->bindValue(':date', $dateFilter);
    $stmtCountP->execute();
    $totalPassager = (int)$stmtCountP->fetchColumn();

    $query = "
        SELECT c.*, p.validation_passager 
        FROM covoiturages c 
        JOIN participations p ON p.covoiturage_id = c.covoiturage_id 
        WHERE p.utilisateur_id = :id
    ";
    if ($dateFilter) $query .= " AND c.date_depart = :date";
    $query .= " ORDER BY c.date_depart ASC, c.heure_depart ASC LIMIT :limit OFFSET :offset";

    $stmtPassager = $pdo->prepare($query);
    $stmtPassager->bindValue(':id', $userId, PDO::PARAM_INT);
    if ($dateFilter) $stmtPassager->bindValue(':date', $dateFilter);
    $stmtPassager->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtPassager->bindValue(':offset', $offsetPassager, PDO::PARAM_INT);
    $stmtPassager->execute();
    $trajetsPassager = $stmtPassager->fetchAll(PDO::FETCH_ASSOC);
}

function renderPagination($total, $limit, $currentPage, $paramName) {
    $pages = (int)ceil($total / $limit);
    if ($pages <= 1) return;
    echo '<nav class="ma-pagination"><ul class="ma-page-list">';
    $maxToShow = min(5, $pages);
    for ($i = 1; $i <= $maxToShow; $i++) {
        $active = ($i == $currentPage) ? ' ma-page-active' : '';
        echo "<li class='ma-page-item$active'><a class='ma-page-link' href='?{$paramName}={$i}'>$i</a></li>";
    }
    echo '</ul></nav>';
}
?>

<main class="container mt-5"><!-- Main utilisé pour sticky footer -->
    <?php if ($dateFilter): ?>
        <p class="text-muted">
             Filtré par date :
            <?php
                $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
                echo $formatter->format(new DateTime($dateFilter));
            ?>
            <a href="historique.php" class="btn btn-sm btn-outline-secondary ms-2">Réinitialiser</a>
        </p>
    <?php endif; ?>

    <?php if ($isChauffeur): ?>
        <h2>Mes trajets en tant que chauffeur</h2>
        <form method="get" class="mb-3">
            <input type="date" name="date_filter" class="form-control"
                   min="<?= date('Y-m-d') ?>"
                   max="<?= date('Y') ?>-12-21"
                   value="<?= htmlspecialchars($dateFilter ?? date('Y-m-d')) ?>"
                   required>
            <input type="hidden" name="page_chauffeur" value="1">
            <button type="submit" class="btn btn-sm btn-secondary mt-2">Filtrer</button>
        </form>
        <?php if (empty($trajetsChauffeur)): ?>
            <p>Aucun trajet créé.</p>
        <?php else: ?>
            <ul class="list-group mb-3">
                <?php foreach ($trajetsChauffeur as $trajet): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($trajet['adresse_depart']) ?> → <?= htmlspecialchars($trajet['adresse_arrivee']) ?></strong><br>
                            <?php
                            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
                            echo $formatter->format(new DateTime($trajet['date_depart'])) . ' à ' . htmlspecialchars($trajet['heure_depart']);
                            ?>
                            <?php if ($trajet['statut'] === 'annulé'): ?>
                                <span class="badge bg-danger ms-2">annulé</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($trajet['statut'] !== 'annulé'): ?>
                            <button class="btn btn-sm btn-danger annuler-btn" data-id="<?= $trajet['covoiturage_id'] ?>">Annuler</button>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php renderPagination($totalChauffeur, $limit, $pageChauffeur, 'page_chauffeur'); ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($isPassager): ?>
        <h2>Mes trajets en tant que passager</h2>
        <form method="get" class="mb-3">
            <input type="date" name="date_filter" class="form-control"
                   min="<?= date('Y-m-d') ?>"
                   max="<?= date('Y') ?>-12-21"
                   value="<?= htmlspecialchars($dateFilter ?? date('Y-m-d')) ?>"
                   required>
            <input type="hidden" name="page_passager" value="1">
            <button type="submit" class="btn btn-sm btn-secondary mt-2">Filtrer</button>
        </form>
        <?php if (empty($trajetsPassager)): ?>
            <p>Vous n'avez participé à aucun trajet.</p>
        <?php else: ?>
            <ul class="list-group mb-3">
                <?php foreach ($trajetsPassager as $trajet): ?>
                    <li class="list-group-item">
                        <strong><?= htmlspecialchars($trajet['adresse_depart']) ?> → <?= htmlspecialchars($trajet['adresse_arrivee']) ?></strong><br>
                        <?php
                        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
                        echo $formatter->format(new DateTime($trajet['date_depart'])) . ' à ' . htmlspecialchars($trajet['heure_depart']);
                        ?>
                        <?php if ($trajet['statut'] === 'annulé'): ?>
                            <span class="badge bg-danger ms-2">annulé</span>
                        <?php endif; ?>

                        <?php if ($trajet['validation_passager'] == 0 && $trajet['statut'] === 'arrivee'): ?>
                            <!-- Bouton pour valider le trajet -->
                            <form class="mt-2 valider-trajet-form" data-id="<?= $trajet['covoiturage_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success">Confirmer que le trajet s'est bien déroulé</button>
                            </form>
                        <?php elseif ($trajet['validation_passager'] == 1): ?>
                            <span class="badge bg-success ms-2">Trajet validé</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php renderPagination($totalPassager, $limit, $pagePassager, 'page_passager'); ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!$isChauffeur && !$isPassager): ?>
        <p>Vous n'avez pas encore de rôle actif (chauffeur ou passager).</p>
    <?php endif; ?>
</main>

<div class="modal fade" id="modalAnnulationInfo" tabindex="-1" aria-labelledby="modalAnnulationInfoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 1rem;">
      <div class="modal-header bg-warning-subtle">
        <h5 class="modal-title" id="modalAnnulationInfoLabel">Trajet annulé</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        ✅ Votre trajet a bien été annulé.<br>
        💳 Les participants ont été remboursés automatiquement.<br>
        Le trajet est désormais marqué comme <strong>annulé</strong>.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-custom" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.annuler-btn').forEach(button => {
    button.addEventListener('click', () => {
        const trajetId = button.getAttribute('data-id');
        fetch('annuler_trajet.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'covoiturage_id=' + encodeURIComponent(trajetId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = new bootstrap.Modal(document.getElementById('modalAnnulationInfo'));
                modal.show();
                setTimeout(() => window.location.reload(), 4000);
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error(error);
            alert("Erreur lors de l'annulation.");
        });
    });
});

document.querySelectorAll('.valider-trajet-form').forEach(form => {
    form.addEventListener('submit', e => {
        e.preventDefault();
        const trajetId = form.getAttribute('data-id');
        fetch('valider_trajet.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'covoiturage_id=' + encodeURIComponent(trajetId)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Trajet validé avec succès !');
                window.location.reload();
            } else {
                alert(data.message || 'Erreur lors de la validation.');
            }
        })
        .catch(() => alert('Erreur réseau.'));
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
