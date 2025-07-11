<?php
session_start();
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

// Récupère l'ID du rôle 'passager'
$stmtRole = $pdo->prepare("SELECT role_id FROM roles WHERE libelle = 'passager' LIMIT 1");
$stmtRole->execute();
$passagerId = $stmtRole->fetchColumn();

if (!$passagerId) {
    $_SESSION['erreur'] = "Le rôle 'passager' est introuvable.";
    header('Location: espace_utilisateur.php');
    exit;
}

// Ajoute le rôle si non déjà présent
if (!in_array($passagerId, $roles)) {
    $stmtAdd = $pdo->prepare("INSERT INTO utilisateur_roles (utilisateur_id, role_id) VALUES (:id, :role)");
    $stmtAdd->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmtAdd->bindValue(':role', $passagerId, PDO::PARAM_INT);
    $stmtAdd->execute();

    $_SESSION['success'] = "Vous êtes maintenant passager.";
} else {
    $_SESSION['success'] = "Vous êtes déjà passager.";
}

header('Location: espace_utilisateur.php');
exit;
