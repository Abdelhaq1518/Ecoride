<?php
session_start();
require_once __DIR__ . '/../dev/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Vérification CSRF inline
    if (!isset($_SESSION['csrf_token']) || ($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) {
        die("Erreur de sécurité : token CSRF invalide.");
    }

    // Vérifie que les champs obligatoires sont présents et non vides
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
            // Connexion réussie, stockage en session
            $_SESSION['utilisateur'] = [
                'id'       => $utilisateur['id'],
                'pseudo'   => $utilisateur['pseudo'],
                'email'    => $utilisateur['email'],
                'credits'  => $utilisateur['credits']
            ];

            $_SESSION['success'] = "Bienvenue " . htmlspecialchars($utilisateur['pseudo']) . " !";

            header('Location: ../public/espace_utilisateur.php');
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
