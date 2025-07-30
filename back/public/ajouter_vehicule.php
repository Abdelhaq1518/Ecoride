<?php
session_start();
require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/../includes/verify_csrf.php';

if (!isset($_SESSION['utilisateur'])) {
    header('Location: connexion.php');
    exit;
}

$userId = $_SESSION['utilisateur']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie();

    // --- Données véhicule ---
    $marque = trim($_POST['marque'] ?? '');
    $modele = trim($_POST['modele'] ?? '');
    $couleur = trim($_POST['couleur'] ?? '');
    $energie = $_POST['energie'] ?? '';
    $immatriculation = trim($_POST['immatriculation'] ?? '');
    $places = (int)($_POST['places'] ?? 0);
    $dateImmat = $_POST['date_immatriculation'] ?? '';

    // --- Données préférences ---
    $fumeur = isset($_POST['fumeur']) ? 1 : 0;
    $animaux = isset($_POST['animaux']) ? 1 : 0;
    $musique = isset($_POST['musique']) ? 1 : 0;
    $discussion = $_POST['discussion'] ?? 'calme';
    $autres = trim($_POST['autres_preferences'] ?? '');

    // Insertion du véhicule
    $stmtVehicule = $pdo->prepare("INSERT INTO voiture (utilisateur_id, marque, modele, couleur, energie, immatriculation, nb_places, date_immatriculation)
                                   VALUES (:uid, :marque, :modele, :couleur, :energie, :immat, :places, :date_immat)");
    $stmtVehicule->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmtVehicule->bindValue(':marque', $marque);
    $stmtVehicule->bindValue(':modele', $modele);
    $stmtVehicule->bindValue(':couleur', $couleur);
    $stmtVehicule->bindValue(':energie', $energie);
    $stmtVehicule->bindValue(':immat', $immatriculation);
    $stmtVehicule->bindValue(':places', $places, PDO::PARAM_INT);
    $stmtVehicule->bindValue(':date_immat', $dateImmat);
    $stmtVehicule->execute();

    // Vérifier si l'utilisateur a déjà des préférences
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM preferences_conducteur WHERE utilisateur_id = :uid");
    $stmtCheck->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmtCheck->execute();
    $exists = $stmtCheck->fetchColumn() > 0;

    if ($exists) {
        // Mise à jour
        $stmtUpdate = $pdo->prepare("UPDATE preferences_conducteur
         SET fumeur = :fumeur, animaux = :animaux, musique = :musique,
        discussion = :discussion, autres_preferences = :autres
        WHERE utilisateur_id = :uid");
    } else {
        // Insertion
        $stmtUpdate = $pdo->prepare("INSERT INTO preferences_conducteur (utilisateur_id, fumeur, animaux, musique, discussion, autres_preferences)
        VALUES (:uid, :fumeur, :animaux, :musique, :discussion, :autres)");
    }

    $stmtUpdate->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmtUpdate->bindValue(':fumeur', $fumeur, PDO::PARAM_INT);
    $stmtUpdate->bindValue(':animaux', $animaux, PDO::PARAM_INT);
    $stmtUpdate->bindValue(':musique', $musique, PDO::PARAM_INT);
    $stmtUpdate->bindValue(':discussion', $discussion);
    $stmtUpdate->bindValue(':autres', $autres);
    $stmtUpdate->execute();

    $_SESSION['success'] = "Véhicule et préférences enregistrés avec succès.";
    header('Location: espace_utilisateur.php#mes-vehicules');
    exit;
}

