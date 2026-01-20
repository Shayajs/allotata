<?php

/**
 * Script pour exécuter package:discover de manière sécurisée
 * Gère le cas où Laravel n'est pas encore complètement initialisé
 */

// Vérifier si artisan existe
if (!file_exists('artisan')) {
    exit(0);
}

// Essayer d'exécuter package:discover
// On capture les erreurs et on les ignore pour ne pas bloquer composer
$command = 'php artisan package:discover --ansi 2>&1';
exec($command, $output, $returnCode);

// Afficher la sortie si disponible
if (!empty($output)) {
    echo implode("\n", $output) . "\n";
}

// On sort toujours avec succès pour ne pas bloquer composer
// même si package:discover a échoué (Laravel pas encore configuré)
exit(0);
