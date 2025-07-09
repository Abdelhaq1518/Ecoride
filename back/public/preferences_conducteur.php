<?php
session_start();
require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/../includes/verify_csrf.php';

if (!isset($_SESSION['utilisateur'])) {
    header('Location: connexion.php');
    exit;
}

$userId = $_SESSION['utilisateur']['id'];

// Récupérer les préférences actuelles
$stmt = $pdo->prepare("SELECT * FROM preferences_conducteur WHERE utilisateur_id = :uid");
$stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
$stmt->execute();
$pref = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie();

    $fumeur = isset($_POST['fumeur']) ? 1 : 0;
    $animaux = isset($_POST['animaux']) ? 1 : 0;
    $musique = isset($_POST['musique']) ? 1 : 0;
    $discussion = $_POST['discussion'] ?? 'calme';
    $autres = trim($_POST['autres_preferences'] ?? '');

    if ($pref) {
        $stmt = $pdo->prepare("UPDATE preferences_conducteur SET fumeur = :fumeur, animaux = :animaux, musique = :musique, discussion = :discussion, autres_preferences = :autres WHERE utilisateur_id = :uid");
    } else {
        $stmt = $pdo->prepare("INSERT INTO preferences_conducteur (utilisateur_id, fumeur, animaux, musique, discussion, autres_preferences) VALUES (:uid, :fumeur, :animaux, :musique, :discussion, :autres)");
    }

    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':fumeur', $fumeur, PDO::PARAM_INT);
    $stmt->bindValue(':animaux', $animaux, PDO::PARAM_INT);
    $stmt->bindValue(':musique', $musique, PDO::PARAM_INT);
    $stmt->bindValue(':discussion', $discussion);
    $stmt->bindValue(':autres', $autres);
    $stmt->execute();

    $_SESSION['success'] = "Préférences mises à jour.";
    header('Location: preferences_conducteur.php');
    exit;
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<link rel="stylesheet" href="assets/css/espace_utilisateur.css">

<div class="container mt-4">
    <h2 class="mb-4">Mes préférences</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Colonne gauche : préférences actuelles -->
        <div class="col-md-6 mb-4">
            <h4>Préférences actuelles</h4>
            <?php if ($pref): ?>
                <ul class="list-group">
                    <li class="list-group-item">Fumeur : <strong><?= $pref['fumeur'] ? 'Oui' : 'Non' ?></strong></li>
                    <li class="list-group-item">Animaux : <strong><?= $pref['animaux'] ? 'Oui' : 'Non' ?></strong></li>
                    <li class="list-group-item">Musique : <strong><?= $pref['musique'] ? 'Oui' : 'Non' ?></strong></li>
                    <li class="list-group-item">Discussion : <strong><?= htmlspecialchars($pref['discussion']) ?></strong></li>
                    <li class="list-group-item">Autres : <strong><?= $pref['autres_preferences'] ?: 'Aucune' ?></strong></li>
                </ul>
            <?php else: ?>
                <p class="text-muted">Aucune préférence enregistrée.</p>
            <?php endif; ?>
        </div>

        <!-- Colonne droite : formulaire -->
        <div class="col-md-6">
            <h4>Modifier mes préférences</h4>
            <form action="" method="POST">
                <?= csrfInput() ?>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="fumeur" name="fumeur" <?= ($pref && $pref['fumeur']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="fumeur">Fumeur</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="animaux" name="animaux" <?= ($pref && $pref['animaux']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="animaux">Animaux acceptés</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="musique" name="musique" <?= ($pref && $pref['musique']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="musique">Musique</label>
                </div>

                <div class="mb-3">
                    <label for="discussion" class="form-label">Discussion</label>
                    <select class="form-select" name="discussion" id="discussion">
                        <?php
                        $options = ['silencieux', 'discret', 'calme', 'curieux', 'bavard'];
                        foreach ($options as $opt) {
                            $selected = ($pref && $pref['discussion'] === $opt) ? 'selected' : '';
                            echo "<option value=\"$opt\" $selected>" . ucfirst($opt) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="autres_preferences" class="form-label">Autres préférences</label>
                    <textarea class="form-control" name="autres_preferences" id="autres_preferences" rows="3"><?= htmlspecialchars($pref['autres_preferences'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
