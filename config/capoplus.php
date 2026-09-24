<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Limite maximale sur les dépôts (VULN-14)
    |--------------------------------------------------------------------------
    |
    | Aucune limite de dépôt n'a été formellement définie dans le cahier des
    | charges initial. Pour éviter d'inventer une règle métier arbitraire, ce
    | plafond est désactivé par défaut (null).
    |
    | La direction et la politique métier peuvent activer et ajuster ce seuil
    | à tout moment via la variable d'environnement CAPOPLUS_MAX_DEPOSIT.
    |
    */
    'max_deposit' => env('CAPOPLUS_MAX_DEPOSIT', null),
];
