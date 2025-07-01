

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/verif_doublons.php';

header('Content-Type: application/json');

$depart = $_GET['depart'] ?? '';
$arrivee = $_GET['arrivee'] ?? '';
$date = $_GET['date'] ?? '';

$prix_max = isset($_GET['prix_max']) && is_numeric($_GET['prix_max']) ? $_GET['prix_max'] : null;
$ecolo = $_GET['ecolo'] ?? null;
$note_min = isset($_GET['note_min']) && is_numeric($_GET['note_min']) ? $_GET['note_min'] : null;
$duree_max = isset($_GET['duree_max']) && is_numeric($_GET['duree_max']) ? $_GET['duree_max'] : null;

if (empty($depart) || empty($arrivee) || empty($date)) {
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

function rechercherTrajets($pdo, $depart, $arrivee, $date, $prix_max = null, $ecolo = null, $note_min = null, $duree_max = null) {
    $sql = "
        SELECT c.*, u.pseudo, u.photo, u.note_moyenne as note_moyenne_conducteur
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

    if ($ecolo !== null && ($ecolo === '1' || $ecolo === '0')) {
        $sql .= " AND c.type_trajet = :type_trajet";
        $params['type_trajet'] = $ecolo === '1' ? 'ecologique' : 'standard';
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

function traduireDateFr(DateTime $date) {
    $jours = ['Monday'=>'lundi','Tuesday'=>'mardi','Wednesday'=>'mercredi','Thursday'=>'jeudi','Friday'=>'vendredi','Saturday'=>'samedi','Sunday'=>'dimanche'];
    $mois = ['January'=>'janvier','February'=>'février','March'=>'mars','April'=>'avril','May'=>'mai','June'=>'juin','July'=>'juillet','August'=>'août','September'=>'septembre','October'=>'octobre','November'=>'novembre','December'=>'décembre'];

    return $jours[$date->format('l')] . ' ' . $date->format('d') . ' ' . $mois[$date->format('F')] . ' ' . $date->format('Y');
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

echo json_encode([
    'trajets' => $trajets,
    'alternative' => $alternative,
    'note' => $note
]);
