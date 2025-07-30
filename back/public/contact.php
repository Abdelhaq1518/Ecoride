<?php
require_once 'config.php'; 
require_once __DIR__ . '/../includes/header.php';

// Traitement du formulaire
$confirmation = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nom = htmlspecialchars($_POST['nom'] ?? '');
  $prenom = htmlspecialchars($_POST['prenom'] ?? '');
  $email = htmlspecialchars($_POST['email'] ?? '');
  $demande = htmlspecialchars($_POST['demande'] ?? '');

  // Tu peux ici envoyer un mail ou enregistrer la demande en BDD

  $confirmation = "Merci $prenom, votre demande a bien été envoyée.";
}
?>

<main>
  <h1 class="text-center mt-5">Nous contacter</h1>

  <section class="presentation-searchbar container-fluid min-vh-100 d-flex flex-column flex-md-row">
    <!-- Partie gauche : Présentation de l'équipe -->
    <div class="col-md-6 d-flex flex-column justify-content-center p-4">
      <h2 class="mb-4">L'équipe EcoRide</h2>
      <p>
        EcoRide est née à Dublin, sous l’impulsion de José, notre CEO, accompagné de Pauline, Susie et Andreas.
        En mars 2025, notre projet a remporté le <strong>Grand Prix de l’innovation</strong> au Salon de Berne.
        Forts de ce succès, nous avons levé des fonds pour lancer EcoRide en France, avec l’ambition de proposer
        une plateforme de covoiturage respectueuse de l’environnement et de ses utilisateurs.
      </p>
      <p>
        Notre siège français est situé à <strong>Saint-Ouen-sur-Seine</strong>, à deux pas du campus d’Alstom,
        dans un écosystème d’innovation durable.
      </p>
    </div>

    <!-- Partie droite : formulaire -->
    <div class="col-md-6 d-flex align-items-center justify-content-center p-4">
      <div class="form-block w-100">
        <h4 class="mb-3">Formulaire de contact</h4>

        <?php if (!empty($confirmation)): ?>
          <div class="alert alert-success"><?= $confirmation ?></div>
        <?php endif; ?>

        <form method="post" action="">
          <div class="mb-3">
            <label for="nom" class="form-label">Nom</label>
            <input type="text" class="form-control" name="nom" id="nom" required>
          </div>
          <div class="mb-3">
            <label for="prenom" class="form-label">Prénom</label>
            <input type="text" class="form-control" name="prenom" id="prenom" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" name="email" id="email" required>
          </div>
          <div class="mb-3">
            <label for="demande" class="form-label">Votre demande</label>
            <textarea class="form-control" name="demande" id="demande" rows="5" required></textarea>
          </div>
          <button type="submit" class="btn btn-custom w-100">Envoyer</button>
        </form>
      </div>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
