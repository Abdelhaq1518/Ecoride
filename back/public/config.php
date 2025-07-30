<?php
require_once __DIR__ . '/../includes/ini.php';

// Détection de l'environnement (local ou production)
$host = $_SERVER['HTTP_HOST'];

if ($host === 'localhost' || str_contains($host, '127.0.0.1')) {
    define('BASE_URL', '/ecoride/back/public'); // En local
} else {
    define('BASE_URL', '/back/public'); // En production
}

// Déduction de la page actuelle
$currentPage = basename($_SERVER['SCRIPT_NAME']);

// Dictionnaire de correspondance pour les CSS partagés
$sharedStyles = [
    'historique.php' => 'espace_utilisateur.css',
    'statut_trajet.php' => 'espace_utilisateur.css',
    'ajout_trajet.php' => 'espace_utilisateur.css',
    'espace_utilisateur.php' => 'espace_utilisateur.css',
    // Ajoute ici d'autres mappings si besoin
];

// Vérifie si la page a un style partagé
if (array_key_exists($currentPage, $sharedStyles)) {
    $cssFile = $sharedStyles[$currentPage];
    echo '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/' . $cssFile . '" />' . PHP_EOL;
} else {
    // Sinon, essaie de charger une CSS du même nom que la page
    $pageName = pathinfo($currentPage, PATHINFO_FILENAME);
    $cssPath = __DIR__ . '/../public/assets/css/' . $pageName . '.css';

    if (file_exists($cssPath)) {
        echo '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/' . $pageName . '.css" />' . PHP_EOL;
    }
}
