<?php

return [
    // Librenms specific
    'alpha_space' => ':attribute får endast innehålla bokstäver, siffror, understreck och mellanslag.',
    'ip_or_hostname' => ':attribute måste vara en giltig IP-adress, ett giltigt nätverk eller ett giltigt värdnamn.',
    'is_regex' => ':attribute är inte ett giltigt reguljärt uttryck.',
    'array_keys_not_empty' => ':attribute innehåller tomma arraynycklar.',

    'custom' => [
        'attribute-name' => [

        ],
    ],

    'attributes' => [],

    'results' => [
        'autofix' => 'Försök att åtgärda automatiskt',
        'fix' => 'Åtgärda',
        'fixed' => 'Åtgärden är klar. Uppdatera sidan för att köra valideringarna igen.',
        'fetch_failed' => 'Det gick inte att hämta valideringsresultaten',
        'backend_failed' => 'Det gick inte att läsa in data från backend. Kör ./validate.php i konsolen för att kontrollera.',
        'invalid_fixer' => 'Ogiltig åtgärdare',
        'show_all' => 'Visa alla',
        'show_less' => 'Visa färre',
        'validate' => 'Validera',
        'validating' => 'Validerar',
        'skipped' => 'Överhoppad',
        'run' => 'Kör',
    ],
    'validations' => [
        // Display names for validation groups
        'groups' => [
            'configuration' => 'Konfiguration',
            'database' => 'Databas',
            'dependencies' => 'Beroenden',
            'disk' => 'Disk',
            'distributedpoller' => 'Distribuerad poller',
            'mail' => 'E-post',
            'php' => 'PHP',
            'poller' => 'Poller',
            'programs' => 'Program',
            'python' => 'Python',
            'rrd' => 'RRD',
            'scheduler' => 'Schemaläggare',
            'system' => 'System',
            'updates' => 'Uppdateringar',
            'user' => 'Användare',
            'webserver' => 'Webbserver',
        ],
        'rrd' => [
            'CheckRrdVersion' => [
                'fail' => 'Versionen :installed_version av rrdtool är för gammal. Lägsta version som stöds är 1.5.5.',
                'fail_config' => 'Den rrdtool_version :config_version som du har angett är för gammal. Lägsta version som stöds är 1.5.5.',
                'fix' => 'Kommentera bort eller ta bort $config[\'rrdtool_version\'] = \':version\'; i din config.php',
                'ok' => 'rrdtool-versionen är OK',
            ],
            'CheckRrdcachedConnectivity' => [
                'fail_socket' => ':socket finns inte. Anslutningstestet mot rrdcached misslyckades.',
                'fail_port' => 'Det går inte att ansluta till rrdcached-servern :server på port :port',
                'ok' => 'Ansluten till rrdcached',
            ],
            'CheckRrdDirPermissions' => [
                'fail_root' => 'Din RRD-katalog ägs av root. Byt ägare till en användare som inte är root.',
                'fail_mode' => 'Din RRD-katalog har inte rättigheterna 0775.',
                'ok' => 'rrd_dir är skrivbar',
            ],
            'CheckRrdStep' => [
                'fail' => 'Vissa RRD-filer har fel steg. :bad/:total',
                'fail_bad_files' => 'Fel vid läsning av RRD-filer. :bad/:total',
                'list_bad_step_title' => 'RRD-filer med fel steg',
                'list_bad_files_title' => 'Fel vid körning av rrdinfo på filer',
                'list_bad_step_item' => ':file: steget är :step, men det måste vara :target',
                'ok' => 'Alla :total RRD-filer har rätt steg.',
                'timeout' => 'Kontrollen av RRD-filerna tog för lång tid och hoppades över. Kör :command för att kontrollera och åtgärda alla RRD-filer.',
            ],
        ],
        'database' => [
            'CheckDatabaseConnected' => [
                'fail' => 'Det går inte att ansluta till databasen',
                'fail_connect' => 'Det går inte att ansluta till databasen. Kontrollera att databasservern körs och att anslutningsuppgifterna stämmer. Kontrollera DB_HOST, DB_PORT och DB_NAME i miljön eller i :env_file.',
                'fail_access' => 'Databasen är ansluten, men användaren saknar behörighet till den. Kör SQL-frågan för att ge behörigheter. Byt ut localhost mot det lokala värdnamnet om databasen ligger på en annan server.',
                'fail_auth' => 'Databasuppgifterna är felaktiga. Kontrollera DB_USERNAME och DB_PASSWORD i miljön eller i :env_file.',
                'ok' => 'Databasen är ansluten',
            ],
            'CheckDatabaseTableNamesCase' => [
                'fail' => 'Du har lower_case_table_names satt till 1 eller true i din MySQL-konfiguration.',
                'fix' => 'Sätt lower_case_table_names=0 i avsnittet [mysqld] i din MySQL-konfigurationsfil.',
                'ok' => 'lower_case_table_names är aktiverat',
            ],
            'CheckDatabaseServerVersion' => [
                'fail' => ':server version :min är den lägsta version som stöds från och med :date.',
                'fix' => 'Uppdatera :server till en version som stöds, :suggested rekommenderas.',
                'ok' => 'SQL-servern uppfyller minimikraven',
            ],
            'CheckMysqlEngine' => [
                'fail' => 'Vissa tabeller använder inte den rekommenderade InnoDB-motorn. Det kan orsaka problem.',
                'tables' => 'Tabeller',
                'ok' => 'MySQL-motorn är optimal',
            ],
            'CheckSqlServerTime' => [
                'fail' => "Tiden på den här servern och i MySQL-databasen stämmer inte överens\n MySQL-tid :mysql_time\n PHP-tid :php_time",
                'ok' => 'Tiden i MySQL och PHP stämmer överens',
            ],
            'CheckSchemaVersion' => [
                'fail_outdated' => 'Din databas är inaktuell!',
                'fail_legacy_outdated' => 'Ditt databasschema (:current) är äldre än det senaste (:latest).',
                'fix_legacy_outdated' => 'Kör ./daily.sh manuellt och se efter om några fel uppstår.',
                'warn_extra_migrations' => 'Ditt databasschema har extra migreringar (:migrations). Om du precis har bytt från daily-utgåvan till den stabila utgåvan ligger din databas mellan två utgåvor. Nästa utgåva löser detta.',
                'warn_legacy_newer' => 'Ditt databasschema (:current) är nyare än förväntat (:latest). Om du precis har bytt från daily-utgåvan till den stabila utgåvan ligger din databas mellan två utgåvor. Nästa utgåva löser detta.',
                'ok' => 'Databasschemat är aktuellt',
            ],
            'CheckSchemaCollation' => [
                'ok' => 'Kollationeringarna för databasen och kolumnerna är korrekta',
            ],
        ],
        'distributedpoller' => [
            'CheckDistributedPollerEnabled' => [
                'ok' => 'Inställningen för distribuerad pollning är aktiverad globalt',
                'not_enabled' => 'Du har inte aktiverat distributed_poller',
                'not_enabled_globally' => 'Du har inte aktiverat distributed_poller globalt',
            ],
            'CheckMemcached' => [
                'not_configured_host' => 'Du har inte konfigurerat distributed_poller_memcached_host',
                'not_configured_port' => 'Du har inte konfigurerat distributed_poller_memcached_port',
                'could_not_connect' => 'Det gick inte att ansluta till memcached-servern',
                'ok' => 'Anslutningen till memcached är OK',
            ],
            'CheckRrdcached' => [
                'fail' => 'Du har inte aktiverat rrdcached',
            ],
        ],
        'poller' => [
            'CheckActivePoller' => [
                'fail' => 'Pollern körs inte. Ingen poller har körts under de senaste :interval sekunderna.',
                'both_fail' => 'Både Dispatcher-tjänsten och Python-wrappern har varit aktiva nyligen. Det kan leda till dubbel pollning.',
                'ok' => 'Aktiva pollrar hittades',
            ],
            'CheckDispatcherService' => [
                'fail' => 'Inga aktiva dispatcher-noder hittades',
                'ok' => 'Dispatcher-tjänsten är aktiverad',
                'nodes_down' => 'Vissa dispatcher-noder har inte checkat in nyligen',
                'not_detected' => 'Dispatcher-tjänsten hittades inte',
                'warn' => 'Dispatcher-tjänsten har använts, men inte nyligen',
            ],
            'CheckLocking' => [
                'fail' => 'Problem med cacheservern: :message',
                'ok' => 'Låsen fungerar',
            ],
            'CheckPythonWrapper' => [
                'fail' => 'Inga aktiva pollrar som körs via Python-wrappern hittades',
                'no_pollers' => 'Inga pollrar som körs via Python-wrappern hittades',
                'cron_unread' => 'Det gick inte att läsa cron-filerna',
                'ok' => 'Python-wrappern pollar',
                'nodes_down' => 'Vissa pollernoder har inte checkat in nyligen',
                'not_detected' => 'Cron-posten för Python-wrappern saknas',
            ],
            'CheckRedis' => [
                'bad_driver' => ':driver används för låsning. Sätt CACHE_STORE=redis.',
                'ok' => 'Redis fungerar',
                'unavailable' => 'Redis är inte tillgängligt',
            ],
            'CheckSchedules' => [
                'dispatcher_poll_fast' => 'Dispatcher-tjänsten är inställd på att polla oftare än RRD-steget. Det ger onödig belastning på servern om du inte sparar till andra tidsseriedatabaser, eftersom RRD-filen inte kan lagra data med samma upplösning som pollningen.',
                'dispatcher_poll_slow' => 'Dispatcher-tjänsten är inställd på att polla mer sällan än RRD-steget. Det kan orsaka fel i graferna eftersom RRD-filen förväntar sig data oftare. Sätt service_poller_frequency till null så att pollningsfrekvensen matchar RRD-steget.',
                'no_errors' => 'Inga fel hittades i pollningsfrekvenserna.',
            ],
        ],
    ],
];
