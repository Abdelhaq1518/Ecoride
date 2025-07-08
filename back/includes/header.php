
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Bootstrap 5.3.7 CSS : charger en premier -->
  <link 
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" 
    rel="stylesheet" 
    integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" 
    crossorigin="anonymous" 
  />

  <!-- CSS global perso -->
  <link rel="stylesheet" href="/EcoRide/back/public/assets/css/index.css" />

  <?php
  // Inclusion dynamique des CSS spécifiques à la page, si défini
  if (!empty($pageStyles) && is_array($pageStyles)):
      foreach ($pageStyles as $cssFile):
  ?>
      <link rel="stylesheet" href="<?= htmlspecialchars($cssFile) ?>" />
  <?php
      endforeach;
  else:
    // Ancien fallback pour covoiturages.php si pas de $pageStyles défini
    if (basename($_SERVER['SCRIPT_NAME']) === 'covoiturages.php'):
  ?>
      <link rel="stylesheet" href="/EcoRide/back/public/assets/css/covoiturages.css" />
  <?php
    endif;
  endif;
  ?>

  <title>EcoRide</title>
</head>
<body>
  <header>
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom-warning">
      <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
          <img src="assets/logo.svg" alt="EcoRide Logo" class="logo-ecoride" />
        </a>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNavDropdown"
          aria-controls="navbarNavDropdown"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNavDropdown">
          <ul class="navbar-nav ms-auto d-flex align-items-center">
            <li class="nav-item">
              <a 
                class="nav-eco nav-link px-4 <?= basename($_SERVER['SCRIPT_NAME']) === 'covoiturages.php' ? 'active' : '' ?>" 
                href="covoiturages.php" 
                id="nav-covoiturages"
              >Les covoiturages</a>
            </li>
            <li class="nav-item">
              <a 
                class="nav-eco nav-link px-4 <?= basename($_SERVER['SCRIPT_NAME']) === 'connexion.php' ? 'active' : '' ?>" 
                href="connexion.php" 
                id="nav-connexion"
              >Connexion</a>
            </li>
            <li class="nav-item">
              <a 
                class="nav-eco nav-link px-4 <?= basename($_SERVER['SCRIPT_NAME']) === 'contact.php' ? 'active' : '' ?>" 
                href="contact.php" 
                id="nav-contact"
              >Contact</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Bootstrap 5.3.7 JS -->
  <script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" 
    integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" 
    crossorigin="anonymous"
  ></script>

  <!-- Routing script -->
  <script src="/EcoRide/back/public/assets/routages.js"></script>
