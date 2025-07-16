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
$nouveauStatut = $_POST['nouveau_statut'] ?? null;

if (!$trajetId || !$nouveauStatut) {
    echo json_encode(['success' => false, 'message' => 'Données incomplètes.']);
    exit;
}

// Vérifie que l'utilisateur est le créateur du trajet
$stmt = $pdo->prepare("SELECT * FROM covoiturages WHERE covoiturage_id = :id AND createur_id = :user_id");
$stmt->execute([':id' => $trajetId, ':user_id' => $userId]);
$trajet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trajet) {
    echo json_encode(['success' => false, 'message' => 'Trajet introuvable ou non autorisé.']);
    exit;
}

$statutActuel = $trajet['statut_trajet'];
$transitions = [
    'en_attente' => ['en_cours'],
    'en_cours' => ['arrivee'],
    'arrivee' => []
];

if (!isset($transitions[$statutActuel]) || !in_array($nouveauStatut, $transitions[$statutActuel])) {
    echo json_encode(['success' => false, 'message' => 'Transition interdite.']);
    exit;
}

// Vérifie que 50% du temps est écoulé avant "arrivee"
if ($nouveauStatut === 'arrivee') {
    $depart = new DateTime($trajet['date_depart'] . ' ' . $trajet['heure_depart']);
    $arrivee = new DateTime($trajet['date_depart'] . ' ' . $trajet['heure_arrivee']);
    $now = new DateTime();

    $duree = $arrivee->getTimestamp() - $depart->getTimestamp();
    $ecoule = $now->getTimestamp() - $depart->getTimestamp();

    if ($ecoule < 0.5 * $duree) {
        echo json_encode(['success' => false, 'message' => 'Moins de 50% du trajet écoulé.']);
        exit;
    }
}

// Mise à jour du statut
$update = $pdo->prepare("UPDATE covoiturages SET statut_trajet = :statut WHERE covoiturage_id = :id");
$update->execute([':statut' => $nouveauStatut, ':id' => $trajetId]);

// Envoi du mail si statut = "arrivee"
if ($nouveauStatut === 'arrivee') {
    $stmtParticipants = $pdo->prepare("
        SELECT u.email, u.pseudo, u.id AS utilisateur_id
        FROM participations p
        JOIN utilisateurs u ON u.id = p.utilisateur_id
        WHERE p.covoiturage_id = :id
    ");
    $stmtParticipants->execute([':id' => $trajetId]);
    $participants = $stmtParticipants->fetchAll(PDO::FETCH_ASSOC);

    foreach ($participants as $participant) {
        $email = $participant['email'];
        $pseudo = $participant['pseudo'];
        $utilisateurId = $participant['utilisateur_id'];

        // Génération token unique
        $token = bin2hex(random_bytes(32));

        // Insertion du token en base
        $stmtInsert = $pdo->prepare("
            INSERT INTO validations_trajets (utilisateur_id, covoiturage_id, token, est_valide)
            VALUES (:utilisateur_id, :covoiturage_id, :token, 0)
        ");
        $stmtInsert->bindValue(':utilisateur_id', $utilisateurId, PDO::PARAM_INT);
        $stmtInsert->bindValue(':covoiturage_id', $trajetId, PDO::PARAM_INT);
        $stmtInsert->bindValue(':token', $token, PDO::PARAM_STR);
        $stmtInsert->execute();

        // Création du lien avec token
        $lienValidation = "http://localhost/ecoride/back/public/valider_trajet.php?token=" . urlencode($token);

        $sujet = "EcoRide – Confirmation d'arrivée du trajet";
        $message = "Bonjour $pseudo,\n\n" .
            "Le trajet auquel vous avez participé est arrivé à destination.\n" .
            "Merci de bien vouloir valider votre trajet en cliquant ici :\n" .
            "$lienValidation\n\n" .
            "Cela permettra de finaliser le trajet et de créditer le chauffeur.\n\n" .
            "Merci pour votre confiance,\nL’équipe EcoRide.";

        envoyerEmail($email, $sujet, $message);
    }
}

echo json_encode(['success' => true, 'message' => "Statut mis à jour."]);
