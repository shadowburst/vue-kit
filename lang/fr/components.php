<?php

declare(strict_types=1);

return [
    'alert_error' => [
        'title' => 'Une erreur est survenue.',
    ],
    'appearance' => [
        'light' => 'Clair',
        'dark' => 'Sombre',
        'system' => 'Système',
    ],
    'navigation' => [
        'menu' => 'Menu de navigation',
        'platform' => 'Plateforme',
    ],
    'password_input' => [
        'show' => 'Afficher le mot de passe',
        'hide' => 'Masquer le mot de passe',
    ],
    'two_factor_recovery_codes' => [
        'title' => 'Codes de récupération 2FA',
        'description' => 'Les codes de récupération vous permettent de retrouver l\'accès si vous perdez votre appareil 2FA. Conservez-les dans un gestionnaire de mots de passe sécurisé.',
        'view' => 'Afficher les codes de récupération',
        'hide' => 'Masquer les codes de récupération',
        'regenerate' => 'Régénérer les codes',
        'help' => 'Chaque code de récupération ne peut être utilisé qu\'une seule fois pour accéder à votre compte et sera supprimé après utilisation. Si vous en avez besoin de plus, cliquez sur :action ci-dessus.',
    ],
    'two_factor_setup_modal' => [
        'enabled_title' => 'Authentification à deux facteurs activée',
        'enabled_description' => 'L\'authentification à deux facteurs est maintenant activée. Scannez le code QR ou saisissez la clé de configuration dans votre application d\'authentification.',
        'verify_title' => 'Vérifier le code d\'authentification',
        'verify_description' => 'Saisissez le code à 6 chiffres de votre application d\'authentification',
        'enable_title' => 'Activer l\'authentification à deux facteurs',
        'enable_description' => 'Pour terminer l\'activation de l\'authentification à deux facteurs, scannez le code QR ou saisissez la clé de configuration dans votre application d\'authentification',
        'manual_entry' => 'ou saisissez le code manuellement',
    ],
    'ui' => [
        'custom' => [
            'confirm_dialog' => [
                'title' => [
                    'default' => 'Êtes-vous sûr ?',
                    'destructive' => 'Êtes-vous sûr ?',
                ],
            ],
        ],
    ],
];
