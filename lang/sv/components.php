<?php

return [
    'notification-subscription-status' => [
        'no-support' => 'Den här webbläsaren stöder inte aviseringar',
        'no-transport' => 'För att kunna aktivera webbläsaraviseringar måste en larmtransport hänvisa till den här användaren',
        'enabled' => 'Aviseringar är aktiverade för den här webbläsaren',
        'disabled' => 'Aviseringar är inaktiverade för den här webbläsaren',
        'enable' => 'Aktivera',
        'disable' => 'Inaktivera',
    ],

    'maintenance-mode' => [
        'button' => [
            'maintenance_mode' => 'Underhållsläge',
            'device_under_maintenance' => 'Enheten är under underhåll',
        ],
        'titles' => [
            'device_maintenance' => 'Enhetsunderhåll',
            'end_maintenance' => 'Avsluta underhåll',
        ],
        'confirm' => [
            'end_prompt' => 'Är du säker på att du vill avsluta underhållet för den här enheten?',
        ],
        'form' => [
            'notes_label' => 'Anteckningar:',
            'notes_placeholder' => 'Underhållsanteckningar',
            'duration_label' => 'Varaktighet:',
            'behavior_label' => 'Beteende:',
            'start_maintenance' => 'Starta underhåll',
            'end_maintenance' => 'Avsluta underhåll',
        ],
        'errors' => [
            'enable' => 'Det gick inte att sätta enheten i underhållsläge',
            'disable' => 'Det gick inte att inaktivera underhållsläget',
        ],
    ],
];
