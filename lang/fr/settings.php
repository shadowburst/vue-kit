<?php

declare(strict_types=1);

return [
    'title' => 'Paramètres',
    'description' => 'Gérez votre profil et les paramètres de votre compte',
    'language' => 'Langue',
    'language_settings' => 'Paramètres de langue',
    'language_description' => 'Choisissez votre langue préférée.',
    'save' => 'Enregistrer',

    'navigation' => [
        'profile' => 'Profil',
        'security' => 'Sécurité',
        'appearance' => 'Apparence',
        'language' => 'Langue',
    ],

    'profile' => [
        'title' => 'Paramètres du profil',
        'information' => 'Informations du profil',
        'description' => 'Modifiez votre nom et votre adresse e-mail',
        'full_name' => 'Nom complet',
        'email_unverified' => 'Votre adresse e-mail n\'est pas vérifiée.',
        'resend_verification' => 'Cliquez ici pour renvoyer l\'e-mail de vérification.',
        'verification_sent' => 'Un nouveau lien de vérification a été envoyé à votre adresse e-mail.',
        'member_since' => 'Membre depuis :date',
    ],

    'appearance' => [
        'title' => 'Paramètres d\'apparence',
        'description' => 'Modifiez les paramètres d\'apparence de votre compte',
    ],

    'security' => [
        'title' => 'Paramètres de sécurité',
        'update_password' => 'Modifier le mot de passe',
        'password_description' => 'Assurez-vous que votre compte utilise un mot de passe long, aléatoire et sécurisé',
        'save_password' => 'Enregistrer le mot de passe',
        'two_factor_title' => 'Authentification à deux facteurs',
        'two_factor_description' => 'Gérez vos paramètres d\'authentification à deux facteurs',
        'two_factor_disabled_body' => 'Lorsque vous activez l\'authentification à deux facteurs, un code sécurisé vous sera demandé lors de la connexion. Ce code peut être récupéré depuis une application compatible TOTP sur votre téléphone.',
        'two_factor_enabled_body' => 'Un code sécurisé et aléatoire vous sera demandé lors de la connexion. Vous pouvez le récupérer depuis l\'application compatible TOTP sur votre téléphone.',
        'continue_setup' => 'Continuer la configuration',
        'enable_two_factor' => 'Activer la 2FA',
        'disable_two_factor' => 'Désactiver la 2FA',
    ],

    'delete_account' => [
        'title' => 'Supprimer le compte',
        'description' => 'Supprimer votre compte et toutes ses ressources',
        'warning' => 'Avertissement',
        'warning_body' => 'Veuillez continuer avec prudence, cette action est irréversible.',
        'confirm_title' => 'Voulez-vous vraiment supprimer votre compte ?',
        'confirm_description' => 'Une fois votre compte supprimé, toutes ses ressources et données seront également supprimées définitivement. Veuillez saisir votre mot de passe pour confirmer la suppression définitive de votre compte.',
    ],

    'attributes' => [
        'locale' => 'langue',
        'current_password' => 'mot de passe actuel',
        'password' => 'mot de passe',
        'new_password' => 'nouveau mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'name' => 'nom',
        'email' => 'adresse e-mail',
    ],
];
