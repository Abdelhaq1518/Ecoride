<?php
session_start();
require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/../includes/verify_csrf.php';
require_once __DIR__ . '/../dev/vendor/autoload.php'; 

use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;

$mongo = new Client("mongodb://localhost:27017");
$collection = $mongo->ecoride->avis_covoiturage;

$token = $_GET['token'] ?? '';
$erreur = '';
$validation = null;

// Vérification du token
$stmt = $pdo->prepare("SELECT * FROM validations_trajets WHERE token = :token");
$stmt->bindValue(':token', $token);
$stmt->execute();
$validation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$validation) {
    $erreur = "Lien invalide ou expiré.";
} elseif ($validation['est_valide']) {
    $erreur = "Ce trajet a déjà été confirmé.";
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validation) {
    validateCsrfOrDie();

    $est_valide = isset($_POST['validation']) && $_POST['validation'] === 'ok' ? 1 : 0;
    $commentaire = trim($_POST['commentaire'] ?? '');
    $note = isset($_POST['note']) ? (int)$_POST['note'] : null;

    $stmt = $pdo->prepare("
        UPDATE validations_trajets 
        SET est_valide = :valide, commentaire = :commentaire, date_validation = NOW() 
        WHERE id = :id
    ");
    $stmt->bindValue(':valide', $est_valide, PDO::PARAM_BOOL);
    $stmt->bindValue(':commentaire', $commentaire);
    $stmt->bindValue(':id', $validation['id'], PDO::PARAM_INT);
    $stmt->execute();

    $collection->insertOne([
        'utilisateur_id' => (int)$validation['utilisateur_id'],
        'covoiturage_id' => (int)$validation['covoiturage_id'],
        'note' => $note,
        'commentaire' => $commentaire,
        'est_valide' => (bool)$est_valide,
        'date' => new UTCDateTime(),
        'statut' => 'en attente'
    ]);

    $_SESSION['success'] = "Merci ! Votre retour a bien été enregistré.";
    header('Location: connexion.php');
    exit;
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<link rel="stylesheet" href="/EcoRide/back/public/assets/css/espace_utilisateur.css">

<div class="container mt-5">
    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php elseif ($validation): ?>
        <h2 class="mb-4">Confirmation de trajet</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="mb-3">
                <label class="form-label fw-bold">Comment s’est passé le trajet ?</label><br>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="validation" value="ok" id="validation_ok" required>
                    <label class="form-check-label" for="validation_ok">Tout s’est bien passé ✅</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="validation" value="pb" id="validation_pb" required>
                    <label class="form-check-label" for="validation_pb">Un problème s’est produit 😟</label>
                </div>
            </div>

            <div class="mb-3">
                <label for="note" class="form-label">Note du trajet (1 à 5)</label>
                <select class="form-select" name="note" id="note" required>
                    <option value="">-- Choisissez --</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?> ★</option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="commentaire" class="form-label">Commentaire (facultatif)</label>
                <textarea class="form-control" name="commentaire" id="commentaire" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-custom">Envoyer mon retour</button>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
