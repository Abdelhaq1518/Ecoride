<?php
// Démarre la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Génère un token CSRF unique et le stocke en session si inexistant.
 * @return string Le token CSRF
 */
function generateToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // 64 caractères
    }
    return $_SESSION['csrf_token'];
}

/**
 * Injecte le champ CSRF dans le formulaire HTML.
 * @return string Code HTML de l’input caché avec le token
 */
function csrfInput(): string {
    $token = generateToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Vérifie la validité du token CSRF envoyé.
 * @param string $token Le token transmis par le formulaire
 * @return bool true si valide, false sinon
 */
function verifyToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Fonction optionnelle : valide automatiquement ou arrête le script
 */
function validateCsrfOrDie(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !verifyToken($_POST['csrf_token'])) {
            $_SESSION['erreur'] = 'Requête non autorisée (CSRF)';
            header('Location: connexion.php');
            exit;
        }
    }
}