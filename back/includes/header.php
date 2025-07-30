<?php
require_once __DIR__ . '/ini.php';
require_once __DIR__ . '/../public/config.php';

// Déduction du nom de la page courante (ex: espace_utilisateur)
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');

// Déduction du chemin CSS associé
$customCssPath = BASE_URL . "/assets/css/{$currentPage}.css";
$physicalCssPath = __DIR__ . '/../public/assets/css/' . $currentPage . '.css';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Bootstrap 5.3.7 CSS -->
  <link 
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" 
    rel="stylesheet" 
    integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" 
    crossorigin="anonymous" 
  />

  <!-- CSS global personnalisé -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/index.css" />

  <!-- CSS spécifique à la page (si le fichier existe) -->
  <?php if (file_exists($physicalCssPath)) : ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($customCssPath) ?>">
  <?php endif; ?>
</head>
<body>

  <title>EcoRide</title>
</head>
<body>
  <header>
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom-warning">
      <div class="container-fluid">
        <a class="navbar-brand" href="/ecoride/back/public/index.php">
          <img src="/ecoride/back/public/assets/img/logo.webp" alt="EcoRide Logo" class="logo-ecoride" />
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
                href="/ecoride/back/public/covoiturages.php"
              >Les covoiturages</a>
            </li>
            <li class="nav-item">
              <a 
                class="nav-eco nav-link px-4 <?= basename($_SERVER['SCRIPT_NAME']) === 'connexion.php' ? 'active' : '' ?>" 
                href="/ecoride/back/public/connexion.php"
              >Connexion</a>
            </li>
            <li class="nav-item">
              <a 
                class="nav-eco nav-link px-4 <?= basename($_SERVER['SCRIPT_NAME']) === 'contact.php' ? 'active' : '' ?>" 
                href="/ecoride/back/public/contact.php"
              >Contact</a>
            </li>

            <?php if (!empty($_SESSION['utilisateur'])): ?>
              <li class="nav-item">
                <a 
                  class="nav-eco nav-link px-4 <?= basename($_SERVER['SCRIPT_NAME']) === 'espace_utilisateur.php' ? 'active' : '' ?>" 
                  href="/ecoride/back/public/espace_utilisateur.php"
                >Mon espace</a>
              </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Bootstrap JS -->
  <script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" 
    integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" 
    crossorigin="anonymous"
  ></script>

  <!-- Routing script et recherche.js -->
<script src="/ecoride/back/public/assets/routages.js"></script>
<script src="/ecoride/back/public/assets/recherche.js"></script>

