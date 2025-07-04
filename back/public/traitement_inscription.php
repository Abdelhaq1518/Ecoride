<?php
session_start();
require_once __DIR__ . '/../dev/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données POST (trim + null coalescing)
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['motdepasse'] ?? '';
    $role_str = $_POST['role'] ?? '';
    
    // Map rôle texte vers role_id
    $role_map = [
        'chauffeur' => 1,
        'passager' => 2,
        'combo' => 3,
        'les_deux' => 3 // si possible alias
    ];
    $role_id = $role_map[$role_str] ?? 0;
    
    // Validation basique
    if (!$pseudo || !$email || !$password || !in_array($role_id, [1, 2, 3])) {
        die('Tous les champs sont obligatoires et rôle invalide.');
    }

    // Vérifier si pseudo ou email existe déjà
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE pseudo = :pseudo OR email = :email");
    $stmtCheck->bindValue(':pseudo', $pseudo, PDO::PARAM_STR);
    $stmtCheck->bindValue(':email', $email, PDO::PARAM_STR);
    $stmtCheck->execute();
    if ($stmtCheck->fetchColumn() > 0) {
        die('Pseudo ou email déjà utilisé.');
    }

    // Générer un UUID (v4)
    function generateUUIDv4() {
        $data = random_bytes(16);
        // Met les bits selon RFC 4122 pour UUID v4
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    $uuid = generateUUIDv4();

    // Hasher le mot de passe avec BCRYPT
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Colonnes optionnelles - valeurs par défaut (modifiable selon besoin)
    $nom = '';
    $prenom = '';
    $telephone = '';
    $adresse = '';
    $date_naissance = null; // NULL pour champ DATE nullable

    try {
        $pdo->beginTransaction();

        // Insert utilisateur complet
        $stmtInsertUser = $pdo->prepare("
            INSERT INTO utilisateurs (UUID, pseudo, email, PASSWORD, credits, nom, prenom, telephone, adresse, date_naissance)
            VALUES (:uuid, :pseudo, :email, :password, :credits, :nom, :prenom, :telephone, :adresse, :date_naissance)
        ");
        $stmtInsertUser->bindValue(':uuid', $uuid, PDO::PARAM_STR);
        $stmtInsertUser->bindValue(':pseudo', $pseudo, PDO::PARAM_STR);
        $stmtInsertUser->bindValue(':email', $email, PDO::PARAM_STR);
        $stmtInsertUser->bindValue(':password', $passwordHash, PDO::PARAM_STR);
        $stmtInsertUser->bindValue(':credits', 20, PDO::PARAM_INT);
        $stmtInsertUser->bindValue(':nom', $nom, PDO::PARAM_STR);
        $stmtInsertUser->bindValue(':prenom', $prenom, PDO::PARAM_STR);
        $stmtInsertUser->bindValue(':telephone', $telephone, PDO::PARAM_STR);
        $stmtInsertUser->bindValue(':adresse', $adresse, PDO::PARAM_STR);
        $stmtInsertUser->bindValue(':date_naissance', $date_naissance, PDO::PARAM_NULL);
        $stmtInsertUser->execute();

        $userId = $pdo->lastInsertId();

        // Insert rôle
        $stmtInsertRole = $pdo->prepare("
            INSERT INTO utilisateur_roles (utilisateur_id, role_id) VALUES (:user_id, :role_id)
        ");
        $stmtInsertRole->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmtInsertRole->bindValue(':role_id', $role_id, PDO::PARAM_INT);
        $stmtInsertRole->execute();

        $pdo->commit();

        // Connexion automatique
        $_SESSION['user_id'] = $userId;
        $_SESSION['pseudo'] = $pseudo;
        $_SESSION['credits'] = 20;
        $_SESSION['role_id'] = $role_id;

        header('Location: /back/public/index.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die('Erreur lors de l\'inscription : ' . $e->getMessage());
    }
} else {
    die('Méthode non autorisée');
}