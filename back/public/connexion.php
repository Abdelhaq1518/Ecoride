<?php
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Feuille de style spécifique à la page -->
<link rel="stylesheet" href="assets/css/connexion.css">

<main class="container py-5">
  <div class="connexion-container">
    
    <!-- Bloc Connexion -->
    <div class="form-block bloc-connexion">
      <h4 class="mb-4">Se connecter</h4>
      <form method="post" action="traitement_connexion.php" class="h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="mb-3">
            <label for="pseudo" class="form-label">Pseudo</label>
            <input type="text" class="form-control" id="pseudo" name="pseudo" required>
          </div>
          <div class="mb-3">
            <label for="email_connexion" class="form-label">Adresse e-mail</label>
            <input type="email" class="form-control" id="email_connexion" name="email" required>
          </div>
          <div class="mb-3">
            <label for="motdepasse" class="form-label">Mot de passe</label>
            <input type="password" class="form-control" id="motdepasse" name="motdepasse" required>
          </div>
        </div>
        <button type="submit" class="btn btn-custom w-100">SE CONNECTER</button>
      </form>
    </div>

    <!-- Bloc Inscription -->
    <div class="form-block bloc-inscription">
      <h4 class="mb-4">S'inscrire</h4>
      <form method="post" action="traitement_inscription.php" class="h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="pseudo_insc" class="form-label">Pseudo</label>
              <input type="text" class="form-control" id="pseudo_insc" name="pseudo" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="email" class="form-label">Adresse e-mail</label>
              <input type="email" class="form-control" id="email" name="email" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="motdepasse_insc" class="form-label">Mot de passe</label>
            <input type="password" class="form-control" id="motdepasse_insc" name="motdepasse" required>
            <div class="password-note">
              Le mot de passe doit contenir au moins 11 caractères, avec des majuscules, minuscules, chiffres et caractères spéciaux.
            </div>
          </div>
          <div class="mb-3">
            <label for="role" class="form-label">Je suis :</label>
            <select class="form-select" name="role" id="role" required>
              <option value="">Choisissez un rôle</option>
              <option value="passager">Passager</option>
              <option value="chauffeur">Chauffeur</option>
              <option value="les_deux">Les deux</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-custom w-100">S'INSCRIRE</button>
      </form>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
