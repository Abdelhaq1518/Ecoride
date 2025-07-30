<?php
session_start();
require_once 'config.php'; 
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/../includes/verify_csrf.php';

if (!isset($_SESSION['utilisateur'])) {
    header('Location: connexion.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT r.role_id, r.libelle
    FROM utilisateur_roles ur
    JOIN roles r ON ur.role_id = r.role_id
    WHERE ur.utilisateur_id = :id
");
$stmt->bindValue(':id', $_SESSION['utilisateur']['id'], PDO::PARAM_INT);
$stmt->execute();
$rolesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Extraire les libellés et role_ids dans des arrays séparés
$roles = array_column($rolesData, 'libelle');
$roleIds = array_column($rolesData, 'role_id');

// Si l'utilisateur a le rôle employé (role_id = 4), on le redirige vers espace_employe.php
if (in_array(4, $roleIds)) {
    header('Location: espace_employe.php');
    exit;
}

$stmtVehicules = $pdo->prepare("SELECT * FROM voiture WHERE utilisateur_id = :id");
$stmtVehicules->bindValue(':id', $_SESSION['utilisateur']['id'], PDO::PARAM_INT);
$stmtVehicules->execute();
$vehicules = $stmtVehicules->fetchAll(PDO::FETCH_ASSOC);

$stmtPrefs = $pdo->prepare("SELECT * FROM preferences_conducteur WHERE utilisateur_id = :id LIMIT 1");
$stmtPrefs->bindValue(':id', $_SESSION['utilisateur']['id'], PDO::PARAM_INT);
$stmtPrefs->execute();
$preferences = $stmtPrefs->fetch(PDO::FETCH_ASSOC);


?>

<body>
<div class="full-height-container">
    <!-- Menu latéral -->
    <nav class="sidebar_espace p-3" style="min-width: 220px; min-height: 100vh;">
        <h5 class="mb-4">Mon espace</h5>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="#mes-vehicules" class="nav-link">Mes véhicules</a>
            </li>

            <?php if (in_array('chauffeur', $roles) || in_array('combo', $roles)): ?>
                <li class="nav-item">
                    <a href="preferences_conducteur.php" class="nav-link">Mes préférences</a>
                </li>
                <li class="nav-item">
                    <a href="statut_trajet.php" class="nav-link">Démarrer/Arrêter un covoiturage</a>
                </li>
            <?php endif; ?>

            <li class="nav-item">
                <a href="ajout_trajet.php" class="nav-link">Ajouter un trajet</a>
            </li>
            <li class="nav-item">
                <a href="historique.php" class="nav-link">Historique</a>
            </li>
            <li class="nav-item">
                <a href="deconnexion.php" class="nav-link text-dark">Se déconnecter</a>
            </li>
        </ul>
    </nav>

    <!-- Contenu principal -->
    <main class="p-4 bg-eco-light">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['erreur'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['erreur']) ?></div>
            <?php unset($_SESSION['erreur']); ?>
        <?php endif; ?>

        <h2>Bienvenue, <?= htmlspecialchars($_SESSION['utilisateur']['pseudo']) ?> !</h2>

        <section class="mt-4">
            <h4>Votre rôle actuel :</h4>
            <?php if ($roles): ?>
                <ul>
                    <?php foreach ($roles as $role): ?>
                        <li><?= htmlspecialchars(ucfirst($role)) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-warning">Aucun rôle associé.</p>
            <?php endif; ?>
        </section>

        <section class="mt-4">
            <h4>Actions possibles :</h4>
            <?php if (in_array('chauffeur', $roles) || in_array('combo', $roles)): ?>
                <a href="#form-vehicule" class="btn btn-add mb-2">Ajouter un véhicule</a><br>
            <?php endif; ?>

            <?php if (in_array('passager', $roles) || in_array('combo', $roles)): ?>
                <a href="#" class="btn btn-secondary mb-2">Voir mes trajets réservés</a>
            <?php endif; ?>

            <?php if (!in_array('chauffeur', $roles) && !in_array('combo', $roles)): ?>
                <a href="devenir_chauffeur.php" class="btn btn-choix fw-bold mt-3">Je souhaite devenir chauffeur</a>
            <?php endif; ?>

            <?php if (!in_array('passager', $roles) && !in_array('combo', $roles)): ?>
                <a href="devenir_passager.php" class="btn btn-choix fw-bold mt-2">Je souhaite devenir passager</a>
            <?php endif; ?>
        </section>

        <?php if (in_array('chauffeur', $roles) || in_array('combo', $roles)): ?>
            <section id="mes-vehicules" class="mt-5">
                <h4>Mes véhicules</h4>
                <?php if ($vehicules): ?>
                    <ul class="list-group mb-3">
                        <?php foreach ($vehicules as $v): ?>
                            <li class="list-group-item card-vehicule">
                                <?= htmlspecialchars($v['marque']) ?> <?= htmlspecialchars($v['modele']) ?> - <?= htmlspecialchars($v['immatriculation']) ?> (<?= (int)$v['nb_places'] ?> places)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Aucun véhicule enregistré.</p>
                <?php endif; ?>
            </section>

            <section id="form-vehicule" class="mt-4">
                <h4>Ajouter un véhicule</h4>
                <form action="ajouter_vehicule.php" method="POST" class="row g-3 mt-2 form-block">
                    <?= csrfInput() ?>

                    <div class="col-md-6">
                        <label for="marque" class="form-label">Marque</label>
                        <input type="text" class="form-control" name="marque" id="marque" required>
                    </div>
                    <div class="col-md-6">
                        <label for="modele" class="form-label">Modèle</label>
                        <input type="text" class="form-control" name="modele" id="modele" required>
                    </div>
                    <div class="col-md-6">
                        <label for="couleur" class="form-label">Couleur</label>
                        <input type="text" class="form-control" name="couleur" id="couleur">
                    </div>
                    <div class="col-md-6">
                        <label for="energie" class="form-label">Énergie</label>
                        <select class="form-select" name="energie" id="energie" required>
                            <option value="">-- Choisir --</option>
                            <option value="essence">Essence</option>
                            <option value="diesel">Diesel</option>
                            <option value="électrique">Électrique</option>
                            <option value="hybride">Hybride</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="immatriculation" class="form-label">Plaque d'immatriculation</label>
                        <input type="text" class="form-control" name="immatriculation" id="immatriculation" required>
                    </div>
                    <div class="col-md-6">
                        <label for="places" class="form-label">Nombre de places</label>
                        <input type="number" class="form-control" name="places" id="places" min="1" max="9" required>
                    </div>
                    <div class="col-md-6">
                        <label for="date_immatriculation" class="form-label">Date de première immatriculation</label>
                        <input type="date" class="form-control" name="date_immatriculation" id="date_immatriculation" required max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="col-12">
                        <h5>Préférences</h5>
                        <div class="form-preferences">
                            <input class="form-preferences-input" type="checkbox" name="fumeur" id="fumeur" value="1">
                            <label class="form-preferences-label" for="fumeur">Fumeur</label>
                        </div>
                        <div class="form-preferences">
                            <input class="form-preferences-input" type="checkbox" name="animaux" id="animaux" value="1">
                            <label class="form-preferences-label" for="animaux">Animaux acceptés</label>
                        </div>
                        <div class="form-preferences">
                            <input class="form-preferences-input" type="checkbox" name="musique" id="musique" value="1">
                            <label class="form-preferences-label" for="musique">Musique pendant le trajet</label>
                        </div>
                        <div class="form-preferences">
                            <input class="form-input" type="checkbox" name="discussion" id="discussion" value="1">
                            <label class="form-check-label" for="discussion">Discussion pendant le trajet</label>
                        </div>
                        <div class="mt-2">
                            <label for="autres_preferences" class="form-label">Autres préférences</label>
                            <textarea class="form-control" name="autres_preferences" id="autres_preferences" rows="2" placeholder="Ex : pas de nourriture, pause café, etc."></textarea>
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-custom">Enregistrer le véhicule</button>
                    </div>
                </form>
            </section>
        <?php endif; ?>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>