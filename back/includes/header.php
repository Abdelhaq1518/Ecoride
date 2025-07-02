<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="assets/index.css" />
    <?php if (basename($_SERVER['SCRIPT_NAME']) === 'covoiturages.php'): ?>
      <link rel="stylesheet" href="/EcoRide/back/public/assets/css/covoiturages.css">
    <?php endif; ?>
    <title>EcoRide</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-ENjdO4Dr2bkBIFxQpeoJxE6U5eM+/3f+GpWQYl4nUOH3zYtD3Lz58GxIV5DkBx1Q"
      crossorigin="anonymous"
    />
  </head>
  <body>
    <header>
      <nav class="navbar navbar-expand-lg navbar-light navbar-custom-warning">
        <div class="container-fluid">
          <a class="navbar-brand" href="index.php">
            <img
              src="assets/logo.svg"
              alt="EcoRide Logo"
              class="logo-ecoride"
            />
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
                <a class="nav-eco nav-link px-4" href="covoiturages.php" id="nav-covoiturages">Les covoiturages</a>
              </li>
              <li class="nav-item">
                <a class="nav-eco nav-link px-4" href="connexion.php" id="nav-connexion">Connexion</a>
              </li>
              <li class="nav-item">
                <a class="nav-eco nav-link px-4" href="contact.php" id="nav-contact">Contact</a>
              </li>
            </ul>
          </div>
        </div>
      </nav>
    </header>

    <!-- Inclure Bootstrap JS (important pour le toggler) -->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+pm/8ujB0K/H16v/7WTr9dR1+OpDy"
      crossorigin="anonymous"
    ></script>

    <!-- Inclure ton script de routage -->
    <script src="assets/js/routages.js"></script>
  </body>
</html>
