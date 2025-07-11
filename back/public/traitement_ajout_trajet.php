<?php
session_start();
require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/../includes/csrf_token.php';

if (!isset($_SESSION['utilisateur'])) {
    header('Location: connexion.php');
    exit;
}

$userId = $_SESSION['utilisateur']['id'];
validateCsrfOrDie();

function redirectWithError(string $msg, array $oldData = []) {
    $_SESSION['erreur'] = $msg;
    $_SESSION['old'] = $oldData;
    header('Location: ajout_trajet.php');
    exit;
}

// Récupération et nettoyage des données POST
$dateDepart = $_POST['date_depart'] ?? '';
$heureDepart = $_POST['heure_depart'] ?? '';
$heureArrivee = $_POST['heure_arrivee'] ?? '';
$lieuDepart = trim($_POST['lieu_depart'] ?? '');
$lieuArrivee = trim($_POST['lieu_arrivee'] ?? '');
$places = (int)($_POST['places_disponibles'] ?? 0);
$credits = (int)($_POST['cout_credits'] ?? 0);
$choixVehicule = $_POST['choix_vehicule'] ?? '';
$vehiculeId = null;

$oldData = [
    'date_depart' => $dateDepart,
    'heure_depart' => $heureDepart,
    'heure_arrivee' => $heureArrivee,
    'lieu_depart' => $lieuDepart,
    'lieu_arrivee' => $lieuArrivee,
    'places_disponibles' => $places,
    'cout_credits' => $credits,
    'choix_vehicule' => $choixVehicule,
    'vehicule_id' => $_POST['vehicule_id'] ?? '',
    'vehicule_libre_marque' => $_POST['vehicule_libre_marque'] ?? '',
    'vehicule_libre_modele' => $_POST['vehicule_libre_modele'] ?? '',
    'vehicule_libre_couleur' => $_POST['vehicule_libre_couleur'] ?? '',
    'vehicule_libre_places' => $_POST['vehicule_libre_places'] ?? '',
    'vehicule_libre_energie' => $_POST['vehicule_libre_energie'] ?? '',
    'vehicule_libre_date_immatriculation' => $_POST['vehicule_libre_date_immatriculation'] ?? '',
];

// Validation
if (!$dateDepart || !$heureDepart || !$heureArrivee || !$lieuDepart || !$lieuArrivee || $places < 1 || $credits < 1) {
    redirectWithError("Tous les champs obligatoires doivent être remplis.", $oldData);
}

// Heures valides
function isValid30MinInterval($time): bool {
    return preg_match('/^([01]\d|2[0-3]):(00|30)$/', $time) === 1;
}
if (!isValid30MinInterval($heureDepart) || !isValid30MinInterval($heureArrivee)) {
    redirectWithError("Les heures doivent être saisies par tranche de 30 minutes (ex: 11:00 ou 11:30).", $oldData);
}
if (strtotime($heureArrivee) < strtotime($heureDepart)) {
    redirectWithError("L'heure d'arrivée doit être égale ou postérieure à l'heure de départ.", $oldData);
}

// Vérification crédits
$stmtCredits = $pdo->prepare("SELECT credits FROM utilisateurs WHERE id = :id FOR UPDATE");
$stmtCredits->bindValue(':id', $userId, PDO::PARAM_INT);
$stmtCredits->execute();
$chauffeur = $stmtCredits->fetch(PDO::FETCH_ASSOC);
if (!$chauffeur || $chauffeur['credits'] < 2) {
    redirectWithError("Crédits insuffisants pour créer un trajet. 2 crédits sont requis.", $oldData);
}

// Traitement véhicule
if ($choixVehicule === 'existant') {
    $vehiculeId = $_POST['vehicule_id'] ?? null;
    if (!$vehiculeId || !is_numeric($vehiculeId)) {
        redirectWithError("Veuillez sélectionner un véhicule existant valide.", $oldData);
    }

    $stmtVeh = $pdo->prepare("SELECT couleur, date_immatriculation FROM voiture WHERE voiture_id = :vid AND utilisateur_id = :uid");
    $stmtVeh->bindValue(':vid', $vehiculeId, PDO::PARAM_INT);
    $stmtVeh->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmtVeh->execute();
    $vehicule = $stmtVeh->fetch(PDO::FETCH_ASSOC);
    if (!$vehicule) {
        redirectWithError("Véhicule existant non trouvé ou non autorisé.", $oldData);
    }

} elseif ($choixVehicule === 'libre') {
    $marque = trim($_POST['vehicule_libre_marque'] ?? '');
    $modele = trim($_POST['vehicule_libre_modele'] ?? '');
    $couleur = trim($_POST['vehicule_libre_couleur'] ?? '');
    $placesVehicule = (int)($_POST['vehicule_libre_places'] ?? 0);
    $energie = trim($_POST['vehicule_libre_energie'] ?? '');
    $dateImmatriculation = $_POST['vehicule_libre_date_immatriculation'] ?? '';

    if (!$marque || !$modele || !$couleur || $placesVehicule < 1 || !$energie || !$dateImmatriculation) {
        redirectWithError("Informations du véhicule ponctuel incomplètes.", $oldData);
    }

    // ✅ immatriculation factice (max 12 caractères)
    $immatriculation = 'LIB-' . strtoupper(substr(uniqid(), -8));

    $stmtInsertVehicule = $pdo->prepare("INSERT INTO voiture (utilisateur_id, marque, modele, couleur, nb_places, energie, date_immatriculation, immatriculation) VALUES (:uid, :marque, :modele, :couleur, :places, :energie, :date_immat, :immat)");
    $stmtInsertVehicule->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmtInsertVehicule->bindValue(':marque', $marque);
    $stmtInsertVehicule->bindValue(':modele', $modele);
    $stmtInsertVehicule->bindValue(':couleur', $couleur);
    $stmtInsertVehicule->bindValue(':places', $placesVehicule, PDO::PARAM_INT);
    $stmtInsertVehicule->bindValue(':energie', $energie);
    $stmtInsertVehicule->bindValue(':date_immat', $dateImmatriculation);
    $stmtInsertVehicule->bindValue(':immat', $immatriculation);
    $stmtInsertVehicule->execute();

    $vehiculeId = $pdo->lastInsertId();

} else {
    redirectWithError("Type de véhicule invalide.", $oldData);
}

// Insertion du trajet
$stmtTrajet = $pdo->prepare("
    INSERT INTO covoiturages 
    (createur_id, vehicule_id, adresse_depart, adresse_arrivee, date_depart, heure_depart, heure_arrivee, places_disponibles, cout_credits, type_trajet)
    VALUES 
    (:createur, :vehicule, :depart, :arrivee, :date, :heure_depart, :heure_arrivee, :places, :credits, 'ecologique')
");

$stmtTrajet->bindValue(':createur', $userId, PDO::PARAM_INT);
$stmtTrajet->bindValue(':vehicule', $vehiculeId, PDO::PARAM_INT);
$stmtTrajet->bindValue(':depart', $lieuDepart);
$stmtTrajet->bindValue(':arrivee', $lieuArrivee);
$stmtTrajet->bindValue(':date', $dateDepart);
$stmtTrajet->bindValue(':heure_depart', $heureDepart);
$stmtTrajet->bindValue(':heure_arrivee', $heureArrivee);
$stmtTrajet->bindValue(':places', $places, PDO::PARAM_INT);
$stmtTrajet->bindValue(':credits', $credits, PDO::PARAM_INT);
$stmtTrajet->execute();

// Déduction des crédits
$stmtDebit = $pdo->prepare("UPDATE utilisateurs SET credits = credits - 2 WHERE id = :id");
$stmtDebit->bindValue(':id', $userId, PDO::PARAM_INT);
$stmtDebit->execute();

$_SESSION['success'] = "Trajet créé avec succès. 2 crédits ont été déduits pour les frais EcoRide.";
header('Location: ajout_trajet.php');
exit;
