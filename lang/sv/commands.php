<?php

return [
    'errors' => [
        'db_connect' => 'Det gick inte att ansluta till databasen. Kontrollera att databastjänsten körs och att anslutningsinställningarna stämmer.',
        'db_auth' => 'Det gick inte att ansluta till databasen. Kontrollera inloggningsuppgifterna: :error',
        'no_devices' => 'Inga enheter matchar den angivna enhetsspecifikationen',
        'no_new_devices' => 'Inga nya enheter',
    ],
    'api:token-create' => [
        'description' => 'Skapa en ny API-token för en användare',
        'arguments' => [
            'username' => 'Användaren som token ska skapas för',
        ],
        'options' => [
            'name' => 'Namn på token',
        ],
        'created' => 'Token har skapats.',
        'save-warning' => 'Spara den här token. Den visas inte igen.',
        'user-not-found' => 'Användaren \':username\' hittades inte.',
    ],
    'api:token-list' => [
        'description' => 'Lista en användares API-token',
        'arguments' => [
            'username' => 'Användaren vars token ska listas',
        ],
        'no-tokens' => 'Inga token hittades för användaren \':username\'.',
        'user-not-found' => 'Användaren \':username\' hittades inte.',
    ],
    'api:token-revoke' => [
        'description' => 'Återkalla en API-token för en användare',
        'arguments' => [
            'username' => 'Användaren som token tillhör',
            'token-id' => 'ID för den token som ska återkallas (se api:token-list)',
        ],
        'revoked' => 'Token \':name\' (ID: :id) har återkallats.',
        'token-not-found' => 'Token med ID :id hittades inte för användaren \':username\'.',
        'user-not-found' => 'Användaren \':username\' hittades inte.',
    ],
    'config:clear' => [
        'description' => 'Rensa konfigurationscachen. Den aktuella konfigurationen innehåller därefter alla ändringar som gjorts sedan den senaste fullständiga inläsningen.',
    ],
    'config:get' => [
        'description' => 'Hämta ett konfigurationsvärde',
        'arguments' => [
            'setting' => 'Inställningen vars värde ska hämtas, i punktnotation (exempel: snmp.community.0)',
        ],
        'options' => [
            'dump' => 'Skriv ut hela konfigurationen som JSON',
        ],
    ],
    'config:list' => [
        'description' => 'Lista och sök bland konfigurationsinställningar',
        'arguments' => [
            'search' => 'Sök efter en inställning på konfigurationsnamn eller beskrivning',
        ],
        'not_found' => 'Inga inställningar matchade \':search\'',
    ],
    'config:set' => [
        'description' => 'Ange ett konfigurationsvärde (eller ta bort det)',
        'arguments' => [
            'setting' => 'Inställningen som ska sättas, i punktnotation (exempel: snmp.community.0). Lägg till suffixet .+ för att lägga till i en array',
            'value' => 'Värdet som ska sättas. Om du utelämnar det tas inställningen bort.',
        ],
        'options' => [
            'ignore-checks' => 'Ignorera alla säkerhetskontroller',
        ],
        'confirm' => 'Vill du återställa :setting till standardvärdet?',
        'forget_from' => 'Vill du glömma :path från :parent?',
        'errors' => [
            'append' => 'Det går inte att lägga till i en inställning som inte är en array',
            'failed' => 'Det gick inte att sätta :setting',
            'invalid' => 'Det här är inte en giltig inställning. Kontrollera vad du har angett.',
            'invalid_os' => 'Det angivna operativsystemet (:os) finns inte',
            'nodb' => 'Databasen är inte ansluten',
            'no-validation' => 'Det går inte att sätta :setting, den saknar valideringsdefinition.',
        ],
    ],
    'db:seed' => [
        'existing_config' => 'Databasen innehåller befintliga inställningar. Vill du fortsätta?',
    ],
    'dev:check' => [
        'description' => 'Kodkontroller för LibreNMS. Utan alternativ kör kommandot alla kontroller',
        'arguments' => [
            'check' => 'Kör den angivna kontrollen :checks',
        ],
        'options' => [
            'commands' => 'Skriv bara ut kommandona, kör inte kontrollerna',
            'db' => 'Kör enhetstester som kräver en databasanslutning',
            'fail-fast' => 'Avbryt kontrollerna vid första felet',
            'full' => 'Kör fullständiga kontroller och strunta i filtret för ändrade filer',
            'module' => 'Specifik modul att köra tester mot. Medför unit, --db, --snmpsim',
            'os' => 'Specifikt OS att köra tester mot. Kan vara ett reguljärt uttryck eller en kommaseparerad lista. Medför unit, --db, --snmpsim',
            'os-modules-only' => 'Hoppa över testet av OS-identifiering när du anger ett specifikt OS. Det snabbar upp tester för ändringar som inte rör identifiering.',
            'quiet' => 'Dölj utdata om inget fel uppstår',
            'snmpsim' => 'Använd snmpsim för enhetstester',
        ],
    ],
    'dev:collect-snmprec' => [
        'description' => 'Samla in SNMP-data från en enhet till testfiler för snmpsim',
        'help' => "Samla in de OID:er som används vid upptäckt och pollning i en snmprec-fixtur.\n\n" .
            "Exempel:\n  lnms dev:collect-snmprec 123 --variant=crs317 --modules=ports,sensors\n\n" .
            'Använd -v för att visa insamlade OID:er, -vv för LibreNMS felsökningsutdata eller -vvv för fullständig felsöknings- och SNMP-utdata.',
        'arguments' => [
            'device' => 'ID, IP-adress eller värdnamn för enheten som data ska samlas in från',
        ],
        'options' => [
            'variant' => 'Obligatorisk variant av fixturen, vanligtvis enhetens modell. Ange ett tomt värde för att uttryckligen välja basfixturen',
            'modules' => 'Kommaseparerade upptäckts-/pollermoduler att samla in data för',
            'prefer-collected' => 'Använd de nyinsamlade värdena när en OID redan finns (övriga befintliga OID:er behålls)',
            'os' => 'Namnet på det OS som testdata ska sparas för (används bara om enheten är generisk)',
            'output' => 'Skriv till den här snmprec-filen i stället för till standardsökvägen för fixturer',
            'full' => 'Gå igenom hela enheten i stället för att köra upptäckts- och pollermoduler',
        ],
        'device_not_found' => 'Enheten \':device\' hittades inte.',
        'variant_required' => 'Alternativet --variant (-r) krävs för att basfixturen inte ska uppdateras av misstag. Använd --variant= för att välja den uttryckligen.',
        'variant_underscore' => 'Variantnamnet får inte innehålla understreck (_).',
        'variant_single' => 'Det går bara att samla in en variant i taget.',
        'os_required' => 'OS (-o, --os) krävs eftersom enheten är generisk.',
        'capturing_data' => 'Samlar in SNMP-data ...',
        'saved_snmprec' => 'Sparade snmprec-data i :file',
        'no_data' => 'Inga data för :file',
        'verify_private_data' => 'Kontrollera att filerna inte innehåller några privata uppgifter innan du delar dem.',
    ],
    'dev:generate-test-data' => [
        'description' => 'Generera JSON-testdata från snmpsim-inspelningar',
        'help' => "Generera om befintliga JSON-fixturer, eller återskapa fixturer uttryckligen med --variant.\n\n" .
            "Exempel:\n  lnms dev:generate-test-data routeros\n  lnms dev:generate-test-data all\n  lnms dev:generate-test-data routeros --variant=crs317,wifi --modules=ports,sensors\n\n" .
            'Använd -v för att visa utdata från upptäckt och pollning, -vv för LibreNMS felsökningsutdata eller -vvv för fullständig felsöknings- och SNMP-utdata.',
        'arguments' => [
            'os' => 'Bearbeta befintliga JSON-fixturer för det här operativsystemet, inklusive dess varianter, eller ange "all" för alla OS-fixturer',
        ],
        'options' => [
            'variant' => 'Kommaseparerade OS-varianter att bearbeta eller återskapa (kräver ett OS, ange ett tomt värde för basfixturen)',
            'modules' => 'Kommaseparerade moduler att generera om (standard: modulerna i den befintliga fixturen, eller de konfigurerade standardmodulerna med --variant)',
            'output' => 'Skriv en fixtur till den här filen, eller använd - för standardutdata',
        ],
        'scope_required' => 'Ange ett OS (eller all).',
        'variant_requires_os' => '--variant kräver ett OS.',
        'invalid_module' => 'Ogiltigt modulnamn: :module',
        'no_fixtures' => 'Inga matchande JSON-testfixturer hittades.',
        'no_fixtures_for_os' => 'Inga matchande JSON-testfixturer hittades för operativsystemet ":os".',
        'fixture_selection_note' => 'Urvalet för hela operativsystemet utgår från befintliga filer i tests/data/*.json, så snmprec-filer som bara används för identifiering ingår inte.',
        'recreate_hint' => 'Ange varianten uttryckligen med --variant för att återskapa en borttagen fixtur (använd --variant= för operativsystemets basfixtur).',
        'output_single' => '--output kan bara användas med en kombination av OS och variant.',
        'combinations_found' => 'Flera kombinationer (:count) hittades.',
        'labels' => [
            'os' => 'OS: :os',
            'variant' => 'Variant: :variant',
            'base' => '(bas)',
            'modules' => 'Moduler: :modules',
            'configured_defaults' => 'konfigurerade standardvärden',
        ],
        'progress' => [
            'generating' => 'Genererar testdata',
            'generated' => 'Genererade testdata',
            'fixtures' => '{1} :count fixtur|[2,*] :count fixturer',
            'discovering_module' => ':fixture: upptäcker :module',
            'discovered_module' => ':fixture: upptäckte :module',
            'polling_module' => ':fixture: pollar :module',
            'polled_module' => ':fixture: pollade :module',
            'discovery_complete' => ':fixture: upptäckten är klar',
            'polling_complete' => ':fixture: pollningen är klar',
        ],
        'saved_to' => 'Sparad i :file',
        'generated_count' => '{1} Genererade :count fixtur.|[2,*] Genererade :count fixturer.',
        'ready' => 'Klart för testning!',
        'waiting_for_snmpsim' => 'Väntar på att snmpsim ska starta ...',
        'snmpsim_failed' => "Det gick inte att starta snmpsim. Kontrollera att det är installerat och fungerar och att snmprec-filerna är giltiga.\n:error",
    ],
    'dev:simulate' => [
        'description' => 'Simulera enheter med hjälp av testdata',
        'arguments' => [
            'file' => 'Filnamnet (endast basnamnet) på den snmprec-fil som ska uppdateras eller läggas till i LibreNMS. Om du inte anger någon fil läggs ingen enhet till och ingen uppdateras.',
        ],
        'options' => [
            'multiple' => 'Använd community-namnet som värdnamn i stället för snmpsim',
            'remove' => 'Ta bort enheten efter stopp',
        ],
        'added' => 'Enheten :hostname (:id) har lagts till',
        'exit' => 'Ctrl-C för att stoppa',
        'removed' => 'Enheten :id har tagits bort',
        'updated' => 'Enheten :hostname (:id) har uppdaterats',
        'setup' => 'Skapar en virtuell miljö för snmpsim i :dir',
    ],
    'device:add' => [
        'description' => 'Lägg till en ny enhet',
        'arguments' => [
            'device spec' => 'Värdnamn eller IP-adress att lägga till',
        ],
        'options' => [
            'v1' => 'Använd SNMP v1',
            'v2c' => 'Använd SNMP v2c',
            'v3' => 'Använd SNMP v3',
            'display-name' => "En sträng som visas som enhetens namn. Standard är värdnamnet.\nDet kan vara en enkel mall med följande ersättningar: {{ \$hostname }}, {{ \$sysName }}, {{ \$sysName_fallback }}, {{ \$ip }}",
            'force' => 'Lägg till enheten utan att göra några säkerhetskontroller',
            'group' => 'Pollergrupp (för distribuerad pollning)',
            'ping-fallback' => 'Lägg till enheten som enbart ping om den inte svarar på SNMP',
            'port-association-mode' => 'Anger hur portar mappas. ifName rekommenderas för Linux/Unix',
            'community' => 'Community för SNMP v1 eller v2',
            'transport' => 'Transport för anslutning till enheten',
            'port' => 'Port för SNMP-transporten',
            'security-name' => 'Säkerhetsanvändarnamn för SNMPv3',
            'auth-password' => 'Autentiseringslösenord för SNMPv3',
            'auth-protocol' => 'Autentiseringsprotokoll för SNMPv3',
            'privacy-protocol' => 'Krypteringsprotokoll för SNMPv3',
            'privacy-password' => 'Krypteringslösenord för SNMPv3',
            'ping-only' => 'Lägg till en enhet som endast pingas',
            'os' => 'Endast ping: ange OS',
            'hardware' => 'Endast ping: ange hårdvara',
            'sysName' => 'Endast ping: ange sysName',
        ],
        'validation-errors' => [
            'port.between' => 'Porten måste vara 1–65535',
            'poller-group.in' => 'Den angivna pollergruppen finns inte',
        ],
        'messages' => [
            'save_failed' => 'Det gick inte att spara enheten :hostname',
            'try_force' => 'Använd alternativet --force för att hoppa över säkerhetskontrollerna',
            'added' => 'Lade till enheten :hostname (:device_id)',
        ],
    ],
    'device:discover' => [
        'description' => 'Upptäck information om befintliga enheter. Det avgör vad som pollas.',
        'arguments' => [
            'device spec' => 'Enhetsspecifikation att upptäcka: device_id, hostname, jokertecken (*), odd, even, all',
        ],
        'options' => [
            'modules' => 'Ange vilka moduler som ska köras. Lägg till en undermodul med /. Flera värden är tillåtna.',
            'os' => 'Upptäck endast enheter med angivet operativsystem',
            'type' => 'Upptäck endast enheter av angiven typ',
        ],
        'errors' => [
            'none_up' => 'Enheten var nere, det gick inte att upptäcka den.|Alla enheter var nere, det gick inte att upptäcka dem.',
            'none_actioned' => 'Inga enheter upptäcktes.',
        ],
        'actioned' => 'Upptäckte :count enheter på :time',
        'starting' => 'Startar upptäckt:',
    ],
    'device:ping' => [
        'description' => 'Pinga enheten och registrera data om svaret',
        'arguments' => [
            'device spec' => 'Enhet att pinga, en av: <enhets-ID>, <värdnamn/IP>, all, fast ("fast" pingar alla enheter och uppdaterar grafer och status)',
        ],
        'options' => [
            'groups' => 'Grupp-ID att pinga. Ange flera gånger för flera grupper. (gäller endast med fast)',
        ],
        'errors' => [
            'groups_without_fast' => 'Alternativet --groups (-g) stöds endast med enhetsspecifikationen "fast".',
        ],
    ],
    'device:poll' => [
        'description' => 'Polla data från enheter enligt vad upptäckten har definierat',
        'arguments' => [
            'device spec' => 'Enhetsspecifikation att polla: device_id, hostname, jokertecken (*), odd, even, all',
        ],
        'options' => [
            'modules' => 'Ange en enskild modul som ska köras. Separera moduler med komma. Lägg till en undermodul med /',
            'no-data' => 'Uppdatera inte datalagren (RRD, InfluxDB med flera)',
            'os' => 'Polla endast enheter med angivet operativsystem',
            'type' => 'Polla endast enheter av angiven typ',
        ],
        'errors' => [
            'none_up' => 'Enheten var nere, det gick inte att polla den.|Alla enheter var nere, det gick inte att polla dem.',
            'none_actioned' => 'Inga enheter pollades.',
        ],
        'actioned' => 'Pollade :count enheter på :time',
        'starting' => 'Startar pollningen:',
    ],
    'device:remove' => [
        'doesnt_exists' => 'Det finns ingen sådan enhet: :device',
    ],
    'key:rotate' => [
        'description' => 'Rotera APP_KEY. Kommandot dekrypterar alla krypterade data med den gamla nyckeln och lagrar dem sedan med den nya nyckeln i APP_KEY.',
        'arguments' => [
            'old_key' => 'Den gamla APP_KEY som gäller för de krypterade uppgifterna',
        ],
        'options' => [
            'generate-new-key' => 'Om den nya nyckeln inte finns i .env används APP_KEY från .env för att dekryptera data. Kommandot genererar sedan en ny nyckel och skriver in den i .env.',
            'forgot-key' => 'Om du inte har den gamla nyckeln måste du ta bort alla krypterade data. Annars kan du inte använda vissa funktioner i LibreNMS.',
        ],
        'destroy' => 'Vill du förstöra alla krypterade konfigurationsdata?',
        'destroy_confirm' => 'Förstör bara alla krypterade data om du inte kan hitta den gamla APP_KEY.',
        'cleared-cache' => 'Konfigurationen var cachad. Cachen rensades för att säkerställa att APP_KEY är korrekt. Kör lnms key:rotate igen.',
        'backup_keys' => 'Dokumentera BÅDA nycklarna. Om något går fel: skriv in den nya nyckeln i .env och använd sedan den gamla nyckeln som argument till det här kommandot.',
        'backup_key' => 'Dokumentera den här nyckeln! Den krävs för att komma åt krypterade data',
        'backups' => 'Det här kommandot kan orsaka oåterkallelig dataförlust. Det ogiltigförklarar dessutom alla webbläsarsessioner. Kontrollera att du har säkerhetskopior.',
        'confirm' => 'Jag har säkerhetskopior och vill fortsätta',
        'decrypt-failed' => 'Det gick inte att dekryptera :item. Hoppade över det.',
        'failed' => 'Det gick inte att dekryptera ett eller flera objekt. Skriv in den nya nyckeln som APP_KEY och kör det här igen med den gamla nyckeln som argument.',
        'current_key' => 'Nuvarande APP_KEY: :key',
        'new_key' => 'Ny APP_KEY: :key',
        'old_key' => 'Gammal APP_KEY: :key',
        'save_key' => 'Vill du spara den nya nyckeln i .env?',
        'success' => 'Nycklarna har roterats!',
        'validation-errors' => [
            'not_in' => ':attribute får inte vara samma som nuvarande APP_KEY',
            'required' => 'Antingen den gamla nyckeln eller --generate-new-key krävs.',
        ],
    ],
    'lnms' => [
        'validation-errors' => [
            'optionValue' => 'Det valda värdet för :option är ogiltigt. Det måste vara ett av: :values',
        ],
    ],
    'maintenance:cleanup-database' => [
        'description' => 'Rensa bort föräldralösa poster i databasen.',
    ],
    'maintenance:cleanup-networks' => [
        'delete' => 'Tar bort :count oanvända nätverk',
    ],
    'maintenance:fetch-ouis' => [
        'description' => 'Hämta MAC OUI:er och cachelagra dem för att kunna visa tillverkarnamn för MAC-adresser',
        'options' => [
            'force' => 'Ignorera inställningar eller lås som hindrar kommandot från att köras',
            'wait' => 'Vänta en slumpmässig tid. Schemaläggaren använder detta för att undvika belastningstoppar på servern.',
        ],
        'disabled' => 'Integrationen för MAC OUI är inaktiverad (:setting)',
        'enable_question' => 'Vill du aktivera integrationen för MAC OUI och schemalagd hämtning?',
        'recently_fetched' => 'MAC OUI-databasen hämtades nyligen. Hoppade över uppdateringen.',
        'waiting' => 'Uppdateringen av MAC OUI börjar om :minutes minut|Uppdateringen av MAC OUI börjar om :minutes minuter',
        'starting' => 'Lagrar MAC OUI i databasen',
        'downloading' => 'Laddar ner',
        'processing' => 'Bearbetar CSV',
        'saving' => 'Sparar resultatet',
        'success' => 'Kopplingarna mellan OUI och tillverkare har uppdaterats. :count OUI ändrades|Uppdateringen är klar. :count OUI:er ändrades',
        'error' => 'Fel vid bearbetning av MAC OUI:',
        'vendor_update' => 'Lägger till OUI :oui för :vendor',
    ],
    'maintenance:rrd-step' => [
        'description' => 'Konvertera RRD-filer så att de matchar konfigurerat steg och heartbeat',
        'arguments' => [
            'device' => 'Värdnamn, enhets-ID eller all',
        ],
        'options' => [
            'confirm' => 'Bekräfta att du har säkerhetskopierat dina RRD-filer.',
        ],
        'errors' => [
            'invalid' => 'Ogiltigt värdnamn eller enhets-ID angavs',
        ],
        'confirm_backup' => 'Bekräfta att du har säkerhetskopierat dina RRD-filer innan du fortsätter.',
        'mismatched_heartbeat' => ':file: Heartbeat stämmer inte. :ds != :hb',
        'skipping' => 'Hoppade över :file. Steget är redan :step.',
        'converting' => 'Konverterar :file:',
        'summary' => 'Konverterade: :converted  Misslyckades: :failed  Överhoppade: :skipped',
    ],
    'maintenance:cleanup-syslog' => [
        'description' => 'Rensa syslog-poster som är äldre än ett angivet antal dagar',
        'arguments' => [
            'days' => 'Antal dagar som syslog-poster ska behållas (standard: det konfigurerade värdet för syslog_purge)',
        ],
        'bad_days_input' => 'Antalet dagar måste vara numeriskt',
        'bad_days_setting' => 'Rensning av syslog är inaktiverad eftersom inställningen syslog_purge är ogiltig',
        'delete' => 'Rensade bort syslog-poster äldre än :days dagar (:count rader)',
        'disabled' => 'Rensning av syslog är inaktiverad eftersom antalet dagar är <= 0',
    ],
    'maintenance:discover-ssl-certificates' => [
        'description' => 'Upptäck SSL-certifikat på enheter (HTTPS-port 443)',
        'options' => [
            'device' => 'Enhetsspecifikation att upptäcka: device_id, hostname eller all',
        ],
        'no_devices' => 'Inga enheter hittades',
        'summary' => 'Skapade: :created, Uppdaterade: :updated, Misslyckades: :failed',
    ],
    'maintenance:refresh-ssl-certificates' => [
        'description' => 'Uppdatera certifikatdata för lagrade SSL-certifikat',
        'options' => [
            'id' => 'ID för det certifikat som ska uppdateras (utelämna för att uppdatera alla aktiverade)',
        ],
        'none' => 'Det finns inga aktiverade certifikat att uppdatera',
        'summary' => 'Uppdaterade: :refreshed, Misslyckades: :failed',
    ],
    'plugin:disable' => [
        'description' => 'Inaktivera alla insticksprogram med det angivna namnet',
        'arguments' => [
            'plugin' => 'Namnet på insticksprogrammet som ska inaktiveras, eller "all" för att inaktivera samtliga',
        ],
        'already_disabled' => 'Insticksprogrammet är redan inaktiverat',
        'disabled' => ':count insticksprogram inaktiverades',
        'failed' => 'Det gick inte att inaktivera insticksprogrammet eller insticksprogrammen',
    ],
    'plugin:enable' => [
        'description' => 'Aktivera det senaste insticksprogrammet med det angivna namnet',
        'arguments' => [
            'plugin' => 'Namnet på insticksprogrammet som ska aktiveras, eller "all" för att aktivera samtliga',
        ],
        'already_enabled' => 'Insticksprogrammet är redan aktiverat',
        'enabled' => ':count insticksprogram aktiverades',
        'failed' => 'Det gick inte att aktivera insticksprogrammet eller insticksprogrammen',
    ],
    'port:tune' => [
        'description' => 'Justera portarnas RRD-filer så att den maximala överföringshastigheten begränsas utifrån ifSpeed',
        'arguments' => [
            'device spec' => 'Enhetsspecifikation att justera: device_id, hostname, jokertecken (*), odd, even, all',
            'ifname' => 'Portens ifName att matcha. Använd all eller * som jokertecken',
        ],
        'device' => 'Enhet :device:',
        'port' => 'Justerar porten :port',
    ],
    'report:devices' => [
        'description' => 'Skriv ut data från enheter',
        'columns' => 'Databaskolumner:',
        'synthetic' => 'Ytterligare fält:',
        'counts' => 'Antal relationer:',
        'arguments' => [
            'device spec' => 'Enhetsspecifikation att polla: device_id, hostname, jokertecken (*), odd, even, all',
        ],
        'options' => [
            'list-fields' => 'Skriv ut en lista över giltiga fält',
            'fields' => 'En kommaseparerad lista över fält som ska visas. Giltiga alternativ: enheternas kolumnnamn från databasen, antal relationer (ports_count) och displayName. Används inte för JSON-utdata.',
            'output' => 'Utdataformat som data ska visas i :types',
            'no-header' => 'Lägg inte till någon rubrik',
            'relationships' => 'En kommaseparerad lista över relationer som ska tas med. Används endast för JSON-utdata.',
            'list-relationships' => 'Skriv ut en lista med beskrivningar av relationerna',
            'all-relationships' => 'Ta med alla relationer. -r, --relationships har företräde.',
            'devices-as-array' => 'Returnera utdata som en JSON-array i stället för en JSON-post per enhet och rad',
        ],
    ],
    'smokeping:generate' => [
        'args-nonsense' => 'Använd antingen --probes eller --targets',
        'config-insufficient' => 'För att generera en Smokeping-konfiguration måste du ha ställt in "smokeping.probes", "fping" och "fping6" i din konfiguration',
        'dns-fail' => 'kunde inte slås upp och uteslöts från konfigurationen',
        'description' => 'Generera en konfiguration som kan användas med Smokeping',
        'header-first' => 'Den här filen genererades automatiskt av "lnms smokeping:generate',
        'header-second' => 'Lokala ändringar kan skrivas över utan förvarning och utan säkerhetskopiering',
        'header-third' => 'Mer information finns på https://docs.librenms.org/Extensions/Smokeping/"',
        'no-devices' => 'Inga kvalificerade enheter hittades. Enheterna får inte vara inaktiverade.',
        'no-probes' => 'Minst en probe krävs.',
        'options' => [
            'probes' => 'Generera probe-listan – används för att dela upp Smokeping-konfigurationen i flera filer. Kan inte kombineras med "--targets"',
            'targets' => 'Generera mållistan – används för att dela upp Smokeping-konfigurationen i flera filer. Kan inte kombineras med "--probes"',
            'no-header' => 'Lägg inte till standardkommentaren i början av den genererade filen',
            'no-dns' => 'Hoppa över DNS-uppslagningar',
            'single-process' => 'Använd bara en enda process för Smokeping',
            'compat' => '[utfasad] Efterlikna beteendet hos gen_smokeping.php',
        ],
    ],
    'snmp:fetch' => [
        'description' => 'Kör en SNMP-fråga mot en enhet',
        'arguments' => [
            'device spec' => 'Enhetsspecifikation att polla: device_id, hostname, jokertecken (*), odd, even, all',
            'oid(s)' => 'En eller flera SNMP-OID:er att hämta. Varje OID måste anges som MIB::oid eller som en numerisk OID',
        ],
        'failed' => 'SNMP-kommandot misslyckades!',
        'numeric' => 'Numerisk',
        'oid' => 'OID',
        'options' => [
            'output' => 'Ange utdataformatet :formats',
            'numeric' => 'Numeriska OID:er',
            'depth' => 'Djup att gruppera SNMP-tabellen på. Vanligtvis samma antal som antalet objekt i tabellens index',
        ],
        'not_found' => 'Enheten hittades inte',
        'textual' => 'Textuell',
        'value' => 'Värde',
    ],
    'translation:generate' => [
        'description' => 'Generera uppdaterade JSON-språkfiler för användning i webbgränssnittet',
    ],
    'user:add' => [
        'description' => 'Lägg till en lokal användare. Du kan bara logga in med den här användaren om auth är inställt på mysql.',
        'arguments' => [
            'username' => 'Användarnamnet som användaren loggar in med',
        ],
        'options' => [
            'descr' => 'Beskrivning av användaren',
            'email' => 'E-postadress som ska användas för användaren',
            'password' => 'Lösenord för användaren. Om du inte anger det frågar kommandot efter det.',
            'full-name' => 'Användarens fullständiga namn',
            'role' => 'Ge användaren önskad roll :roles',
        ],
        'form' => [
            'username' => 'Användarnamn',
            'password' => 'Lösenord',
            'roles' => 'Välj användarroll(er)',
            'email' => 'E-post (valfritt)',
            'full-name' => 'Fullständigt namn (valfritt)',
            'descr' => 'Beskrivning (valfritt)',
        ],
        'success' => 'Användaren :username har lagts till',
        'wrong-auth' => 'Varning! Du kan inte logga in med den här användaren eftersom auth inte är inställt på MySQL.',
    ],
];
