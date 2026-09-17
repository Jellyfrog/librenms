<?php

return [
    'database_connect' => [
        'title' => 'Fel vid anslutning till databasen',
    ],
    'database_inconsistent' => [
        'title' => 'Inkonsekvent databas',
        'header' => 'Inkonsekvenser i databasen upptäcktes i samband med ett databasfel. Åtgärda dem för att fortsätta.',
    ],
    'dusk_unsafe' => [
        'title' => 'Det är inte säkert att köra Dusk i produktion',
        'message' => 'Kör ":command" för att ta bort Dusk. Om du är utvecklare, ange rätt APP_ENV.',
    ],
    'file_write_failed' => [
        'title' => 'Fel: Det gick inte att skriva till filen',
        'message' => 'Det gick inte att skriva till filen (:file). Kontrollera behörigheterna och SELinux/AppArmor.',
    ],
    'host_exists' => [
        'hostname_exists' => 'Enheten :hostname finns redan',
        'ip_exists' => 'Det går inte att lägga till :hostname, enheten :existing har redan IP-adressen :ip',
        'sysname_exists' => 'Enheten :hostname finns redan på grund av dubblerat sysName: :sysname',
    ],
    'host_name_empty' => 'Värdnamnet är tomt',
    'invalid_auth_mechanism' => [
        'title' => 'Ogiltig autentiseringsmekanism',
        'message' => 'Ingen giltig autentiseringsmekanism är konfigurerad. Kontrollera inställningen auth_mechanism.',
    ],
    'host_unreachable' => [
        'unpingable' => 'Det gick inte att pinga :hostname (:ip)',
        'unsnmpable' => 'Det gick inte att ansluta till :hostname. Kontrollera SNMP-uppgifterna och att enheten är nåbar via SNMP.',
        'unresolvable' => 'Värdnamnet kunde inte slås upp till någon IP-adress',
        'no_reply_community' => 'SNMP :version: Inget svar med communityn :credentials',
        'no_reply_credentials' => 'SNMP :version: Inget svar med uppgifterna :credentials',
    ],
    'ldap_missing' => [
        'title' => 'PHP saknar stöd för LDAP',
        'message' => 'PHP har inte stöd för LDAP. Installera eller aktivera PHP-tillägget för LDAP.',
    ],
    'maximum_execution_time_exceeded' => [
        'title' => 'Den maximala körtiden på :seconds sekund överskreds|Den maximala körtiden på :seconds sekunder överskreds',
        'message' => 'Sidladdningen överskred den maximala körtid som är konfigurerad i PHP. Öka max_execution_time i din php.ini eller förbättra serverns hårdvara.',
    ],
    'unserializable_route_cache' => [
        'title' => 'Fel orsakat av att PHP-versionerna inte stämmer överens',
        'message' => 'Den PHP-version som din webbserver kör (:web_version) stämmer inte överens med CLI-versionen (:cli_version)',
    ],
    'snmp_version_unsupported' => [
        'message' => 'SNMP-versionen ":snmpver" stöds inte, den måste vara v1, v2c eller v3',
    ],
];
