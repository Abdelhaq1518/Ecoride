<?php
session_start();
require_once __DIR__ . '/../dev/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\ObjectId;

if (!isset($_POST['id'], $_POST['action'])) {
    $_SESSION['error'] = "Requête invalide.";
    header('Location: liste_litiges.php');
    exit;
}

$id = $_POST['id'];
$action = $_POST['action'];

try {
    $mongo = new Client("mongodb://localhost:27017");
    $collection = $mongo->ecoride->litiges;

    if (!ObjectId::isValid($id)) {
        $_SESSION['error'] = "ID invalide.";
        header('Location: liste_litiges.php');
        exit;
    }

    $objectId = new ObjectId($id);

    if ($action === 'resoudre') {
        $result = $collection->updateOne(
            ['_id' => $objectId],
            ['$set' => ['statut' => 'resolu']]
        );

        if ($result->getModifiedCount() > 0) {
            $_SESSION['success'] = "Le litige a été résolu.";
        } else {
            $_SESSION['error'] = "Aucune modification effectuée.";
        }
    } else {
        $_SESSION['error'] = "Action non reconnue.";
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
}

header('Location: liste_litiges.php');
exit;
