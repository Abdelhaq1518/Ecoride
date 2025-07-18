<?php
session_start();
require_once __DIR__ . '/../dev/vendor/autoload.php';
require_once __DIR__ . '/../includes/verify_csrf.php';

use MongoDB\Client;
use MongoDB\BSON\ObjectId;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée');
}

validateCsrfOrDie();

$mongo = new Client("mongodb://localhost:27017");
$collection = $mongo->ecoride->avis_covoiturage;

$id = $_POST['id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$id || !in_array($action, ['valider', 'refuser'])) {
    $_SESSION['error'] = 'Données invalides.';
    header('Location: espace_employe.php');
    exit;
}

try {
    $objectId = new ObjectId($id);

    if ($action === 'valider') {
        $collection->updateOne(
            ['_id' => $objectId],
            ['$set' => [
                'est_valide' => true,
                'statut' => 'valide' // 💡 MàJ du statut
            ]]
        );
        $_SESSION['success'] = "Avis validé avec succès.";
    } else {
        $collection->deleteOne(['_id' => $objectId]);
        $_SESSION['success'] = "Avis refusé et supprimé.";
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Erreur lors de la mise à jour de l'avis : " . $e->getMessage();
}

header('Location: espace_employe.php');
exit;
