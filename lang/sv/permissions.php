<?php

return [
    'device' => [
        'title' => 'Enheter',
        'viewAll' => ['label' => 'Visa alla enheter', 'description' => 'Visa alla enheter'],
        'view' => ['label' => 'Visa enhetsinformation', 'description' => 'Visa de enheter som användaren har åtkomst till'],
        'create' => ['label' => 'Lägg till enheter', 'description' => 'Lägg till nya enheter i LibreNMS'],
        'update' => ['label' => 'Redigera enheter', 'description' => 'Ändra enhetsinställningar'],
        'delete' => ['label' => 'Ta bort enheter', 'description' => 'Ta bort enheter från LibreNMS'],
        'debug' => ['label' => 'Felsöka enheter', 'description' => 'Kör snmpwalk och andra felsökningskommandon mot enheter'],
        'updateNotes' => ['label' => 'Uppdatera enhetsanteckningar', 'description' => 'Uppdatera enhetsanteckningar'],
    ],

    'config-backup' => [
        'title' => 'Konfigurationssäkerhetskopiering',
        'view' => ['label' => 'Visa enhetskonfiguration', 'description' => 'Visa säkerhetskopior av enheternas konfiguration'],
        'refresh' => ['label' => 'Uppdatera enhetskonfiguration', 'description' => 'Utlös en säkerhetskopiering av enhetens konfiguration på begäran'],
    ],

    'alert' => [
        'title' => 'Larm',
        'viewAll' => ['label' => 'Visa alla larm', 'description' => 'Visa alla larm'],
        'view' => ['label' => 'Visa larminformation', 'description' => 'Visa larm för de enheter som användaren har åtkomst till'],
        'detail' => ['label' => 'Visa larminformation', 'description' => 'Visa detaljerad information om larm'],
        'update' => ['label' => 'Redigera larm', 'description' => 'Kvittera eller ändra larm'],
        'delete' => ['label' => 'Ta bort larm', 'description' => 'Ta bort larmhistorik'],
    ],

    'alert-rule' => [
        'title' => 'Larmregler',
        'viewAll' => ['label' => 'Visa alla larmregler', 'description' => 'Visa alla larmregler'],
        'view' => ['label' => 'Visa larmregel', 'description' => 'Visa information om larmregler för de enheter som användaren har åtkomst till'],
        'create' => ['label' => 'Skapa larmregler', 'description' => 'Skapa nya larmregler'],
        'update' => ['label' => 'Redigera larmregler', 'description' => 'Ändra befintliga larmregler'],
        'delete' => ['label' => 'Ta bort larmregler', 'description' => 'Ta bort larmregler'],
    ],

    'alert-schedule' => [
        'title' => 'Larmscheman',
        'view' => ['label' => 'Visa larmschema', 'description' => 'Visa information om larmscheman'],
        'create' => ['label' => 'Skapa larmscheman', 'description' => 'Skapa nya larmscheman'],
        'update' => ['label' => 'Redigera larmscheman', 'description' => 'Ändra befintliga larmscheman'],
        'delete' => ['label' => 'Ta bort larmscheman', 'description' => 'Ta bort larmscheman'],
    ],

    'alert-template' => [
        'title' => 'Larmmallar',
        'view' => ['label' => 'Visa larmmallar', 'description' => 'Visa larmmallar'],
        'create' => ['label' => 'Skapa larmmallar', 'description' => 'Skapa nya larmmallar'],
        'update' => ['label' => 'Redigera larmmallar', 'description' => 'Ändra befintliga larmmallar'],
        'delete' => ['label' => 'Ta bort larmmallar', 'description' => 'Ta bort larmmallar'],
    ],

    'alert-transport' => [
        'title' => 'Larmtransporter',
        'view' => ['label' => 'Visa larmtransporter', 'description' => 'Visa larmtransporter'],
        'create' => ['label' => 'Skapa larmtransporter', 'description' => 'Skapa nya larmtransporter'],
        'update' => ['label' => 'Redigera larmtransporter', 'description' => 'Ändra befintliga larmtransporter'],
        'delete' => ['label' => 'Ta bort larmtransporter', 'description' => 'Ta bort larmtransporter'],
    ],

    'api' => [
        'title' => 'API-åtkomst',
        'access' => ['label' => 'API-åtkomst', 'description' => 'Använda LibreNMS REST-API'],
    ],

    'application' => [
        'title' => 'Applikationer',
        'update' => ['label' => 'Uppdatera applikation', 'description' => 'Uppdatera applikationsdata'],
    ],

    'auth-log' => [
        'title' => 'Autentiseringsloggar',
        'view' => ['label' => 'Visa autentiseringsloggar', 'description' => 'Visa autentiseringsloggar'],
    ],

    'bill' => [
        'title' => 'Fakturor',
        'viewAll' => ['label' => 'Visa alla fakturor', 'description' => 'Visa alla faktureringsposter'],
        'view' => ['label' => 'Visa fakturainformation', 'description' => 'Visa faktureringsinformation och grafer för de fakturor som användaren har åtkomst till'],
        'create' => ['label' => 'Skapa fakturor', 'description' => 'Skapa nya faktureringsposter'],
        'update' => ['label' => 'Redigera fakturor', 'description' => 'Ändra faktureringsinställningar'],
        'delete' => ['label' => 'Ta bort fakturor', 'description' => 'Ta bort faktureringsposter'],
    ],

    'component' => [
        'title' => 'Komponenter',
        'update' => ['label' => 'Uppdatera komponent', 'description' => 'Uppdatera komponentdata'],
    ],

    'custom-map' => [
        'title' => 'Kartor',
        'viewAll' => ['label' => 'Visa alla kartor', 'description' => 'Visa alla nätverkskartor'],
        'view' => ['label' => 'Visa karta', 'description' => 'Visa nätverkskartor som innehåller enheter som användaren har åtkomst till'],
        'create' => ['label' => 'Skapa kartor', 'description' => 'Skapa nya nätverkskartor'],
        'update' => ['label' => 'Redigera kartor', 'description' => 'Ändra befintliga nätverkskartor'],
        'delete' => ['label' => 'Ta bort kartor', 'description' => 'Ta bort nätverkskartor'],
    ],

    'dashboard' => [
        'title' => 'Instrumentpaneler',
        'copy' => ['label' => 'Kopiera instrumentpanel', 'description' => 'Kopiera instrumentpaneler från andra användare'],
    ],

    'device-group' => [
        'title' => 'Enhetsgrupper',
        'viewAll' => ['label' => 'Visa alla enhetsgrupper', 'description' => 'Visa alla enhetsgrupper'],
        'view' => ['label' => 'Visa enhetsgrupp', 'description' => 'Visa enhetsgrupper som innehåller enheter som användaren har åtkomst till'],
        'create' => ['label' => 'Skapa enhetsgrupper', 'description' => 'Skapa nya enhetsgrupper'],
        'update' => ['label' => 'Redigera enhetsgrupper', 'description' => 'Ändra befintliga enhetsgrupper'],
        'delete' => ['label' => 'Ta bort enhetsgrupper', 'description' => 'Ta bort enhetsgrupper'],
    ],

    'link' => [
        'title' => 'Länkar',
        'viewAll' => ['label' => 'Visa alla länkar', 'description' => 'Visa information om nätverkslänkar'],
    ],

    'location' => [
        'title' => 'Platser',
        'viewAll' => ['label' => 'Visa alla platser', 'description' => 'Visa alla platser'],
        'view' => ['label' => 'Visa plats', 'description' => 'Visa platser som hör till enheter som användaren har åtkomst till'],
        'create' => ['label' => 'Skapa platser', 'description' => 'Skapa nya platser'],
        'update' => ['label' => 'Redigera platser', 'description' => 'Ändra befintliga platser'],
        'delete' => ['label' => 'Ta bort platser', 'description' => 'Ta bort platser'],
    ],

    'mempool' => [
        'title' => 'Minnespooler',
        'update' => ['label' => 'Uppdatera minnespool', 'description' => 'Uppdatera data om minnespooler'],
    ],

    'notification' => [
        'title' => 'Aviseringar',
        'create' => ['label' => 'Skapa aviseringar', 'description' => 'Skapa nya aviseringar'],
        'update' => ['label' => 'Redigera aviseringar', 'description' => 'Ändra befintliga aviseringar'],
    ],

    'oxidized' => [
        'title' => 'Oxidized',
        'list' => ['label' => 'Lista enheter för Oxidized', 'description' => 'Låt Oxidized hämta sin enhetslista via API:et'],
        'search' => ['label' => 'Sök i Oxidized', 'description' => 'Sök i Oxidized säkerhetskopior av konfigurationer'],
    ],

    'peering-db' => [
        'title' => 'PeeringDB',
        'view' => ['label' => 'Visa PeeringDB', 'description' => 'Visa information från PeeringDB'],
    ],

    'plugin' => [
        'title' => 'Insticksprogram',
        'admin' => ['label' => 'Hantera insticksprogram', 'description' => 'Hantera insticksprogrammens inställningar och status'],
    ],

    'poller' => [
        'title' => 'Pollrar',
        'view' => ['label' => 'Visa pollrar', 'description' => 'Visa information och status för pollrar'],
        'update' => ['label' => 'Redigera pollrar', 'description' => 'Ändra pollerinställningar'],
        'delete' => ['label' => 'Ta bort pollrar', 'description' => 'Ta bort pollrar från LibreNMS'],
    ],

    'poller-group' => [
        'title' => 'Pollergrupper',
        'create' => ['label' => 'Skapa pollergrupper', 'description' => 'Skapa nya pollergrupper'],
        'update' => ['label' => 'Redigera pollergrupper', 'description' => 'Ändra befintliga pollergrupper'],
        'delete' => ['label' => 'Ta bort pollergrupper', 'description' => 'Ta bort pollergrupper'],
    ],

    'port' => [
        'title' => 'Portar',
        'viewAll' => ['label' => 'Visa alla portar', 'description' => 'Visa alla portar'],
        'view' => ['label' => 'Visa portinformation', 'description' => 'Visa portar på de enheter eller portar som användaren har åtkomst till'],
        'update' => ['label' => 'Redigera portar', 'description' => 'Ändra portbeskrivningar och portinställningar'],
        'delete' => ['label' => 'Ta bort portar', 'description' => 'Ta bort portar och deras data permanent'],
    ],

    'port-group' => [
        'title' => 'Portgrupper',
        'viewAll' => ['label' => 'Visa alla portgrupper', 'description' => 'Visa alla portgrupper'],
        'view' => ['label' => 'Visa portgrupp', 'description' => 'Visa portgrupper som innehåller portar som användaren har åtkomst till'],
        'create' => ['label' => 'Skapa portgrupper', 'description' => 'Skapa nya portgrupper'],
        'update' => ['label' => 'Redigera portgrupper', 'description' => 'Ändra befintliga portgrupper'],
        'delete' => ['label' => 'Ta bort portgrupper', 'description' => 'Ta bort portgrupper'],
    ],

    'processor' => [
        'title' => 'Processorer',
        'viewAll' => ['label' => 'Visa alla processorer', 'description' => 'Visa alla processorer'],
        'view' => ['label' => 'Visa processor', 'description' => 'Visa processorer för de enheter som användaren har åtkomst till'],
        'update' => ['label' => 'Uppdatera processor', 'description' => 'Uppdatera processordata'],
    ],

    'reporting' => [
        'title' => 'Rapportering',
        'update' => ['label' => 'Uppdatera rapportering', 'description' => 'Uppdatera inställningar för rapportering'],
    ],

    'role' => [
        'title' => 'Roller',
        'update' => ['label' => 'Redigera roller', 'description' => 'Ändra rollernas behörigheter och inställningar'],
    ],

    'routing' => [
        'title' => 'Routing',
        'viewAll' => ['label' => 'Visa all routing', 'description' => 'Visa all routinginformation'],
        'view' => ['label' => 'Visa routing', 'description' => 'Visa specifik routinginformation'],
        'update' => ['label' => 'Uppdatera routing', 'description' => 'Uppdatera routingdata'],
    ],

    'service' => [
        'title' => 'Tjänster',
        'viewAll' => ['label' => 'Visa alla tjänster', 'description' => 'Visa alla tjänster'],
        'view' => ['label' => 'Visa tjänster', 'description' => 'Visa tjänster för de enheter som användaren har åtkomst till'],
        'create' => ['label' => 'Lägg till tjänster', 'description' => 'Lägg till nya tjänster på enheter'],
        'update' => ['label' => 'Redigera tjänster', 'description' => 'Ändra inställningar för tjänstekontroller'],
        'delete' => ['label' => 'Ta bort tjänster', 'description' => 'Ta bort tjänster från enheter'],
    ],

    'service-template' => [
        'title' => 'Tjänstemallar',
        'view' => ['label' => 'Visa tjänstemallar', 'description' => 'Visa tjänstemallar'],
        'create' => ['label' => 'Skapa tjänstemallar', 'description' => 'Skapa nya tjänstemallar'],
        'update' => ['label' => 'Redigera tjänstemallar', 'description' => 'Ändra befintliga tjänstemallar'],
        'delete' => ['label' => 'Ta bort tjänstemallar', 'description' => 'Ta bort tjänstemallar'],
    ],

    'settings' => [
        'title' => 'Inställningar',
        'view' => ['label' => 'Visa inställningar', 'description' => 'Visa globala LibreNMS-inställningar'],
        'update' => ['label' => 'Redigera inställningar', 'description' => 'Ändra globala LibreNMS-inställningar'],
    ],

    'syslog' => [
        'title' => 'Syslog',
        'delete' => ['label' => 'Ta bort syslog', 'description' => 'Ta bort syslog-historik'],
    ],

    'user' => [
        'title' => 'Användare',
        'view' => ['label' => 'Visa användare', 'description' => 'Visa information om användarkonton'],
        'create' => ['label' => 'Skapa användare', 'description' => 'Skapa nya användarkonton'],
        'update' => ['label' => 'Redigera användare', 'description' => 'Ändra användarkonton, roller och behörigheter'],
        'delete' => ['label' => 'Ta bort användare', 'description' => 'Ta bort användarkonton'],
        'manage' => ['label' => 'Hantera behörigheter', 'description' => 'Hantera användarbehörigheter'],
        'updatePassword' => ['label' => 'Uppdatera lösenord', 'description' => 'Uppdatera användarens lösenord'],
    ],

    'vlan' => [
        'title' => 'VLAN',
        'viewAll' => ['label' => 'Visa alla VLAN', 'description' => 'Visa all VLAN-information'],
    ],

    'vminfo' => [
        'title' => 'Virtuella maskiner',
        'viewAll' => ['label' => 'Visa alla virtuella maskiner', 'description' => 'Visa all information om virtuella maskiner'],
        'view' => ['label' => 'Visa virtuell maskin', 'description' => 'Visa information om virtuella maskiner för de enheter som användaren har åtkomst till'],
        'update' => ['label' => 'Uppdatera virtuell maskin', 'description' => 'Uppdatera data om virtuella maskiner'],
    ],

    'wireless-sensor' => [
        'title' => 'Trådlösa sensorer',
        'update' => ['label' => 'Uppdatera trådlös sensor', 'description' => 'Uppdatera data från trådlösa sensorer'],
        'delete' => ['label' => 'Ta bort trådlös sensor', 'description' => 'Ta bort data från trådlösa sensorer'],
    ],

    'customoid' => [
        'title' => 'Egna OID:er',
        'view' => ['label' => 'Visa egna OID:er', 'description' => 'Visa data för egna OID:er'],
        'create' => ['label' => 'Skapa egna OID:er', 'description' => 'Skapa nya egna OID:er'],
        'update' => ['label' => 'Redigera egna OID:er', 'description' => 'Ändra befintliga egna OID:er'],
        'delete' => ['label' => 'Ta bort egna OID:er', 'description' => 'Ta bort egna OID:er'],
    ],

    'rbac' => [
        'title' => 'Roller och behörigheter',
        'beta_warning_title' => 'Betafunktion',
        'beta_warning_message' => 'Det här är en betafunktion. Behörigheter kan tillämpas felaktigt. Rapportera gärna de problem du hittar.',
        'manage_users' => 'Hantera användare',
        'manage_roles' => 'Hantera roller',
        'add_role' => 'Lägg till roll',
        'create_role' => 'Skapa roll',
        'create_new_role' => 'Skapa ny roll',
        'edit_role' => 'Redigera roll',
        'delete_role' => 'Ta bort roll',
        'role_name' => 'Rollnamn',
        'permissions' => 'Behörigheter',
        'actions' => 'Åtgärder',
        'all_permissions' => 'Alla behörigheter',
        'view_all_permissions' => 'Visa alla behörigheter',
        'view_permissions' => 'Visa behörigheter',
        'no_permissions' => 'Inga behörigheter tilldelade',
        'confirm_delete' => 'Är du säker på att du vill ta bort den här rollen?',
        'role_name_placeholder' => 't.ex. network-engineer',
        'search_permissions' => 'Sök behörigheter ...',
        'select_all' => 'Markera alla',
        'clear_all' => 'Avmarkera alla',
        'save_role' => 'Spara roll',
        'update_role' => 'Uppdatera roll',
        'created' => 'Rollen :name har skapats',
        'updated' => 'Rollen :name har uppdaterats',
        'deleted' => 'Rollen :name har tagits bort',
        'role_name_regex' => 'Rollnamn får endast innehålla små bokstäver och bindestreck (-).',
    ],
    'permissions' => [
        'user_permissons' => 'Behörigheter för :name',
        'bill_access' => 'Fakturaåtkomst (:count)',
        'device_access' => 'Enhetsåtkomst (:count)',
        'device_group_access' => 'Åtkomst till enhetsgrupper (:count)',
        'port_access' => 'Portåtkomst (:count)',
        'bill_all' => 'Alla fakturor',
        'device_all' => 'Alla enheter',
        'device_group_all' => 'Alla enhetsgrupper',
        'port_all' => 'Alla portar',
        'none_configured' => 'Inga konfigurerade',
    ],
];
