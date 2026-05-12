<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
    'password' => 'Le mot de passe fourni est incorrect.',
    'throttle' => 'Tentatives de connexion trop nombreuses. Veuillez réessayer dans :seconds secondes.',

    'login' => [
        'title' => 'Connexion',
        'layout_title' => 'Connectez-vous à votre compte',
        'description' => 'Saisissez votre e-mail et votre mot de passe pour vous connecter',
        'forgot_password' => 'Mot de passe oublié ?',
        'remember_me' => 'Se souvenir de moi',
        'no_account' => 'Vous n\'avez pas de compte ?',
        'sign_up' => 'Créer un compte',
    ],

    'register' => [
        'title' => 'Inscription',
        'layout_title' => 'Créer un compte',
        'description' => 'Saisissez vos informations pour créer votre compte',
        'submit' => 'Créer le compte',
        'already_registered' => 'Vous avez déjà un compte ?',
    ],

    'forgot_password' => [
        'title' => 'Mot de passe oublié',
        'description' => 'Saisissez votre e-mail pour recevoir un lien de réinitialisation',
        'submit' => 'Envoyer le lien de réinitialisation',
        'return_to' => 'Ou revenir à',
    ],

    'reset_password' => [
        'title' => 'Réinitialiser le mot de passe',
        'description' => 'Saisissez votre nouveau mot de passe ci-dessous',
    ],

    'confirm_password' => [
        'title' => 'Confirmer le mot de passe',
        'layout_title' => 'Confirmez votre mot de passe',
        'description' => 'Ceci est une zone sécurisée de l\'application. Veuillez confirmer votre mot de passe avant de continuer.',
    ],

    'verify_email' => [
        'title' => 'Vérification de l\'e-mail',
        'layout_title' => 'Vérifier l\'e-mail',
        'description' => 'Veuillez vérifier votre adresse e-mail en cliquant sur le lien que nous venons de vous envoyer.',
        'sent' => 'Un nouveau lien de vérification a été envoyé à l\'adresse e-mail fournie lors de votre inscription.',
        'resend' => 'Renvoyer l\'e-mail de vérification',
    ],

    'two_factor_challenge' => [
        'title' => 'Authentification à deux facteurs',
        'recovery_title' => 'Code de récupération',
        'recovery_description' => 'Confirmez l\'accès à votre compte en saisissant l\'un de vos codes de récupération d\'urgence.',
        'authentication_title' => 'Code d\'authentification',
        'authentication_description' => 'Saisissez le code fourni par votre application d\'authentification.',
        'use_authentication_code' => 'se connecter avec un code d\'authentification',
        'use_recovery_code' => 'se connecter avec un code de récupération',
        'or_you_can' => 'ou vous pouvez',
        'recovery_code_placeholder' => 'Saisir le code de récupération',
    ],

    'placeholders' => [
        'email' => 'email@example.com',
    ],

    'attributes' => [
        'email' => 'adresse e-mail',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'name' => 'nom',
        'token' => 'jeton de réinitialisation',
        'current_password' => 'mot de passe actuel',
        'new_password' => 'nouveau mot de passe',
    ],

];
