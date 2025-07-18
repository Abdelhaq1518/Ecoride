<?php
session_start();
date_default_timezone_set('Europe/Paris');
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="/EcoRide/back/public/assets/css/espace_utilisateur.css">';
require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/../dev/mailer.php';

if (!isset($_SESSION['utilisateur']['id'])) {
    echo "<p>Vous devez être connecté pour accéder à cette page.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$userId = $_SESSION['utilisateur']['id'];
$statuts_valides = ['en_attente', 'en_cours', 'arrivee', 'valide', 'litige'];

function renderPagination($total, $limit, $currentPage) {
    $pages = ceil($total / $limit);
    if ($pages <= 1) return;
    echo '<nav class="ma-pagination mt-3"><ul class="ma-page-list">';
    for ($i = 1; $i <= $pages; $i++) {
        $active = ($i == $currentPage) ? ' ma-page-active' : '';
        $dateFilter = $_GET['date_filter'] ?? '';
        echo "<li class='ma-page-item$active'><a class='ma-page-link' href='?page=$i&date_filter=" . urlencode($dateFilter) . "'>$i</a></li>";
    }
    echo '</ul></nav>';
}

$dateFilter = $_GET['date_filter'] ?? null;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;
$today = date('Y-m-d');
$maxDate = '2025-12-21';

if ($dateFilter) {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM covoiturages WHERE createur_id = :id AND date_depart = :date");
    $stmtCount->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmtCount->bindValue(':date', $dateFilter);
    $stmtCount->execute();
    $total = (int)$stmtCount->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM covoiturages WHERE createur_id = :id AND date_depart = :date ORDER BY date_depart ASC, heure_depart ASC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':date', $dateFilter);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT * FROM covoiturages WHERE createur_id = :id AND date_depart >= :today ORDER BY date_depart ASC, heure_depart ASC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':today', $today);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function libelleStatut($statut) {
    return match ($statut) {
        'en_attente' => 'En attente',
        'en_cours' => 'En cours',
        'arrivee' => 'Arrivée',
        'valide' => 'Validé',
        'litige' => 'Litige',
        default => ucfirst($statut)
    };
}
?>

<div class="container mt-5 mb-5">
    <h2>Démarrer / Arrêter un covoiturage</h2>

    <?php if ($dateFilter): ?>
        <p class="text-muted">📅 Filtré par date : <?= (new DateTime($dateFilter))->format('d/m/Y') ?>
            <a href="statut_trajet.php" class="btn btn-sm btn-outline-secondary ms-2">Réinitialiser</a>
        </p>
    <?php endif; ?>

    <form method="get" class="mb-3">
        <input type="date" name="date_filter" class="form-control" min="<?= $today ?>" max="<?= $maxDate ?>" required>
        <input type="hidden" name="page" value="1">
        <button type="submit" class="btn btn-sm btn-secondary mt-2">Filtrer</button>
    </form>

    <div id="messageStatut" style="margin-bottom:1rem;"></div>

    <?php if (empty($trajets)): ?>
        <p>Aucun trajet à afficher.</p>
    <?php else: ?>
        <ul class="list-group">
            <?php foreach ($trajets as $trajet): 
                $heureDepart = new DateTime($trajet['date_depart'] . ' ' . $trajet['heure_depart']);
                $heureArrivee = new DateTime($trajet['date_depart'] . ' ' . $trajet['heure_arrivee']);
                $dureeSeconds = $heureArrivee->getTimestamp() - $heureDepart->getTimestamp();
                $now = new DateTime();
                $elapsedSeconds = $now->getTimestamp() - $heureDepart->getTimestamp();
                $seuil50 = $dureeSeconds * 0.1;

                // Gestion des validations après arrivée
                if ($trajet['statut_trajet'] === 'arrivee') {
                    $stmtPassagers = $pdo->prepare("SELECT u.email, u.id AS utilisateur_id FROM participations p JOIN utilisateurs u ON p.utilisateur_id = u.id WHERE p.covoiturage_id = :trajet_id");
                    $stmtPassagers->bindValue(':trajet_id', $trajet['covoiturage_id'], PDO::PARAM_INT);
                    $stmtPassagers->execute();
                    $passagers = $stmtPassagers->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($passagers as $passager) {
                        $token = bin2hex(random_bytes(32));

                        $stmtInsert = $pdo->prepare("INSERT INTO validations_trajets (utilisateur_id, covoiturage_id, token, est_valide) VALUES (:utilisateur_id, :covoiturage_id, :token, 0)");
                        $stmtInsert->bindValue(':utilisateur_id', $passager['utilisateur_id'], PDO::PARAM_INT);
                        $stmtInsert->bindValue(':covoiturage_id', $trajet['covoiturage_id'], PDO::PARAM_INT);
                        $stmtInsert->bindValue(':token', $token, PDO::PARAM_STR);
                        $stmtInsert->execute();

                        $lien = "http://localhost/EcoRide/back/public/valider_trajet.php?token=" . $token;
                        $message = "Bonjour,\n\nLe trajet auquel vous avez participé est maintenant terminé.\nVeuillez confirmer votre trajet et laisser un avis en cliquant sur ce lien :\n\n$lien\n\nMerci de votre confiance !";
                        envoyerEmail($passager['email'], "Confirmation de votre trajet EcoRide", $message);
                    }
                }
            ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= htmlspecialchars($trajet['adresse_depart']) ?> → <?= htmlspecialchars($trajet['adresse_arrivee']) ?></strong><br>
                        <?= (new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE))->format(new DateTime($trajet['date_depart'])) ?>
                        à <?= htmlspecialchars($trajet['heure_depart']) ?><br>
                        <span class="badge bg-secondary"><?= libelleStatut($trajet['statut_trajet']) ?></span><br>
                        <small class="text-muted">
                            ⏱ Durée estimée : <?= $heureDepart->diff($heureArrivee)->format('%hh %Im') ?>
                        </small>
                    </div>

                    <?php if ($trajet['statut_trajet'] === 'en_attente' && $now >= $heureDepart): ?>
                        <button class="btn btn-sm btn-primary changer-statut" data-id="<?= $trajet['covoiturage_id'] ?>" data-next="en_cours">Démarrer</button>

                    <?php elseif ($trajet['statut_trajet'] === 'en_cours'): ?>
                        <?php if ($elapsedSeconds >= $seuil50): ?>
                            <button class="btn btn-sm btn-success border-subtle changer-statut" data-id="<?= $trajet['covoiturage_id'] ?>" data-next="arrivee">En cours (cliquez ici pour terminer)</button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-warning" disabled title="Vous pouvez terminer le trajet après au moins 50% de la durée estimée">En cours (fin possible après 50%)</button>
                        <?php endif; ?>

                    <?php else: ?>
                        <span class="text-muted">Statut : <?= libelleStatut($trajet['statut_trajet']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($dateFilter) renderPagination($total, $limit, $page); ?>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.changer-statut').forEach(button => {
    button.addEventListener('click', () => {
        const id = button.dataset.id;
        const nextStatut = button.dataset.next;

        fetch('changer_statut.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                covoiturage_id: id,
                nouveau_statut: nextStatut
            })
        })
        .then(res => res.json())
        .then(data => {
            const messageDiv = document.getElementById('messageStatut');
            if (data.success) {
                messageDiv.style.color = 'green';
                messageDiv.textContent = `Statut mis à jour avec succès : ${nextStatut}`;
                setTimeout(() => window.location.reload(), 1500);
            } else {
                messageDiv.style.color = 'red';
                messageDiv.textContent = data.message || "Une erreur est survenue.";
            }
        })
        .catch(() => {
            const messageDiv = document.getElementById('messageStatut');
            messageDiv.style.color = 'red';
            messageDiv.textContent = "Erreur de communication avec le serveur.";
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
