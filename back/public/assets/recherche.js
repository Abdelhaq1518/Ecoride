console.log("Form handler attached");
const form = document.getElementById("search-form");
form.removeEventListener("submit", handleSearch);
form.addEventListener("submit", handleSearch);

function handleSearch(e) {
  e.preventDefault();

  const depart = document.getElementById("depart").value.trim();
  const arrivee = document.getElementById("arrivee").value.trim();
  const date = document.getElementById("date").value;

  const ecologique = document.getElementById("filter-ecologique")?.value || "";
  const prixMax = document.getElementById("filter-prix")?.value || "";
  const dureeMax = document.getElementById("filter-duree")?.value || "";
  const noteMin = document.getElementById("filter-note")?.value || "";

  const url = new URL("get_trajets.php", window.location.href);
  url.searchParams.set("depart", depart);
  url.searchParams.set("arrivee", arrivee);
  url.searchParams.set("date", date);
  url.searchParams.set("ecologique", ecologique);
  url.searchParams.set("prix_max", prixMax);
  url.searchParams.set("duree_max", dureeMax);
  url.searchParams.set("note_min", noteMin);

  fetch(url.toString())
    .then((response) => response.json())
    .then((data) => {
      const resultsDiv = document.getElementById("results");
      resultsDiv.innerHTML = `
        <div class="filters d-flex flex-wrap align-items-center mb-3">
          <div class="filter-group me-2">
            <label for="filter-ecologique" class="form-label">Voyage écologique :</label>
            <select id="filter-ecologique" class="form-select form-select-sm">
              <option value="">Tous</option>
              <option value="ecologique">Écologique</option>
              <option value="standard">Standard</option>
            </select>
          </div>
          <div class="filter-group me-2">
            <label for="filter-prix" class="form-label">Prix maximum :</label>
            <input type="number" id="filter-prix" class="form-control form-control-sm" placeholder="Prix max" min="0">
          </div>
          <div class="filter-group me-2">
            <label for="filter-duree" class="form-label">Durée maximale (heures) :</label>
            <input type="number" id="filter-duree" class="form-control form-control-sm" placeholder="Durée max" min="0">
          </div>
          <div class="filter-group me-2">
            <label for="filter-note" class="form-label">Note minimale du chauffeur :</label>
            <input type="number" id="filter-note" class="form-control form-control-sm" placeholder="Note min" min="0" max="5">
          </div>
          <button id="apply-filters" class="btn btn-primary btn-sm">Rechercher</button>
        </div>
      `;

      if (
        !data ||
        data.error ||
        !Array.isArray(data.trajets) ||
        data.trajets.length === 0
      ) {
        resultsDiv.insertAdjacentHTML(
          "beforeend",
          `<p>${data?.error ?? "Aucun trajet trouvé pour cette recherche."}</p>`
        );
        return;
      }

      displayTrajets(data.trajets);

      if (data.note) {
        resultsDiv.insertAdjacentHTML(
          "beforeend",
          `<div class="alert alert-warning mb-4"><strong>Note :</strong> ${data.note}</div>`
        );
      }
    })
    .catch((error) => {
      console.error("Erreur lors de la récupération des trajets :", error);
      document.getElementById(
        "results"
      ).innerHTML = `<p>Une erreur est survenue. Veuillez réessayer.</p>`;
    });
}

function displayTrajets(trajets) {
  const resultsDiv = document.getElementById("results");

  trajets.forEach((trajet) => {
    console.log("Trajet :", trajet); // Pour déboguer

    const photo =
      trajet.photo?.trim() !== ""
        ? `assets/img/${trajet.photo}`
        : `assets/img/default-user.webp`;

    const typeTrajet =
      trajet.type_trajet === "ecologique" ? " Écologique" : " Standard";

    const dateDepart = new Date(trajet.date_depart);
    const jourFr = dateDepart.toLocaleDateString("fr-FR", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });

    const heureDepart = trajet.heure_depart?.substring(0, 5) || "?";
    const heureArrivee = trajet.heure_arrivee?.substring(0, 5) || "?";
    const noteMoyenne = trajet.note_moyenne_conducteur || "Non noté";

    // ✅ Attention au bon nom de fichier ici :
    resultsDiv.insertAdjacentHTML(
      "beforeend",
      `
      <div class="trajet-result shadow-sm p-4 mb-4 rounded-4 border border-light-subtle">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <div class="mb-3">
            <h5 class="mb-1 fw-semibold text-dark">${trajet.adresse_depart}</h5>
            <h5 class="mb-3 fw-semibold text-dark">→ ${
              trajet.adresse_arrivee
            }</h5>
            <p class="mb-1 text-muted">
              Départ le ${jourFr} à ${heureDepart}<br>
              Arrivée estimée à ${heureArrivee}
            </p>
            <div class="d-flex flex-md-row gap-2 mt-2 ms-1">
              <span class="badge text-bg-${
                trajet.type_trajet === "ecologique" ? "success" : "secondary"
              }">${typeTrajet}</span>
              <span class="badge badge-places">${
                trajet.places_disponibles
              } place(s)</span>
              <span class="badge badge-credits">${
                trajet.cout_credits
              } crédits</span>
              <a href="details_trajets.php?id=${
                trajet.covoiturage_id
              }" class="btn btn-outline-success btn-sm">Détail</a>
            </div>
          </div>
          <div class="conducteur d-flex align-items-center gap-3">
            <img src="${photo}" class="conducteur-img rounded-circle border border-2" alt="Photo de ${
        trajet.pseudo
      }" />
            <div>
              <strong>${trajet.pseudo}</strong><br>
              <small class="text-muted">Note : ${noteMoyenne}/5</small>
            </div>
          </div>
        </div>
      </div>
      `
    );
  });

  resultsDiv.classList.remove("d-none");
}
