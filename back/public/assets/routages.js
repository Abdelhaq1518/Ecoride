document.addEventListener("DOMContentLoaded", () => {
  const routes = {
    "nav-covoiturages": "covoiturages.php",
    "nav-connexion": "connexion.php",
    "nav-contact": "contact.php",
  };

  Object.entries(routes).forEach(([id, url]) => {
    const btn = document.getElementById(id);
    if (btn) {
      btn.addEventListener("click", () => {
        window.location.href = url;
      });
    }
  });
});
