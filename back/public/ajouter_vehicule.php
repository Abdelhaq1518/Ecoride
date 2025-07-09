<?php
session_start();
require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/../includes/verify_csrf.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['erreur'] = "Vous devez être connecté pour ajouter un véhicule.";
    header('Location: connexion.php');
    exit;
}

// Vérification méthode et CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['erreur'] = "Requête non autorisée.";
    header('Location: espace_utilisateur.php');
    exit;
}
validateCsrfOrDie();

// Récupération et validation des données
$marque          = trim($_POST['marque'] ?? '');
$modele          = trim($_POST['modele'] ?? '');
$couleur         = trim($_POST['couleur'] ?? '');
$energie         = trim($_POST['energie'] ?? '');
$immatriculation = trim($_POST['immatriculation'] ?? '');
$nb_places       = intval($_POST['places'] ?? 0);
$date_immat      = $_POST['date_immatriculation'] ?? null;

if (!$marque || !$modele || !$energie || !$immatriculation || $nb_places <= 0 || !$date_immat) {
    $_SESSION['erreur'] = "Tous les champs obligatoires doivent être remplis correctement.";
    header('Location: espace_utilisateur.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO voiture (
            utilisateur_id, 
            marque, 
            modele, 
            couleur, 
            energie, 
            immatriculation, 
            nb_places, 
            date_immatriculation
        ) VALUES (
            :uid, 
            :marque, 
            :modele, 
            :couleur, 
            :energie, 
            :immatriculation, 
            :nb_places, 
            :date
        )
    ");

    $stmt->bindValue(':uid', $_SESSION['utilisateur']['id'], PDO::PARAM_INT);
    $stmt->bindValue(':marque', $marque, PDO::PARAM_STR);
    $stmt->bindValue(':modele', $modele, PDO::PARAM_STR);
    $stmt->bindValue(':couleur', $couleur, PDO::PARAM_STR);
    $stmt->bindValue(':energie', $energie, PDO::PARAM_STR);
    $stmt->bindValue(':immatriculation', $immatriculation, PDO::PARAM_STR);
    $stmt->bindValue(':nb_places', $nb_places, PDO::PARAM_INT);
    $stmt->bindValue(':date', $date_immat, PDO::PARAM_STR);

    $stmt->execute();

    $_SESSION['success'] = "Véhicule ajouté avec succès.";
    header('Location: espace_utilisateur.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['erreur'] = "Erreur lors de l'ajout du véhicule : " . $e->getMessage();
    header('Location: espace_utilisateur.php');
    exit;
}
