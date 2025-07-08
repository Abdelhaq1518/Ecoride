<?php
session_start();
require_once __DIR__ . '/../includes/csrf_token.php';
require_once __DIR__ . '/../dev/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !verifyToken($_POST['csrf_token'])) {
        $_SESSION['erreur'] = "Requête non autorisée (CSRF)";
        header('Location: connexion.php');
        exit;
    }

    $pseudo = trim($_POST['pseudo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['motdepasse'] ?? '';
    $role_str = $_POST['role'] ?? '';

    $role_map = [
        'chauffeur' => 1,
        'passager' => 2,
        'combo' => 3,
        'les_deux' => 3
    ];
    $role_id = $role_map[$role_str] ?? 0;

    if (!$pseudo || !$email || !$password || !in_array($role_id, [1, 2, 3])) {
        die('Tous les champs sont obligatoires et rôle invalide.');
    }

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE pseudo = :pseudo OR email = :email");
    $stmtCheck->bindValue(':pseudo', $pseudo, PDO::PARAM_STR);
    $stmtCheck->bindValue(':email', $email, PDO::PARAM_STR);
    $stmtCheck->execute();

    if ($stmtCheck->fetchColumn() > 0) {
        die('Pseudo ou email déjà utilisé.');
    }

    function generateUUIDv4() {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    $uuid = generateUUIDv4();
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $nom = '';
    $prenom = '';
    $telephone = '';
    $adresse = '';
    $date_naissance = null;

    try {
        $pdo->beginTransaction();

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

        $stmtInsertRole = $pdo->prepare("
            INSERT INTO utilisateur_roles (utilisateur_id, role_id) VALUES (:user_id, :role_id)
        ");
        $stmtInsertRole->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmtInsertRole->bindValue(':role_id', $role_id, PDO::PARAM_INT);
        $stmtInsertRole->execute();

        $pdo->commit();

        $_SESSION['success'] = "Bienvenue chez EcoRide ! Pour bien démarrer, vous bénéficiez de 20 crédits.";
        header('Location: connexion.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die('Erreur lors de l\'inscription : ' . $e->getMessage());
    }
} else {
    die('Méthode non autorisée');
}
