<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../dev/db.php';
require_once __DIR__ . '/../includes/verify_csrf.php';
require_once __DIR__ . '/../includes/ini.php';

// Récupération des véhicules existants du chauffeur
$stmtVehicules = $pdo->prepare("SELECT voiture_id, marque, modele, nb_places, couleur, date_immatriculation FROM voiture WHERE utilisateur_id = :uid");
$stmtVehicules->bindValue(':uid', $_SESSION['utilisateur']['id'], PDO::PARAM_INT);
$stmtVehicules->execute();
$vehicules = $stmtVehicules->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/espace_utilisateur.css">';
<div class="container mt-5">
    <h2>Ajouter un trajet</h2>

    <?php if (isset($_SESSION['erreur'])): ?>
        <div class="alert alert-danger"> <?= $_SESSION['erreur'] ?> </div>
        <?php unset($_SESSION['erreur']); endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"> <?= $_SESSION['success'] ?> </div>
        <?php unset($_SESSION['success']); endif; ?>

    <form method="post" action="traitement_ajout_trajet.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="date_depart">Date de départ</label>
                <input type="date" class="form-control" name="date_depart" required>
            </div>
            <div class="col-md-3">
                <label for="heure_depart">Heure de départ</label>
                <select class="form-control" name="heure_depart" required>
                    <?php for ($h = 0; $h < 24; $h++):
                        foreach (["00", "30"] as $m):
                            $time = sprintf("%02d:%s", $h, $m);
                            echo "<option value='$time'>$time</option>";
                        endforeach;
                    endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="heure_arrivee">Heure d'arrivée</label>
                <select class="form-control" name="heure_arrivee" required>
                    <?php for ($h = 0; $h < 24; $h++):
                        foreach (["00", "30"] as $m):
                            $time = sprintf("%02d:%s", $h, $m);
                            echo "<option value='$time'>$time</option>";
                        endforeach;
                    endfor; ?>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="lieu_depart">Adresse de départ</label>
                <input type="text" class="form-control" name="lieu_depart" required>
            </div>
            <div class="col-md-6">
                <label for="lieu_arrivee">Adresse d'arrivée</label>
                <input type="text" class="form-control" name="lieu_arrivee" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="places_disponibles">Places disponibles</label>
                <input type="number" class="form-control" name="places_disponibles" min="1" required>
            </div>
            <div class="col-md-6">
                <label for="cout_credits">Montant en crédits</label>
                <input type="number" class="form-control" name="cout_credits" min="1" required>
            </div>
        </div>

        <hr>

        <div class="mb-3">
            <label>Type de véhicule</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="choix_vehicule" value="existant" id="vehiculeExistant" checked onclick="toggleVehicule(this.value)">
                <label class="form-check-label" for="vehiculeExistant">Véhicule existant</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="choix_vehicule" value="libre" id="vehiculeLibre" onclick="toggleVehicule(this.value)">
                <label class="form-check-label" for="vehiculeLibre">Véhicule ponctuel</label>
            </div>
        </div>

        <div class="row" id="blocVehiculeExistant">
            <div class="col-md-12 mb-3">
                <label for="vehicule_id">Sélectionnez un véhicule</label>
                <select class="form-control" name="vehicule_id">
                    <?php foreach ($vehicules as $vehicule): ?>
                        <option value="<?= $vehicule['voiture_id'] ?>">
                            <?= htmlspecialchars($vehicule['marque'] . ' ' . $vehicule['modele'] . ' - ' . $vehicule['nb_places'] . ' places - ' . $vehicule['couleur'] . ' - ' . $vehicule['date_immatriculation']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div id="blocVehiculeLibre" style="display: none;">
            <div class="row">
                <div class="col-md-4">
                    <label>Marque</label>
                    <input type="text" name="vehicule_libre_marque" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>Modèle</label>
                    <input type="text" name="vehicule_libre_modele" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>Places</label>
                    <input type="number" name="vehicule_libre_places" class="form-control" min="1">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4">
                    <label>Couleur</label>
                    <input type="text" name="vehicule_libre_couleur" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>Type d'énergie</label>
                    <input type="text" name="vehicule_libre_energie" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>Date de 1re immatriculation</label>
                    <input type="date" name="vehicule_libre_date_immatriculation" class="form-control" max="<?= date('Y-m-d') ?>">
                </div>
            </div>
        </div>

        <div class="mt-4 text-center">
            <button type="submit" class="btn btn-custom">Créer le trajet</button>
        </div>
    </form>
</div>

<script>
    function toggleVehicule(value) {
        const blocExistant = document.getElementById("blocVehiculeExistant");
        const blocLibre = document.getElementById("blocVehiculeLibre");

        if (value === "existant") {
            blocExistant.style.display = "block";
            blocLibre.style.display = "none";
        } else {
            blocExistant.style.display = "none";
            blocLibre.style.display = "block";
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
