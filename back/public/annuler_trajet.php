<?php
session_start();
require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/../dev/mailer.php';

header('Content-Type: application/json');

if (!isset($_SESSION['utilisateur']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit;
}

$userId = $_SESSION['utilisateur']['id'];
$trajetId = $_POST['covoiturage_id'] ?? null;

if (!$trajetId) {
    echo json_encode(['success' => false, 'message' => 'ID du trajet manquant.']);
    exit;
}

// Vérifie que l'utilisateur est bien le créateur du trajet
$stmt = $pdo->prepare("SELECT * FROM covoiturages WHERE covoiturage_id = :id AND createur_id = :user_id");
$stmt->bindValue(':id', $trajetId, PDO::PARAM_INT);
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();

$trajet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trajet) {
    echo json_encode(['success' => false, 'message' => 'Trajet introuvable ou non autorisé.']);
    exit;
}

// Met à jour le statut du trajet
$stmtUpdate = $pdo->prepare("UPDATE covoiturages SET statut = 'annulé' WHERE covoiturage_id = :id");
$stmtUpdate->bindValue(':id', $trajetId, PDO::PARAM_INT);
if (!$stmtUpdate->execute()) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'annulation.']);
    exit;
}

// Récupère le coût en crédits
$cout = (int) $trajet['cout_credits'];

// Récupère les participants avec leur e-mail et pseudo
$stmtParticipants = $pdo->prepare("
    SELECT u.id, u.email, u.pseudo
    FROM participations p
    JOIN utilisateurs u ON u.id = p.utilisateur_id
    WHERE p.covoiturage_id = :id
");
$stmtParticipants->bindValue(':id', $trajetId, PDO::PARAM_INT);
$stmtParticipants->execute();
$participants = $stmtParticipants->fetchAll(PDO::FETCH_ASSOC);

// Prépare la requête de remboursement
$stmtRembourse = $pdo->prepare("
    UPDATE utilisateurs
    SET credits = credits + :remboursement
    WHERE id = :id
");

foreach ($participants as $participant) {
    $participantId = $participant['id'];
    $email = $participant['email'];
    $pseudo = $participant['pseudo'];

    if ($participantId != $userId) {
        // Remboursement
        $stmtRembourse->bindValue(':remboursement', $cout, PDO::PARAM_INT);
        $stmtRembourse->bindValue(':id', $participantId, PDO::PARAM_INT);
        $stmtRembourse->execute();

        // Mail passager
        $sujet = "EcoRide – Annulation d’un trajet";
        $message = "Bonjour $pseudo,\n\n" .
            "Nous vous informons que le trajet prévu le " .
            date('d/m/Y à H:i', strtotime($trajet['date_depart'] . ' ' . $trajet['heure_depart'])) .
            " a été annulé par le chauffeur.\n\n" .
            "Vous avez été remboursé de $cout crédits.\n\nMerci pour votre compréhension.\n\nL’équipe EcoRide.";
        envoyerEmail($email, $sujet, $message);

    } else {
        // Mail chauffeur
        $sujet = "EcoRide – Confirmation d'annulation";
        $message = "Bonjour $pseudo,\n\n" .
            "Vous avez annulé le trajet prévu le " .
            date('d/m/Y à H:i', strtotime($trajet['date_depart'] . ' ' . $trajet['heure_depart'])) . ".\n\n" .
            "Les participants ont été automatiquement remboursés.\n\nMerci de prévenir à l'avance si possible.\n\nL’équipe EcoRide.";
        envoyerEmail($email, $sujet, $message);
    }
}

echo json_encode([
    'success' => true,
    'message' => "Trajet annulé. Les participants ont été remboursés et informés par mail."
]);
