<?php
// back/public/config.php

$host = $_SERVER['HTTP_HOST'];

if ($host === 'localhost' || str_contains($host, '127.0.0.1')) {
    define('BASE_URL', '/back/public'); // adapte selon ton chemin local
} else {
    define('BASE_URL', ''); // sur AlwaysData, ton dossier cible est déjà public
}

