<footer class="custom-footer text-center text-md-start mt-5 py-4 px-3">
  <div class="container d-md-flex justify-content-between align-items-center">
    <div class="mb-3 mb-md-0 text-muted small d-flex flex-column flex-md-row align-items-center gap-2">
      <div class="d-flex align-items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
          fill="currentColor" class="bi bi-c-circle" viewBox="0 0 16 16" aria-hidden="true">
          <path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zM0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8z"/>
          <path d="M10.079 10.11a.5.5 0 0 0 .764.643 4.001 4.001 0 1 1 0-5.506.5.5 0 1 0-.764.643A3 3 0 1 0 10.08 10.11z"/>
        </svg>
        <span>2025 Abde-lhake AIT SAID, tous droits réservés.</span>
      </div>
      <span class="text-muted small ms-md-3">
        <a href="mailto:covoitest7@gmail.com" class="text-muted text-decoration-none">covoitest7@gmail.com</a>
      </span>
    </div>
    <div class="d-flex gap-3">
      <a href="/ecoride/back/public/mentions_legales.php" class="footer-link">Mentions légales</a>
      <a href="/ecoride/back/public/politique.php" class="footer-link">Politique de confidentialité</a>
    </div>
  </div>
</footer>

<!-- Script de recherche (ajuste le chemin si nécessaire) -->
<script src="/ecoride/back/public/assets/recherche.js"></script>

<!-- Bootstrap Bundle (inclut Popper) -->
<script 
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" 
  crossorigin="anonymous"
></script>


<script>
  const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
  const navbarCollapse = document.querySelector('.navbar-collapse');

  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      if (navbarCollapse.classList.contains('show')) {
        const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
          toggle: true
        });
      }
    });
  });
</script>

</body>
</html>
