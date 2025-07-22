<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../dev/db.php';

$user_id = $_SESSION['utilisateur']['id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour participer.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$covoiturage_id = $input['covoiturage_id'] ?? null;

if (!$covoiturage_id || !is_numeric($covoiturage_id)) {
    echo json_encode(['success' => false, 'message' => 'Trajet invalide.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Récupération du trajet avec FOR UPDATE pour verrouiller
    $stmt = $pdo->prepare("SELECT createur_id, places_disponibles, cout_credits FROM covoiturages WHERE covoiturage_id = :id FOR UPDATE");
    $stmt->bindValue(':id', $covoiturage_id, PDO::PARAM_INT);
    $stmt->execute();
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Trajet non trouvé.']);
        exit;
    }

    //  Empêcher la participation à son propre trajet
    if ((int)$trajet['createur_id'] === (int)$user_id) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas participer à votre propre trajet.']);
        exit;
    }

    //  Vérifier si l'utilisateur a le rôle passager ou combo
    $stmtRole = $pdo->prepare("
        SELECT r.libelle
        FROM utilisateur_roles ur
        JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.utilisateur_id = :id
    ");
    $stmtRole->bindValue(':id', $user_id, PDO::PARAM_INT);
    $stmtRole->execute();
    $userRoles = $stmtRole->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('passager', $userRoles) && !in_array('combo', $userRoles)) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Seuls les passagers peuvent participer à un trajet.']);
        exit;
    }

    // Vérifier si l'utilisateur participe déjà
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM participations WHERE utilisateur_id = :uid AND covoiturage_id = :cid");
    $stmtCheck->bindValue(':uid', $user_id, PDO::PARAM_INT);
    $stmtCheck->bindValue(':cid', $covoiturage_id, PDO::PARAM_INT);
    $stmtCheck->execute();

    if ($stmtCheck->fetchColumn() > 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Vous participez déjà à ce trajet.']);
        exit;
    }

    // Vérifier les places
    if ((int)$trajet['places_disponibles'] <= 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Ce trajet est complet.']);
        exit;
    }

    // Vérifier les crédits
    $stmtCredits = $pdo->prepare("SELECT credits FROM utilisateurs WHERE id = :id FOR UPDATE");
    $stmtCredits->bindValue(':id', $user_id, PDO::PARAM_INT);
    $stmtCredits->execute();
    $user = $stmtCredits->fetch(PDO::FETCH_ASSOC);

    if (!$user || (int)$user['credits'] < (int)$trajet['cout_credits']) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Crédits insuffisants.']);
        exit;
    }

    // Mettre à jour les places disponibles
    $stmtMajPlaces = $pdo->prepare("UPDATE covoiturages SET places_disponibles = places_disponibles - 1 WHERE covoiturage_id = :id");
    $stmtMajPlaces->bindValue(':id', $covoiturage_id, PDO::PARAM_INT);
    $stmtMajPlaces->execute();

    // Débiter les crédits du passager
    $stmtMajCredits = $pdo->prepare("UPDATE utilisateurs SET credits = credits - :cout WHERE id = :id");
    $stmtMajCredits->bindValue(':cout', $trajet['cout_credits'], PDO::PARAM_INT);
    $stmtMajCredits->bindValue(':id', $user_id, PDO::PARAM_INT);
    $stmtMajCredits->execute();

    // Enregistrer la participation du passager avec crédits en attente
    $stmtInsert = $pdo->prepare("
        INSERT INTO participations (utilisateur_id, covoiturage_id, date_participation, etat_credit)
        VALUES (:user_id, :covoiturage_id, NOW(), 'en_attente')
    ");
    $stmtInsert->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmtInsert->bindValue(':covoiturage_id', $covoiturage_id, PDO::PARAM_INT);
    $stmtInsert->execute();

    // Enregistrer aussi le conducteur si ce n'est pas déjà fait
    $stmtCheckConducteur = $pdo->prepare("SELECT COUNT(*) FROM participations WHERE utilisateur_id = :uid AND covoiturage_id = :cid");
    $stmtCheckConducteur->bindValue(':uid', $trajet['createur_id'], PDO::PARAM_INT);
    $stmtCheckConducteur->bindValue(':cid', $covoiturage_id, PDO::PARAM_INT);
    $stmtCheckConducteur->execute();

    if ($stmtCheckConducteur->fetchColumn() == 0) {
        $stmtInsertConducteur = $pdo->prepare("
            INSERT INTO participations (utilisateur_id, covoiturage_id, date_participation)
            VALUES (:user_id, :covoiturage_id, NOW())
        ");
        $stmtInsertConducteur->bindValue(':user_id', $trajet['createur_id'], PDO::PARAM_INT);
        $stmtInsertConducteur->bindValue(':covoiturage_id', $covoiturage_id, PDO::PARAM_INT);
        $stmtInsertConducteur->execute();
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Votre participation est prise en compte. Les crédits sont en attente de validation.',
        'places_restantes' => (int)$trajet['places_disponibles'] - 1
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue.']);
}
