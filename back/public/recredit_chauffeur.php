<?php
session_start();

require_once __DIR__ . '/../dev/db.php';            // Connexion MySQL
require_once __DIR__ . '/../dev/vendor/autoload.php'; // Connexion MongoDB (via Composer autoload)
require_once __DIR__ . '/../dev/mailer.php';         // PHPMailer

use MongoDB\Client;

// Vérifier que l’ID du covoiturage est fourni et valide
if (!isset($_POST['covoiturage_id']) || !is_numeric($_POST['covoiturage_id'])) {
    die("ID de covoiturage invalide.");
}

$covoiturageId = (int) $_POST['covoiturage_id'];

// Connexion MongoDB et récupération du litige pour vérifier statut
$client = new Client();
$collection = $client->ecoride->litiges;

$litige = $collection->findOne(['covoiturage_id' => $covoiturageId]);

if ($litige && isset($litige['statut']) && $litige['statut'] === 'resolu') {
    die("Le litige a déjà été résolu, aucun crédit supplémentaire possible.");
}

// Étape 1 : Récupérer les données du covoiturage et nombre de participants
$sql = "
    SELECT 
        covoiturages.covoiturage_id,
        covoiturages.createur_id,
        covoiturages.cout_credits,
        u1.nom AS nom_chauffeur,
        u1.email AS email_chauffeur,
        COUNT(participations.utilisateur_id) AS nombre_participants
    FROM covoiturages
    JOIN utilisateurs u1 ON covoiturages.createur_id = u1.id
    LEFT JOIN participations ON participations.covoiturage_id = covoiturages.covoiturage_id
    WHERE covoiturages.covoiturage_id = :id
    GROUP BY covoiturages.covoiturage_id
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $covoiturageId, PDO::PARAM_INT);
$stmt->execute();
$donnees = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donnees) {
    die("Covoiturage introuvable.");
}

// Extraire les infos nécessaires
$createurId = $donnees['createur_id'];
$coutCredits = $donnees['cout_credits'];
$nombreParticipants = $donnees['nombre_participants'];

if ($nombreParticipants == 0) {
    die("Aucun participant. Crédit annulé.");
}

// Étape 2 : Calcul du montant à recréditer
$montantRecredit = $coutCredits / $nombreParticipants;

// Étape 3 : Créditer le chauffeur
$update = $pdo->prepare("
    UPDATE utilisateurs 
    SET credits = credits + :montant 
    WHERE id = :createur_id
");
$update->bindValue(':montant', $montantRecredit, PDO::PARAM_STR);
$update->bindValue(':createur_id', $createurId, PDO::PARAM_INT);
$update->execute();

// Étape 4 : Marquer le litige comme résolu dans MongoDB
try {
    $collection->updateOne(
        ['covoiturage_id' => $covoiturageId],
        ['$set' => ['statut' => 'resolu']]
    );
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erreur MongoDB : " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Étape 5 : Envoyer un email de confirmation de règlement du litige
$emailChauffeur = $donnees['email_chauffeur'];
$nomChauffeur = $donnees['nom_chauffeur'];

$sujet = "Règlement du litige – Recrédit effectué";
$message = "
Bonjour $nomChauffeur,

Le litige concernant le covoiturage n°$covoiturageId a été résolu. 
Un recrédit de $montantRecredit crédits a été effectué sur votre compte EcoRide.

Merci pour votre compréhension.

L’équipe EcoRide
";

envoyerEmail($emailChauffeur, $sujet, $message, true); // true = envoi depuis covoitest7+gestion

// Étape 6 : Redirection vers la fiche litige avec succès
header("Location: details_litige.php?id=$covoiturageId&success=1");
exit;
