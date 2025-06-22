document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("search-form");

  if (!form) return;

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const depart = document.getElementById("departure").value.trim();
    const arrivee = document.getElementById("arrival").value.trim();
    const date = document.getElementById("date").value;

    if (depart && arrivee && date) {
      const url = new URL("../recherche.php", window.location.origin); // à adapter selon ton arborescence
      url.searchParams.append("adresse_depart", depart);
      url.searchParams.append("adresse_arrivee", arrivee);
      url.searchParams.append("date_trajet", date);
      window.location.href = url.toString();
    }
  });
});
