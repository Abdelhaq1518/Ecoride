<?php
session_start();
require_once __DIR__ . '/../includes/verify_csrf.php';
require_once __DIR__ . '/../dev/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Valide le token CSRF ou stoppe le script
    validateCsrfOrDie($_POST['csrf_token'] ?? null);

    // Vérifie que les champs obligatoires sont bien présents et non vides
    if (
        isset($_POST['pseudo'], $_POST['email'], $_POST['motdepasse']) &&
        !empty($_POST['pseudo']) &&
        !empty($_POST['email']) &&
        !empty($_POST['motdepasse'])
    ) {
        $pseudo = trim($_POST['pseudo']);
        $email = trim($_POST['email']);
        $password = $_POST['motdepasse'];

        // Recherche utilisateur par pseudo et email
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = :pseudo AND email = :email");
        $stmt->bindValue(':pseudo', $pseudo, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $utilisateur = $stmt->fetch();

        if ($utilisateur && password_verify($password, $utilisateur['PASSWORD'])) {
            // Connexion réussie, stockage des données utilisateur en session
            $_SESSION['utilisateur'] = [
                'id'       => $utilisateur['id'],
                'pseudo'   => $utilisateur['pseudo'],
                'email'    => $utilisateur['email'],
                'credits'  => $utilisateur['credits']
            ];

            $_SESSION['success'] = "Bienvenue " . htmlspecialchars($utilisateur['pseudo']) . " !";

            header('Location: ../public/connexion.php');
            exit;
        } else {
            $_SESSION['erreur'] = "Identifiants incorrects.";
            header('Location: ../public/connexion.php');
            exit;
        }
    } else {
        $_SESSION['erreur'] = "Veuillez remplir tous les champs.";
        header('Location: ../public/connexion.php');
        exit;
    }

} else {
    $_SESSION['erreur'] = "Requête non autorisée.";
    header('Location: ../public/connexion.php');
    exit;
}
