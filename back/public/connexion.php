<?php
session_start();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf_token.php';
?>

<!-- Feuille de style spécifique -->
<link rel="stylesheet" href="assets/css/connexion.css">

<main class="container py-5">
  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success text-center" role="alert">
      <?= htmlspecialchars($_SESSION['success']) ?> Connexion réussie.
      <div class="mt-3">
        <a href="covoiturages.php" class="btn btn-success">Voir les trajets</a>
      </div>
    </div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['erreur'])): ?>
    <div class="alert alert-danger text-center" role="alert">
      <?= htmlspecialchars($_SESSION['erreur']) ?>
    </div>
    <?php unset($_SESSION['erreur']); ?>
  <?php endif; ?>

  <?php if (isset($_GET['inscription']) && $_GET['inscription'] === 'success'): ?>
    <div class="alert alert-success text-center" role="alert">
      Inscription réussie ! Pour bien démarrer, vous bénéficiez de 20 crédits.
    </div>
  <?php endif; ?>

  <div class="connexion-container">
    <!-- Formulaire de connexion -->
    <div class="form-block bloc-connexion">
      <h4 class="mb-4">Se connecter</h4>
      <form method="post" action="traitement_connexion.php">
        <?= csrfInput() ?> <!-- CSRF token ajouté -->
        <div class="inputs-group">
          <div class="mb-3">
            <label for="pseudo" class="form-label">Pseudo</label>
            <input type="text" class="form-control" id="pseudo" name="pseudo" required>
          </div>
          <div class="mb-3">
            <label for="email_conn" class="form-label">Adresse e-mail</label>
            <input type="email" class="form-control" id="email_conn" name="email" required>
          </div>
          <div class="mb-3">
            <label for="motdepasse" class="form-label">Mot de passe</label>
            <input type="password" class="form-control" id="motdepasse" name="motdepasse" required>
          </div>
        </div>
        <button type="submit" class="btn btn-custom w-100">SE CONNECTER</button>
        <?php if (isset($_SESSION['utilisateur'])): ?>
        <div class="mt-3 text-center">
          <a href="deconnexion.php" class="btn btn-outline-secondary w-100">Se déconnecter</a>
        </div>
        <?php endif; ?>
      </form>
    </div>

    <!-- Formulaire d'inscription -->
    <div class="form-block bloc-inscription">
      <h4 class="mb-4">S'inscrire</h4>
      <form method="post" action="traitement_inscription.php">
        <?= csrfInput() ?> <!-- CSRF token ajouté -->
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
        <button type="submit" class="btn btn-custom w-100">S'INSCRIRE</button>
      </form>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
