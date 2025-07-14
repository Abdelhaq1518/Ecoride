<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../dev/mailer.php';

$emailTest = 'aaitsaid78@gmail.com';
$sujet = "Test via Gmail – EcoRide";
$message = "Ceci est un test via Gmail SMTP.";

if (envoyerEmail($emailTest, $sujet, $message)) {
    echo "Mail envoyé avec succès.";
} else {
    echo " Erreur lors de l'envoi du mail. Consulte les logs PHP.";
}
