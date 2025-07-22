<?php
session_start();
require_once __DIR__ . '/../dev/db.php';

header('Content-Type: application/json');

if (!isset($_POST['covoiturage_id'], $_POST['nouveau_statut'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants.']);
    exit;
}

$covoiturageId = (int) $_POST['covoiturage_id'];
$nouveauStatut = $_POST['nouveau_statut'];

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié.']);
    exit;
}

$utilisateurId = $_SESSION['utilisateur']['id'];

// Vérifie que le covoiturage appartient bien à l'utilisateur connecté
$stmtCheck = $pdo->prepare("SELECT * FROM covoiturages WHERE covoiturage_id = :id AND createur_id = :utilisateur_id");
$stmtCheck->bindValue(':id', $covoiturageId, PDO::PARAM_INT);
$stmtCheck->bindValue(':utilisateur_id', $utilisateurId, PDO::PARAM_INT);
$stmtCheck->execute();
$covoiturage = $stmtCheck->fetch();

if (!$covoiturage) {
    echo json_encode(['success' => false, 'message' => 'Covoiturage non trouvé ou non autorisé.']);
    exit;
}

// Liste des statuts autorisés (à adapter selon ta définition ENUM)
$statutsAutorises = ['en_attente', 'en_cours', 'arrivee', 'litige'];

if (!in_array($nouveauStatut, $statutsAutorises, true)) {
    echo json_encode(['success' => false, 'message' => 'Statut invalide.']);
    exit;
}

// Mise à jour du statut du trajet dans la colonne statut_trajet (pas statut_id)
$stmtUpdate = $pdo->prepare("UPDATE covoiturages SET statut_trajet = :statut WHERE covoiturage_id = :id");
$stmtUpdate->bindValue(':statut', $nouveauStatut, PDO::PARAM_STR);
$stmtUpdate->bindValue(':id', $covoiturageId, PDO::PARAM_INT);

if ($stmtUpdate->execute()) {
    echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour.']);
}
