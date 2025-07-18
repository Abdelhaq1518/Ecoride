
<?php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    echo "Accès direct interdit.";
    exit;
}

/**
 * Supprime les doublons de trajets basés sur une clé unique construite
 * avec date, heure, adresses normalisées.
 * 
 * @param array $trajets
 * @return array
 */
function verifier_doublons(array $trajets): array {
    $uniques = [];
    $hashes = [];

    foreach ($trajets as $trajet) {
        // Vérification de la présence des clés minimales
        if (!isset($trajet['date_depart'], $trajet['heure_depart'], $trajet['adresse_depart'], $trajet['adresse_arrivee'])) {
            error_log(">>> Trajet ignoré : données manquantes.");
            continue;
        }

        // Normalisation + création de la clé unique
        $key = hash('sha256',
            $trajet['date_depart'] .
            $trajet['heure_depart'] .
            strtolower(trim($trajet['adresse_depart'])) .
            strtolower(trim($trajet['adresse_arrivee']))
        );

        // Si pas encore vu, on l'ajoute
        if (!isset($hashes[$key])) {
            $hashes[$key] = true;
            $uniques[] = $trajet;
        } else {
            $id = $trajet['id'] ?? 'inconnu';
            error_log(">>> Doublon supprimé : ID $id ({$trajet['date_depart']} {$trajet['heure_depart']})");
        }
    }

    error_log(">>> Nombre de trajets après suppression des doublons : " . count($uniques));
    return $uniques;
}


