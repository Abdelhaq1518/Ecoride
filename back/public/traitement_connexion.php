<?php
session_start();
require_once __DIR__ . '/../dev/db.php';

if (
    isset($_POST['pseudo'], $_POST['email'], $_POST['motdepasse']) &&
    !empty($_POST['pseudo']) &&
    !empty($_POST['email']) &&
    !empty($_POST['motdepasse'])
) {
    $pseudo = trim($_POST['pseudo']);
    $email = trim($_POST['email']);
    $password = $_POST['motdepasse'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = :pseudo AND email = :email");
    $stmt->execute([
        ':pseudo' => $pseudo,
        ':email'  => $email
    ]);

    $utilisateur = $stmt->fetch();

    if ($utilisateur && password_verify($password, $utilisateur['PASSWORD'])) {
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