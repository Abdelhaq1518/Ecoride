<?php include '../includes/header.php'; ?>
<main>
  <h1 class="text-center mt-5">EcoRide</h1>

  <section class="presentation-searchbar container-fluid min-vh-100 d-flex flex-column flex-md-row">
    <!-- Partie gauche -->
    <div class="col-md-6 d-flex flex-column justify-content-center p-4">
      <h2 class="mb-4">C'est quoi EcoRide ?</h2>
      <p>
        EcoRide est une start-up en pleine expansion qui propose une
        plateforme de covoiturage respectueuse de l’environnement.
        Spécialisée dans les trajets en voiture — avec une priorité donnée
        aux véhicules électriques, devant les thermiques et hybrides — EcoRide
        offre à ses utilisateurs la possibilité d’être passager, conducteur ou les deux.
        Les trajets permettent de gagner des crédits via une monnaie virtuelle.
        Innovation, partage et réduction de l’empreinte carbone sont les piliers d’EcoRide. <br><br>
        Avec EcoRide, <strong>“on peut aller loin en se mettant au vert”.</strong>
      </p>
    </div>

    <!-- Partie droite : formulaire -->
    <div class="col-md-6 d-flex align-items-center justify-content-center p-4">
      <form id="search-form" class="w-100" method="post" novalidate>
        <h3 class="mb-4">En voiture !</h3>

        <div class="mb-3">
          <label for="depart" class="form-label">Adresse de départ</label>
          <input type="text" class="form-control" id="depart" name="depart" required>
        </div>

        <div class="mb-3">
          <label for="arrivee" class="form-label">Adresse d'arrivée</label>
          <input type="text" class="form-control" id="arrivee" name="arrivee" required>
        </div>

        <div class="mb-3">
          <label for="date" class="form-label">Date du trajet</label>
          <input type="date" class="form-control" id="date" name="date" required>
        </div>

        <button type="submit" class="btn btn-success w-100">Rechercher</button>
      </form>
    </div>
  </section>

  <!-- Valeurs EcoRide -->
  <section class="container pt-3 pb-4">
    <h2 class="text-center mb-5">Nos valeurs</h2>
    <div class="row g-4">
      <?php
      $valeurs = [
        ["img5.webp", "main portant un chargeur de véhicule électrique", "Écologique"],
        ["img1.webp", "berline électrique noire en cours de chargement", "Pratique"],
        ["img3.webp", "groupe joyeux en voiture", "Convivial"],
        ["img6.webp", "Deux personnes assises dans une berline confortable", "Confortable"],
        ["img2.webp", "femme accédant à la borne de rechargement", "Accessible"],
        ["img4.webp", "passager montre une image sur son téléphone au conducteur", "Solidaire"]
      ];

      foreach ($valeurs as [$src, $alt, $texte]) {
        echo "
        <div class=\"col-md-4\">
          <div class=\"gallery-img-container\">
            <img src=\"assets/img/$src\" alt=\"$alt\" class=\"gallery-img\">
            <span class=\"gallery-text\">$texte</span>
          </div>
        </div>";
      }
      ?>
    </div>
  </section>
</main>

<!-- Script JS pour redirection POST propre -->
<script>
document.getElementById("search-form").addEventListener("submit", function(e) {
  e.preventDefault();

  const depart = document.getElementById("depart").value.trim();
  const arrivee = document.getElementById("arrivee").value.trim();
  const date = document.getElementById("date").value;

  if (!depart || !arrivee || !date) {
    alert("Tous les champs sont obligatoires.");
    return;
  }

  const form = document.createElement("form");
  form.method = "POST";
  form.action = "covoiturages.php";

  const params = { depart, arrivee, date };
  for (const key in params) {
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = key;
    input.value = params[key];
    form.appendChild(input);
  }

  document.body.appendChild(form);
  form.submit();
});
</script>

<?php include '../includes/footer.php'; ?>
