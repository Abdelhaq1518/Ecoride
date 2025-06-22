<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Résultats des trajets</title>
</head>
<body>
  <h1>Résultats des covoiturages</h1>
  <div id="resultats">Chargement...</div>

  <script>
    // On récupère les paramètres directement depuis l'URL
    const params = new URLSearchParams(window.location.search);
    const depart = params.get("adresse_depart");
    const arrivee = params.get("adresse_arrivee");
    const date = params.get("date_trajet");

    // Vérification minimale
    if (!depart || !arrivee || !date) {
      document.getElementById("resultats").innerHTML = "<p>Paramètres manquants dans l'URL.</p>";
    } else {
      // On lance la requête fetch vers le PHP
      fetch(`get_trajets.php?adresse_depart=${encodeURIComponent(depart)}&adresse_arrivee=${encodeURIComponent(arrivee)}&date_trajet=${encodeURIComponent(date)}`)
        .then(response => response.json())
        .then(data => {
          const container = document.getElementById("resultats");
          if (!data || data.length === 0) {
            container.innerHTML = "<p>Aucun covoiturage trouvé.</p>";
          } else {
            container.innerHTML = ""; // Vider le "Chargement..."
            data.forEach(trajet => {
              container.innerHTML += `
                <div>
                  <h3>${trajet.pseudo}</h3>
                  <img src="${trajet.photo}" alt="photo de ${trajet.pseudo}" width="80" />
                  <p>${trajet.adresse_depart} → ${trajet.adresse_arrivee}</p>
                  <p>Départ : ${trajet.date_depart} à ${trajet.heure_depart}</p>
                  <p>Arrivée : ${trajet.date_arrivee} à ${trajet.heure_arrivee}</p>
                  <p>Places restantes : ${trajet.places_disponibles}</p>
                  <p>Type : ${trajet.type === "ecologique" ? "Écologique" : "Classique"}</p>
                  <a href="details.php?id=${trajet.id}">Détail</a>
                </div>
                <hr/>
              `;
            });
          }
        })
        .catch(error => {
          document.getElementById("resultats").innerHTML = "<p>Erreur lors de la recherche.</p>";
          console.error("Erreur fetch:", error);
        });
    }
  </script>
</body>
</html>
