<?php

return [
    'all_devices' => 'Alla enheter',
    'attributes' => [
        'hostname' => 'Värdnamn',
        'features' => 'OS-funktioner',
        'hardware' => 'Hårdvara',
        'icon' => 'Ikon',
        'ip' => 'IP',
        'location' => 'Plats',
        'os' => 'Enhetens OS',
        'serial' => 'Serienummer',
        'sysDescr' => 'sysDescr',
        'sysName' => 'sysName',
        'sysObjectID' => 'sysObjectID',
        'version' => 'OS-version',
        'type' => 'Enhetstyp',
    ],

    'never_polled' => 'Aldrig pollad',
    'vm_host' => 'VM-värd',
    'scheduled_maintenance' => 'Schemalagt underhåll',
    'delete_device' => 'Ta bort enhet',
    'delete' => 'Ta bort :name',
    'confirm_delete' => 'Är du säker på att du vill ta bort enheten :name?',
    'deleted' => 'Enheten :hostname har tagits bort.',
    'please_select' => 'Välj',
    'warning_monitored' => 'Varning! Detta tar bort enheten från övervakningen.',
    'warning_data' => 'Det tar även bort historiska data om den här enheten, till exempel:',
    'device_group' => 'Enhetsgrupp',
    'show_filter' => 'Visa filter',
    'show_header' => 'Visa rubrik',
    'os' => 'OS',
    'status' => 'Status',
    'status_up' => 'Uppe',
    'status_down' => 'Nere',
    'device_type' => 'Enhetstyp',
    'alerts_disabled' => 'Larm inaktiverade',

    'edit' => [
        'delete_device' => 'Ta bort enhet',
        'rediscover_title' => 'Schemalägg enheten för omedelbar omupptäckt av pollern',
        'rediscover' => 'Upptäck enheten på nytt',

        'hostname_title' => 'Ändra värdnamnet som används för namnuppslagning',
        'hostname_ip' => 'Värdnamn / IP',

        'display_title' => 'Visningsnamn för den här enheten. Håll det kort. Tillgängliga platshållare: hostname, sysName, sysName_fallback, ip (till exempel ":sysName")',
        'display_name' => 'Visningsnamn',
        'system_default' => 'Systemstandard',

        'overwrite_ip_title' => 'Använd den här IP-adressen vid pollning i stället för den som slås upp',
        'overwrite_ip' => 'Skriv över IP (använd inte)',

        'description' => 'Beskrivning',
        'type' => 'Typ',
        'static_groups' => 'Statiska grupper',

        'override_sysLocation' => 'Åsidosätt sysLocation',
        'coordinates_title' => 'Ange koordinater i formatet [latitud,longitud]',

        'override_sysContact' => 'Åsidosätt sysContact',

        'depends_on' => 'Den här enheten är beroende av',
        'none' => 'Ingen',

        'poller_group' => 'Pollergrupp',
        'poller_group_general' => 'Allmän',
        'default_poller' => '(standardpoller)',

        'disable_polling_alerting' => 'Inaktivera pollning och larm',
        'disable_alerting' => 'Inaktivera larm',

        'ignore_alert_tag' => 'Ignorera larmtagg',
        'ignore_alert_tag_title' => "Tagga enheten för att ignorera larm. Larmkontrollerna körs fortfarande.\nLarmregler kan läsa av ignoreringstaggen.\nOm larmtaggen Ignorera är aktiv matchar larmregeln inte villkoret `devices.ignore = 0` eller `macros.device = 1`.",

        'ignore_device_status' => 'Ignorera enhetens status',
        'ignore_device_status_title' => 'Tagga enheten för att ignorera status. Den visas då alltid som online.',

        'save' => 'Spara',

        'size_on_disk' => 'Storlek på disk',
        'rrd_files' => 'RRD-filer',
        'last_polled' => 'Senast pollad',
        'last_discovered' => 'Senast upptäckt',

        'rediscover_error' => 'Det gick inte att schemalägga den här enheten för omupptäckt',
    ],

    'oxidized' => [
        'connection_error' => 'Det gick inte att hämta enhetsinformationen från Oxidized',
    ],
];
