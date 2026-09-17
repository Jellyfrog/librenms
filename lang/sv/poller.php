<?php

return [
    'settings' => [
        'settings' => [
            'poller_groups' => [
                'description' => 'Tilldelade grupper',
                'help' => 'Den här noden utför endast åtgärder på enheter i dessa pollergrupper.',
            ],
            'poller_enabled' => [
                'description' => 'Poller aktiverad',
                'help' => 'Aktivera pollerprocesser på den här noden.',
            ],
            'poller_workers' => [
                'description' => 'Pollerprocesser',
                'help' => 'Antal pollerprocesser som ska startas på den här noden.',
            ],
            'poller_frequency' => [
                'description' => 'Pollerfrekvens (varning!)',
                'help' => 'Hur ofta enheter ska pollas på den här noden. Varning! Om du ändrar detta utan att åtgärda RRD-filerna slutar graferna att fungera. Se dokumentationen för mer information.',
            ],
            'poller_down_retry' => [
                'description' => 'Nytt försök när enheten är nere',
                'help' => 'Hur länge systemet ska vänta innan ett nytt försök görs när en enhet är nere vid pollningen på den här noden.',
            ],
            'discovery_enabled' => [
                'description' => 'Upptäckt aktiverad',
                'help' => 'Aktivera upptäcktsprocesser på den här noden.',
            ],
            'discovery_workers' => [
                'description' => 'Upptäcktsprocesser',
                'help' => 'Antal upptäcktsprocesser som ska köras på den här noden. Ett för högt värde kan orsaka överbelastning.',
            ],
            'discovery_frequency' => [
                'description' => 'Upptäcktsfrekvens',
                'help' => 'Hur ofta enhetsupptäckt ska köras på den här noden. Standard är 4 gånger per dygn.',
            ],
            'services_enabled' => [
                'description' => 'Tjänster aktiverade',
                'help' => 'Aktivera tjänsteprocesser på den här noden.',
            ],
            'services_workers' => [
                'description' => 'Tjänsteprocesser',
                'help' => 'Antal tjänsteprocesser på den här noden.',
            ],
            'services_frequency' => [
                'description' => 'Tjänstefrekvens',
                'help' => 'Hur ofta tjänster ska köras på den här noden. Detta måste stämma överens med pollerfrekvensen.',
            ],
            'billing_enabled' => [
                'description' => 'Fakturering aktiverad',
                'help' => 'Aktivera faktureringsprocesser på den här noden.',
            ],
            'billing_frequency' => [
                'description' => 'Faktureringsfrekvens',
                'help' => 'Hur ofta faktureringsdata ska samlas in på den här noden.',
            ],
            'billing_calculate_frequency' => [
                'description' => 'Frekvens för faktureringsberäkning',
                'help' => 'Hur ofta fakturaunderlaget ska beräknas på den här noden.',
            ],
            'alerting_enabled' => [
                'description' => 'Larm aktiverade',
                'help' => 'Aktivera larmprocessen på den här noden.',
            ],
            'alerting_frequency' => [
                'description' => 'Larmfrekvens',
                'help' => 'Hur ofta larmreglerna ska kontrolleras på den här noden. Data uppdateras endast med pollerfrekvensen.',
            ],
            'ping_enabled' => [
                'description' => 'Snabb ping aktiverad',
                'help' => 'Snabb ping pingar enheter för att kontrollera om de är uppe eller nere',
            ],
            'ping_frequency' => [
                'description' => 'Pingfrekvens',
                'help' => 'Hur ofta ping ska kontrolleras på den här noden. Varning! Om du ändrar detta måste du göra ytterligare ändringar. Se dokumentationen om snabb ping.',
            ],
            'update_enabled' => [
                'description' => 'Dagligt underhåll aktiverat',
                'help' => 'Kör underhållsskriptet daily.sh och starta om dispatcher-tjänsten efteråt.',
            ],
            'update_frequency' => [
                'description' => 'Underhållsfrekvens',
                'help' => 'Hur ofta dagligt underhåll ska köras på den här noden. Standard är 1 dygn. Ändra inte detta.',
            ],
            'loglevel' => [
                'description' => 'Loggnivå',
                'help' => 'Loggnivå för dispatcher-tjänsten.',
            ],
            'watchdog_enabled' => [
                'description' => 'Watchdog aktiverad',
                'help' => 'Watchdog övervakar loggfilen och startar om tjänsten om loggfilen inte uppdateras',
            ],
            'watchdog_log' => [
                'description' => 'Loggfil att övervaka',
                'help' => 'Standardvärdet är LibreNMS loggfil.',
            ],
        ],
        'units' => [
            'seconds' => 'Sekunder',
            'workers' => 'Processer',
        ],
    ],
];
