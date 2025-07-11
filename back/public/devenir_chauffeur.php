<?php
session_start();
require_once __DIR__ . '/../includes/ini.php';
require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/../includes/verify_csrf.php';

if (!isset($_SESSION['utilisateur'])) {
    header('Location: connexion.php');
    exit;
}

validateCsrfOrDie();

$userId = $_SESSION['utilisateur']['id'];

// Récupère les rôles actuels
$stmt = $pdo->prepare("SELECT role_id FROM utilisateur_roles WHERE utilisateur_id = :id");
$stmt->bindValue(':id', $userId, PDO::PARAM_INT);
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Récupère l'ID du rôle 'chauffeur'
$stmtRole = $pdo->prepare("SELECT role_id FROM roles WHERE libelle = 'chauffeur' LIMIT 1");
$stmtRole->execute();
$chauffeurId = $stmtRole->fetchColumn();

if (!$chauffeurId) {
    $_SESSION['erreur'] = "Le rôle 'chauffeur' est introuvable.";
    header('Location: espace_utilisateur.php');
    exit;
}

// Ajoute le rôle si non déjà présent
if (!in_array($chauffeurId, $roles)) {
    $stmtAdd = $pdo->prepare("INSERT INTO utilisateur_roles (utilisateur_id, role_id) VALUES (:id, :role)");
    $stmtAdd->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmtAdd->bindValue(':role', $chauffeurId, PDO::PARAM_INT);
    $stmtAdd->execute();

    $_SESSION['success'] = "Vous êtes maintenant chauffeur.";
} else {
    $_SESSION['success'] = "Vous êtes déjà chauffeur.";
}

header('Location: espace_utilisateur.php');
exit;
