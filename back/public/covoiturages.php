<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/verif_doublons.php';

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function traduireDateFr(DateTime $date) {
    $jours = ['Monday'=>'lundi','Tuesday'=>'mardi','Wednesday'=>'mercredi','Thursday'=>'jeudi','Friday'=>'vendredi','Saturday'=>'samedi','Sunday'=>'dimanche'];
    $mois = ['January'=>'janvier','February'=>'février','March'=>'mars','April'=>'avril','May'=>'mai','June'=>'juin','July'=>'juillet','August'=>'août','September'=>'septembre','October'=>'octobre','November'=>'novembre','December'=>'décembre'];

    return $jours[$date->format('l')] . ' ' . $date->format('d') . ' ' . $mois[$date->format('F')] . ' ' . $date->format('Y');
}

function rechercherTrajets($pdo, $depart, $arrivee, $date, $prix_max = null, $ecolo = null, $note_min = null, $duree_max = null) {
    $sql = "
        SELECT c.*, u.nom, u.prenom, u.pseudo, u.photo, u.note_moyenne as note_moyenne_conducteur
        FROM covoiturages c
        JOIN utilisateurs u ON u.id = c.createur_id
        WHERE c.adresse_depart LIKE :depart
        AND c.adresse_arrivee LIKE :arrivee
        AND c.date_depart = :date
        AND c.places_disponibles > 0
    ";

    $params = [
        'depart' => "%$depart%",
        'arrivee' => "%$arrivee%",
        'date' => $date
    ];

    if ($prix_max !== null) {
        $sql .= " AND c.cout_credits <= :prix_max";
        $params['prix_max'] = $prix_max;
    }

    if ($ecolo !== null && ($ecolo === 'ecologique' || $ecolo === 'standard')) {
        $sql .= " AND c.type_trajet = :type_trajet";
        $params['type_trajet'] = $ecolo;
    }

    if ($note_min !== null) {
        $sql .= " AND u.note_moyenne >= :note_min";
        $params['note_min'] = $note_min;
    }

    if ($duree_max !== null) {
        $sql .= " AND TIMESTAMPDIFF(HOUR, c.heure_depart, c.heure_arrivee) <= :duree_max";
        $params['duree_max'] = $duree_max;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$depart = $_POST['depart'] ?? '';
$arrivee = $_POST['arrivee'] ?? '';
$date = $_POST['date'] ?? '';
$prix_max = isset($_POST['prix_max']) && is_numeric($_POST['prix_max']) ? $_POST['prix_max'] : null;
$ecolo = $_POST['ecolo'] ?? null;
$note_min = isset($_POST['note_min']) && is_numeric($_POST['note_min']) ? $_POST['note_min'] : null;
$duree_max = isset($_POST['duree_max']) && is_numeric($_POST['duree_max']) ? $_POST['duree_max'] : null;

if (empty($depart) || empty($arrivee) || empty($date)) {
    header("Location: index.php");
    exit;
}

$trajets = rechercherTrajets($pdo, $depart, $arrivee, $date, $prix_max, $ecolo, $note_min, $duree_max);
$trajets = verifier_doublons($trajets);
$alternative = false;
$note = null;

if (empty($trajets)) {
    $originalDate = new DateTime($date);
    for ($offset = 1; $offset <= 15; $offset++) {
        foreach ([-1, 1] as $direction) {
            $newDate = (clone $originalDate)->modify(($direction * $offset) . ' days');
            $dateAlt = $newDate->format('Y-m-d');

            $trajetsAlt = rechercherTrajets($pdo, $depart, $arrivee, $dateAlt, $prix_max, $ecolo, $note_min, $duree_max);
            if (!empty($trajetsAlt)) {
                $trajets = verifier_doublons($trajetsAlt);
                $alternative = true;
                $note = "Aucun trajet disponible à la date choisie. Voici une alternative le " . traduireDateFr($newDate) . ".";
                break 2;
            }
        }
    }
}

include '../includes/header.php';
?>
<main class="container py-5">
    <h3 class="mb-4">Vos covoiturages</h3>

    <!-- Filtres -->
    <div class="filters-form-wrapper">
        <form method="post" class="filters-form d-flex flex-wrap">
            <input type="hidden" name="depart" value="<?= h($depart) ?>">
            <input type="hidden" name="arrivee" value="<?= h($arrivee) ?>">
            <input type="hidden" name="date" value="<?= h($date) ?>">

            <div class="mb-3 me-3">
                <label class="form-label" for="filter-ecologique">Type :</label>
                <select class="form-select" name="ecolo" id="filter-ecologique">
                    <option value="">Tous</option>
                    <option value="ecologique" <?= $ecolo === 'ecologique' ? 'selected' : '' ?>>Écologique</option>
                    <option value="standard" <?= $ecolo === 'standard' ? 'selected' : '' ?>>Standard</option>
                </select>
            </div>

            <div class="mb-3 me-3">
                <label class="form-label" for="filter-prix">Prix max :</label>
                <input type="number" class="form-control" name="prix_max" id="filter-prix" value="<?= h($prix_max) ?>">
            </div>

            <div class="mb-3 me-3">
                <label class="form-label" for="filter-duree">Durée max (h) :</label>
                <input type="number" class="form-control" name="duree_max" id="filter-duree" value="<?= h($duree_max) ?>">
            </div>

            <div class="mb-3 me-3">
                <label class="form-label" for="filter-note">Note min :</label>
                <input type="number" class="form-control" step="0.1" name="note_min" id="filter-note" value="<?= h($note_min) ?>">
            </div>

            <button type="submit" class="btn custom-rechercher align-self-end">Rechercher</button>
        </form>
    </div>

    <!-- Résultats -->
    <div class="flex-grow-1">
        <?php if (!empty($note)): ?>
            <div class="alert alert-info mb-4"><?= h($note) ?></div>
        <?php endif; ?>

        <?php if (!empty($trajets)): ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php foreach ($trajets as $trajet): ?>
                    <div class="col">
                        <div class="card trajet-result">
                            <div class="card-body">
                                <h5 class="card-title fw-bold"><?= h($trajet['prenom']) ?> <?= h($trajet['nom']) ?></h5>
                                <p class="card-text"><strong>Date :</strong> <?= date('d/m/Y', strtotime($trajet['date_depart'])) ?></p>
                                <p class="card-text"><strong>Départ :</strong> <?= h($trajet['adresse_depart']) ?> à <?= substr(h($trajet['heure_depart']), 0, 5) ?></p>
                                <p class="card-text"><strong>Arrivée estimée à</strong> <?= substr(h($trajet['heure_arrivee']), 0, 5) ?> - <?= h($trajet['adresse_arrivee']) ?></p>

                                <div class="badges-info mb-3">
                                    <span class="badge type-trajet"><?= ucfirst(h($trajet['type_trajet'])) ?></span>
                                    <span class="badge badge-places"><?= h($trajet['places_disponibles']) ?> places</span>
                                    <span class="badge badge-credits"><?= h($trajet['cout_credits']) ?> crédits</span>
                                </div>

                                <div class="d-flex align-items-center gap-3">
                                    <img src="assets/img/<?= h($trajet['photo'] ?? 'default-user.webp') ?>" alt="Photo de profil" class="rounded-circle" width="60" height="60">
                                    <div>
                                        <div class="fw-bold">@<?= h($trajet['pseudo']) ?></div>
                                        <div class="text-muted">Note : <?= h($trajet['note_moyenne_conducteur']) ?>/5</div>
                                    </div>
                                    <a href="details_covoiturages.php?id=<?= h($trajet['covoiturage_id']) ?>" class="details-btn ms-auto">Détails</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Aucun trajet trouvé.</p>
        <?php endif; ?>
    </div>
</main>


<?php include '../includes/footer.php'; ?>
