<?php
// Copiez ce fichier vers config/resend.php et renseignez votre propre clé API Resend.
// config/resend.php est ignoré par git (voir .gitignore) : ne committez jamais de vraie clé API.

return [
    // Clé API Resend (https://resend.com/api-keys)
    'api_key' => 'CHANGE_ME',

    // Adresse d'expédition. Sans domaine vérifié sur Resend, seule
    // 'onboarding@resend.dev' fonctionne, et uniquement vers l'adresse
    // e-mail associée à votre compte Resend (mode bac à sable).
    'from' => 'USFAH Soutenance <onboarding@resend.dev>',

    // Active/désactive l'envoi réel (permet de développer sans consommer le quota)
    'enabled' => true,
];
