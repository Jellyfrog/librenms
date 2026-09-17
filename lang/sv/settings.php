<?php

return [
    'title' => 'Inställningar',
    'readonly' => 'Anges i config.php. Ta bort värdet från config.php för att aktivera det här fältet.',
    'groups' => [
        'alerting' => 'Larm',
        'api' => 'API',
        'apps' => 'Applikationer',
        'auth' => 'Autentisering',
        'authorization' => 'Auktorisering',
        'external' => 'Externt',
        'global' => 'Globalt',
        'os' => 'OS',
        'discovery' => 'Upptäckt',
        'graphing' => 'Grafer',
        'poller' => 'Poller',
        'system' => 'System',
        'webui' => 'Webbgränssnitt',
    ],
    'sections' => [
        'alerting' => [
            'general' => ['name' => 'Allmänna larminställningar'],
            'email' => ['name' => 'E-postalternativ'],
            'rules' => ['name' => 'Standardinställningar för larmregler'],
            'scheduled-maintenance' => ['name' => 'Schemalagt underhåll'],
        ],
        'api' => [
            'cors' => ['name' => 'CORS'],
            'v1' => ['name' => 'API v1 (beta)'],
        ],
        'apps' => [
            'powerdns-recursor' => ['name' => 'PowerDNS Recursor'],
            'oslv_monitor' => ['name' => 'OSLV Monitor'],
            'sneck' => ['name' => 'Sneck'],
            'ssl-certificates' => ['name' => 'SSL-certifikat'],
        ],
        'auth' => [
            'general' => ['name' => 'Allmänna autentiseringsinställningar'],
            'ad' => ['name' => 'Active Directory-inställningar'],
            'ldap' => ['name' => 'LDAP-inställningar'],
            'radius' => ['name' => 'RADIUS-inställningar'],
            'socialite' => ['name' => 'Socialite-inställningar'],
            'http' => ['name' => 'HTTP Auth-inställningar'],
            'sso' => ['name' => 'Enkel inloggning (SSO)'],
        ],
        'authorization' => [
            'device-group' => ['name' => 'Inställningar för enhetsgrupp'],
        ],
        'discovery' => [
            'general' => ['name' => 'Allmänna upptäcktsinställningar'],
            'route' => ['name' => 'Upptäcktsmodul för rutter'],
            'discovery_modules' => ['name' => 'Upptäcktsmoduler'],
            'autodiscovery' => ['name' => 'Nätverksupptäckt'],
            'ports' => ['name' => 'Portmodul'],
            'storage' => ['name' => 'Lagringsmodul'],
            'processor' => ['name' => 'Processormodul'],
            'ipmi' => ['name' => 'IPMI-modul'],
            'sensors' => ['name' => 'Sensormodul'],
            'virtualization' => ['name' => 'Virtualiseringsmodul'],
        ],
        'external' => [
            'binaries' => ['name' => 'Sökvägar till binärer'],
            'location' => ['name' => 'Platsinställningar'],
            'graylog' => ['name' => 'Graylog-integration'],
            'oxidized' => ['name' => 'Oxidized-integration'],
            'mac_oui' => ['name' => 'Integration för uppslagning av MAC OUI'],
            'peeringdb' => ['name' => 'PeeringDB-integration'],
            'nfsen' => ['name' => 'NfSen-integration'],
            'unix-agent' => ['name' => 'Unix-agentintegration'],
            'smokeping' => ['name' => 'Smokeping-integration'],
            'snmptrapd' => ['name' => 'Integration för SNMP-trap'],
            'rancid' => ['name' => 'RANCID-integration'],
            'collectd' => ['name' => 'Collectd-integration'],
            'unimus' => ['name' => 'Unimus-integration'],
        ],
        'poller' => [
            'availability' => ['name' => 'Enhetens tillgänglighet'],
            'distributed' => ['name' => 'Distribuerad poller'],
            'graphite' => ['name' => 'Datalager: Graphite'],
            'influxdb' => ['name' => 'Datalager: InfluxDB'],
            'influxdbv2' => ['name' => 'Datalager: InfluxDBv2'],
            'kafka' => ['name' => 'Datalager: Kafka'],
            'mtu' => ['name' => 'MTU-kontroll'],
            'opentsdb' => ['name' => 'Datalager: OpenTSDB'],
            'ping' => ['name' => 'Ping'],
            'prometheus' => ['name' => 'Datalager: Prometheus'],
            'rrdtool' => ['name' => 'Datalager: RRDTool'],
            'snmp' => ['name' => 'SNMP'],
            'dispatcherservice' => ['name' => 'Dispatcher-tjänsten'],
            'poller_modules' => ['name' => 'Pollermoduler'],
            'ports' => ['name' => 'Pollermodul för portar'],
        ],
        'system' => [
            'billing' => ['name' => 'Fakturering'],
            'cleanup' => ['name' => 'Rensning'],
            'proxy' => ['name' => 'Proxy'],
            'updates' => ['name' => 'Uppdateringar'],
            'scheduledtasks' => ['name' => 'Schemalagda uppgifter'],
            'server' => ['name' => 'Server'],
            'reporting' => ['name' => 'Rapportering'],
        ],
        'webui' => [
            'availability-map' => ['name' => 'Inställningar för tillgänglighetskarta'],
            'custom-map' => ['name' => 'Inställningar för anpassade kartor'],
            'graph' => ['name' => 'Grafinställningar'],
            'dashboard' => ['name' => 'Inställningar för instrumentpaneler'],
            'port-descr' => ['name' => 'Tolkning av gränssnittsbeskrivningar'],
            'search' => ['name' => 'Sökinställningar'],
            'style' => ['name' => 'Stil'],
            'device' => ['name' => 'Enhetsinställningar'],
            'worldmap' => ['name' => 'Inställningar för världskartan'],
            'general' => ['name' => 'Allmänna inställningar för webbgränssnittet'],
            'front-page' => ['name' => 'Inställningar för förstasidan'],
            'menu' => ['name' => 'Menyinställningar'],
            'scheduled-maintenance' => ['name' => 'Schemalagt underhåll'],
            'alert-map' => ['name' => 'Inställningar för larmkarta'],
        ],
    ],
    'settings' => [
        'active_directory' => [
            'users_purge' => [
                'description' => 'Behåll inaktiva användare i',
                'help' => 'En användare tas bort efter så här många dagar utan inloggning. Ange 0 för att aldrig ta bort användare. En borttagen användare återskapas när användaren loggar in igen.',
            ],
        ],
        'addhost_alwayscheckip' => [
            'description' => 'Kontrollera om det finns dubbletter av IP när du lägger till enheter',
            'help' => 'När en värd läggs till med en IP-adress kontrolleras att adressen inte redan finns. Om den finns läggs värden inte till. Kontrollen görs inte när en värd läggs till med värdnamn. Om inställningen är aktiverad slås värdnamnet upp och kontrollen görs även då. Detta förhindrar oavsiktliga dubbletter.',
        ],
        'alert_rule' => [
            'acknowledged_alerts' => [
                'description' => 'Kvitterade larm',
                'help' => 'Skicka larm när en användare kvitterar ett larm',
            ],
            'severity' => [
                'description' => 'Allvarlighetsgrad',
                'help' => 'Allvarlighetsgrad för ett larm',
            ],
            'default_operation_steps_to' => [
                'description' => 'Standardåtgärd: Steg till',
                'help' => 'Standardvärde för sista eskaleringssteget i skapade åtgärdsrader (-1 betyder ingen gräns)',
            ],
            'default_operation_start_in' => [
                'description' => 'Standardåtgärd: Starta efter',
                'help' => 'Standardfördröjning innan en åtgärdsavisering skickas',
            ],
            'default_operation_step_duration' => [
                'description' => 'Standardåtgärd: Steglängd',
                'help' => 'Standardlängd för åtgärdssteg (minuter)',
            ],
            'default_operation_notifications_suppressed' => [
                'description' => 'Standardåtgärd: Undertryck aviseringar',
                'help' => 'Undertryck aviseringar som standard för skapade åtgärdsrader',
            ],
            'invert_rule_match' => [
                'description' => 'Invertera regelmatchning',
                'help' => 'Larma endast om regeln inte matchar',
            ],
            'recovery_alerts' => [
                'description' => 'Återställningslarm',
                'help' => 'Avisera när ett larm återställs',
            ],
            'acknowledgement_alerts' => [
                'description' => 'Kvitteringslarm',
                'help' => 'Avisera när en användare kvitterar ett larm',
            ],
            'invert_map' => [
                'description' => 'Alla enheter utom i listan',
                'help' => 'Larma endast för enheter som inte finns i listan',
            ],
        ],
        'alert' => [
            'ack_until_clear' => [
                'description' => 'Standardalternativ: kvittera tills larmet upphör',
                'help' => 'Kvittera som standard tills larmet upphör',
            ],
            'admins' => [
                'description' => 'Skicka larm till administratörer (utfasat)',
                'help' => 'Utfasat. Använd larmtransporten för e-post i stället.',
            ],
            'default_copy' => [
                'description' => 'Kopiera alla e-postlarm till standardkontakten (utfasat)',
                'help' => 'Utfasat. Använd larmtransporten för e-post i stället.',
            ],
            'default_if_none' => [
                'description' => 'kan inte ställas in i webbgränssnittet? (utfasat)',
                'help' => 'Utfasat. Använd larmtransporten för e-post i stället.',
            ],
            'default_mail' => [
                'description' => 'Standardkontakt (utfasat)',
                'help' => 'Utfasat. Använd larmtransporten för e-post i stället.',
            ],
            'default_only' => [
                'description' => 'Skicka larm endast till standardkontakten (utfasat)',
                'help' => 'Utfasat. Använd larmtransporten för e-post i stället.',
            ],
            'disable' => [
                'description' => 'Inaktivera larm',
                'help' => 'Generera inga larm alls',
            ],
            'acknowledged' => [
                'description' => 'Skicka kvitterade larm',
                'help' => 'Avisera när en användare kvitterar ett larm',
            ],
            'fixed-contacts' => [
                'description' => 'Inaktivera kontaktändringar för aktiva larm',
                'help' => 'Om alternativet är aktiverat gäller ändringar av sysContact eller användarnas e-postadresser inte medan ett larm är aktivt',
            ],
            'globals' => [
                'description' => 'Skicka larm till skrivskyddade användare (utfasat)',
                'help' => 'Utfasat. Använd larmtransporten för e-post i stället.',
            ],
            'scheduled_maintenance_default_behavior' => [
                'description' => 'Standardbeteende för schemalagt underhåll',
                'help' => 'Standardbeteende för schemalagt underhåll',
                'options' => [
                    1 => 'Hoppa över larm',
                    2 => 'Tysta larm',
                    3 => 'Kör larm',
                ],
            ],
            'syscontact' => [
                'description' => 'Skicka larm till sysContact (utfasat)',
                'help' => 'Utfasat. Använd larmtransporten för e-post i stället.',
            ],
            'transports' => [
                'mail' => [
                    'description' => 'Aktivera e-postlarm',
                    'help' => 'Larmtransport för e-post',
                ],
            ],
            'tolerance_window' => [
                'description' => 'Toleransfönster för cron',
                'help' => 'Toleransfönster i sekunder',
            ],
            'users' => [
                'description' => 'Skicka larm till vanliga användare (utfasat)',
                'help' => 'Utfasat. Använd larmtransporten för e-post i stället.',
            ],
        ],
        'alert_log_purge' => [
            'description' => 'Larmloggposter äldre än',
            'help' => 'Rensningen görs av daily.sh',
        ],
        'discovery_on_reboot' => [
            'description' => 'Upptäckt vid omstart',
            'help' => 'Gör en upptäckt av en enhet som har startats om',
        ],
        'allow_duplicate_sysName' => [
            'description' => 'Tillåt dubblerade sysName',
            'help' => 'Som standard läggs inte en enhet med ett dubblerat sysName till. Det förhindrar flera poster för en och samma enhet med flera gränssnitt.',
        ],
        'allow_unauth_graphs' => [
            'description' => 'Tillåt oautentiserad grafåtkomst',
            'help' => 'Låt vem som helst se grafer utan att logga in',
        ],
        'allow_unauth_graphs_cidr' => [
            'description' => 'Tillåt grafåtkomst för angivna nätverk',
            'help' => 'Ge de angivna nätverken oautentiserad grafåtkomst (gäller inte när oautentiserade grafer är aktiverat)',
        ],
        'api' => [
            'cors' => [
                'allowheaders' => [
                    'description' => 'Tillåt huvuden',
                    'help' => 'Anger svarshuvudet Access-Control-Allow-Headers',
                ],
                'allowcredentials' => [
                    'description' => 'Tillåt inloggningsuppgifter',
                    'help' => 'Anger huvudet Access-Control-Allow-Credentials',
                ],
                'allowmethods' => [
                    'description' => 'Tillåtna metoder',
                    'help' => 'Matchar begärans metod.',
                ],
                'enabled' => [
                    'description' => 'Aktivera CORS-stöd för API:et',
                    'help' => 'Gör det möjligt att läsa in API-resurser från en webbklient',
                ],
                'exposeheaders' => [
                    'description' => 'Exponera huvuden',
                    'help' => 'Anger svarshuvudet Access-Control-Expose-Headers',
                ],
                'maxage' => [
                    'description' => 'Maxålder',
                    'help' => 'Anger svarshuvudet Access-Control-Max-Age',
                ],
                'origin' => [
                    'description' => 'Tillåt ursprung för begäran',
                    'help' => 'Matchar begärans ursprung. Jokertecken kan användas, t.ex. *.mydomain.com',
                ],
            ],
            'v1' => [
                'enabled' => [
                    'description' => 'Aktivera v1-API:et (beta)',
                    'help' => 'Anslut dig till det nya REST-API:et v1. När det är inaktiverat returnerar alla /api/v1-slutpunkter 404 och webbgränssnittet döljer hanteringen av v1-token.',
                ],
            ],
        ],
        'apps' => [
            'powerdns-recursor' => [
                'api-key' => [
                    'description' => 'API-nyckel för PowerDNS Recursor',
                    'help' => 'API-nyckel för PowerDNS Recursor-appen vid direktanslutning',
                ],
                'https' => [
                    'description' => 'Ska PowerDNS Recursor använda HTTPS?',
                    'help' => 'Använd HTTPS i stället för HTTP för PowerDNS Recursor-appen vid direktanslutning',
                ],
                'port' => [
                    'description' => 'PowerDNS Recursor-port',
                    'help' => 'TCP-port att använda för PowerDNS Recursor-appen vid direktanslutning',
                ],
            ],
            'oslv_monitor' => [
                'seen_age' => [
                    'description' => 'Åldersgräns för senast sedd',
                    'help' => 'Ålder i sekunder innan objekt betraktas som inaktuella',
                ],
                'linux_pg_memory_stats' => [
                    'description' => 'Minnesstatistik för Linux-sidor',
                    'help' => 'Aktivera insamling av minnesstatistik för Linux-sidor',
                ],
                'misc_linux_memory_stats' => [
                    'description' => 'Övrig minnesstatistik för Linux',
                    'help' => 'Aktivera insamling av övrig minnesstatistik för Linux',
                ],
                'zswap_size' => [
                    'description' => 'Statistik över ZSwap-storlek',
                    'help' => 'Aktivera insamling av ZSwap-storleksstatistik',
                ],
                'zswap_activity' => [
                    'description' => 'Statistik över ZSwap-aktivitet',
                    'help' => 'Aktivera insamling av ZSwap-aktivitetsstatistik',
                ],
                'workingset_stats' => [
                    'description' => 'Statistik för arbetsuppsättning',
                    'help' => 'Aktivera insamling av arbetsuppsättningsstatistik',
                ],
                'thp_activity' => [
                    'description' => 'Statistik över THP-aktivitet',
                    'help' => 'Aktivera insamling av Transparent Huge Pages-aktivitetsstatistik',
                ],
            ],
            'sneck' => [
                'polling_time_diff' => [
                    'description' => 'Skillnad i pollningstid',
                    'help' => 'Aktivera spårning av skillnad i pollningstid för Sneck',
                ],
            ],
        ],
        'astext' => [
            'description' => 'Nyckel för att lagra cachen med beskrivningar av autonoma system',
        ],
        'auth' => [
            'allow_get_login' => [
                'description' => 'Tillåt inloggning via GET (osäkert)',
                'help' => 'Tillåt inloggning med användarnamn och lösenord som variabler i URL:ens GET-begäran. Det är användbart för visningssystem där du inte kan logga in interaktivt. Det är osäkert: loggarna visar lösenordet och inloggningar har ingen hastighetsbegränsning. Det kan göra dig sårbar för brute force-attacker.',
            ],
            'socialite' => [
                'redirect' => [
                    'description' => 'Omdirigera inloggningssida',
                    'help' => 'Inloggningssidan omdirigerar omedelbart till den först definierade leverantören.<br><br>TIPS: Lägg till ?redirect=0 i webbadressen för att förhindra det.',
                ],
                'register' => [
                    'description' => 'Tillåt registrering via leverantör',
                ],
                'configs' => [
                    'description' => 'Leverantörskonfigurationer',
                ],
                'scopes' => [
                    'description' => 'Scope som ska ingå i autentiseringsbegäran',
                    'help' => 'Se https://laravel.com/docs/10.x/socialite#access-scopes',
                ],
                'default_role' => [
                    'description' => 'Standardroll',
                ],
                'claims' => [
                    'description' => 'Anspråk',
                    'help' => 'Koppla grupper till roller',
                ],
            ],
        ],
        'auth_ad_base_dn' => [
            'description' => 'Bas-DN',
            'help' => 'Grupper och användare måste ligga under detta DN. Exempel: dc=example,dc=com',
        ],
        'auth_ad_check_certificates' => [
            'description' => 'Kontrollera certifikatet',
            'help' => 'Kontrollera att certifikaten är giltiga. Vissa servrar använder självsignerade certifikat. Inaktivera inställningen för att tillåta dem.',
        ],
        'auth_ad_debug' => [
            'description' => 'Felsökning',
            'help' => 'Visa detaljerade felmeddelanden. Lämna inte detta aktiverat eftersom det kan läcka data.',
        ],
        'auth_ad_domain' => [
            'description' => 'Active Directory-domän',
            'help' => 'Active Directory-domän, exempel: example.com',
        ],
        'auth_ad_global_read' => [
            'description' => 'Global läsbehörighet',
            'help' => 'Tillåt global läsåtkomst för alla användare',
        ],
        'auth_ad_group' => [
            'description' => 'DN för åtkomstgrupp',
            'help' => 'Distinguished name för en grupp som ska ge normal åtkomstnivå. Exempel: cn=groupname,ou=groups,dc=example,dc=com',
        ],
        'auth_ad_group_filter' => [
            'description' => 'LDAP-filter för grupper',
            'help' => 'Active Directory LDAP-filter för att välja grupper',
        ],
        'auth_ad_groups' => [
            'description' => 'Gruppåtkomst',
            'help' => 'Definiera grupper som har åtkomst och nivå',
        ],
        'auth_ad_require_groupmembership' => [
            'description' => 'Kräv gruppmedlemskap',
            'help' => 'Tillåt endast användare att logga in om de ingår i en definierad grupp',
        ],
        'auth_ad_timeout' => [
            'description' => 'Timeout för anslutning',
            'help' => 'Om en eller flera servrar inte svarar gör en högre timeout inloggningarna långsamma. En för låg timeout kan orsaka anslutningsfel.',
        ],
        'auth_ad_user_filter' => [
            'description' => 'LDAP-filter för användare',
            'help' => 'Active Directory LDAP-filter för att välja användare',
        ],
        'auth_ad_url' => [
            'description' => 'Active Directory-server(ar)',
            'help' => 'Ange en eller flera servrar, separerade med mellanslag. Använd prefixet ldaps:// för SSL. Exempel: ldaps://dc1.example.com ldaps://dc2.example.com',
        ],
        'auth_ldap_attr' => [
            'uid' => [
                'description' => 'Attribut att kontrollera användarnamn mot',
                'help' => 'Attribut som används för att identifiera användare via användarnamn',
            ],
        ],
        'auth_ldap_binddn' => [
            'description' => 'Bind-DN (åsidosätter användarnamnet för bindning)',
            'help' => 'Fullständigt DN för bindningsanvändaren',
        ],
        'auth_ldap_bindpassword' => [
            'description' => 'Lösenord för bindning',
            'help' => 'Lösenord för bindningsanvändaren',
        ],
        'auth_ldap_binduser' => [
            'description' => 'Användarnamn för bindning',
            'help' => 'Används för att fråga LDAP-servern när ingen användare är inloggad (larm, API med mera)',
        ],
        'auth_ad_binddn' => [
            'description' => 'Bind-DN (åsidosätter användarnamnet för bindning)',
            'help' => 'Fullständigt DN för bindningsanvändaren',
        ],
        'auth_ad_bindpassword' => [
            'description' => 'Lösenord för bindning',
            'help' => 'Lösenord för bindningsanvändaren',
        ],
        'auth_ad_binduser' => [
            'description' => 'Användarnamn för bindning',
            'help' => 'Används för att fråga AD-servern när ingen användare är inloggad (larm, API med mera)',
        ],
        'auth_ad_starttls' => [
            'description' => 'Använd STARTTLS',
            'help' => 'Använd STARTTLS för att skydda anslutningen. Ett alternativ till LDAPS.',
            'options' => [
                'disabled' => 'Inaktiverad',
                'optional' => 'Valfri',
                'required' => 'Obligatorisk',
            ],
        ],
        'auth_ldap_cache_ttl' => [
            'description' => 'Utgångstid för LDAP-cachen',
            'help' => 'Lagrar tillfälligt resultatet av LDAP-frågor. Det förbättrar hastigheten, men data kan vara inaktuella.',
        ],
        'auth_ldap_debug' => [
            'description' => 'Visa felsökning',
            'help' => 'Visar felsökningsinformation. Det kan avslöja privata uppgifter. Lämna det inte aktiverat.',
        ],
        'auth_ldap_cacertfile' => [
            'description' => 'Åsidosätt systemets TLS CA-certifikat',
            'help' => 'Använd det angivna CA-certifikatet för LDAPS.',
        ],
        'auth_ldap_ignorecert' => [
            'description' => 'Kräv inget giltigt certifikat',
            'help' => 'Kräv inte ett giltigt TLS-certifikat för LDAPS.',
        ],
        'auth_ldap_emailattr' => [
            'description' => 'E-postattribut',
        ],
        'auth_ldap_group' => [
            'description' => 'DN för åtkomstgrupp',
            'help' => 'Distinguished name för en grupp som ska ge normal åtkomstnivå. Exempel: cn=groupname,ou=groups,dc=example,dc=com',
        ],
        'auth_ldap_groupbase' => [
            'description' => 'Bas-DN för grupper',
            'help' => 'Distinguished name att söka efter grupper i. Exempel: ou=group,dc=example,dc=com',
        ],
        'auth_ldap_groupmemberattr' => [
            'description' => 'Gruppmedlemsattribut',
        ],
        'auth_ldap_groupmembertype' => [
            'description' => 'Hitta gruppmedlemmar via',
            'options' => [
                'username' => 'Användarnamn',
                'fulldn' => 'Fullständigt DN (med prefix och suffix)',
                'puredn' => 'DN-sökning (sök med attributet uid)',
            ],
        ],
        'auth_ldap_groups' => [
            'description' => 'Gruppåtkomst',
            'help' => 'Definiera grupper som har åtkomst och nivå',
        ],
        'auth_ldap_require_groupmembership' => [
            'description' => 'Verifiering av medlemskap i LDAP-grupp',
            'help' => 'Kör ldap_compare om leverantören stöder åtgärden Compare. Hoppa över den om leverantören inte gör det.',
        ],
        'auth_ldap_port' => [
            'description' => 'LDAP-port',
            'help' => 'Port att ansluta till servrarna på. Använd 389 för LDAP och 636 för LDAPS.',
        ],
        'auth_ldap_prefix' => [
            'description' => 'Användarprefix',
            'help' => 'Används för att göra om ett användarnamn till ett distinguished name',
        ],
        'auth_ldap_server' => [
            'description' => 'LDAP-server(ar)',
            'help' => 'Ange en eller flera servrar, separerade med mellanslag. Använd prefixet ldaps:// för SSL',
        ],
        'auth_ldap_starttls' => [
            'description' => 'Använd STARTTLS',
            'help' => 'Använd STARTTLS för att skydda anslutningen. Ett alternativ till LDAPS.',
            'options' => [
                'disabled' => 'Inaktiverad',
                'optional' => 'Valfri',
                'required' => 'Obligatorisk',
            ],
        ],
        'auth_ldap_suffix' => [
            'description' => 'Användarsuffix',
            'help' => 'Används för att göra om ett användarnamn till ett distinguished name',
        ],
        'auth_ldap_timeout' => [
            'description' => 'Timeout för anslutning',
            'help' => 'Om en eller flera servrar inte svarar gör en högre timeout åtkomsten långsam. En för låg timeout kan orsaka anslutningsfel.',
        ],
        'auth_ldap_uid_attribute' => [
            'description' => 'Unikt ID-attribut',
            'help' => 'LDAP-attribut som används för att identifiera användare. Måste vara numeriskt',
        ],
        'auth_ldap_userdn' => [
            'description' => 'Använd fullständigt användar-DN',
            'help' => 'Använder en användares fullständiga DN som värde för medlemsattributet i en grupp. Alternativet är member: username, uppbyggt av prefix och suffix (till exempel member: uid=username,ou=groups,dc=domain,dc=com).',
        ],
        'auth_ldap_userlist_filter' => [
            'description' => 'Anpassat LDAP-användarfilter',
            'help' => 'Anpassat LDAP-filter som begränsar antalet svar. Använd det om din LDAP-katalog har tusentals användare.',
        ],
        'auth_ldap_wildcard_ou' => [
            'description' => 'OU med jokertecken för användare',
            'help' => 'Sök efter en användare på namn i valfri OU, inte bara den OU som anges i användarsuffixet. Använd detta om dina användare finns i olika OU:er. Användarnamnet för bindning, om det är angivet, använder fortfarande användarsuffixet.',
        ],
        'auth_ldap_version' => [
            'description' => 'LDAP-version',
            'help' => 'LDAP-version som ska användas i kommunikationen med servern. Vanligtvis måste det vara 3.',
            'options' => [
                2 => '2',
                3 => '3',
            ],
        ],
        'auth_mechanism' => [
            'description' => 'Auktoriseringsmetod (varning!)',
            'help' => 'Auktoriseringsmetod. Varning: du kan förlora möjligheten att logga in. Sätt tillbaka den till mysql genom att ange $config[\'auth_mechanism\'] = \'mysql\'; i din config.php',
            'options' => [
                'mysql' => 'MySQL (standard)',
                'active_directory' => 'Active Directory',
                'ldap' => 'LDAP',
                'radius' => 'RADIUS',
                'http-auth' => 'HTTP-autentisering',
                'ad-authorization' => 'Externt autentiserat AD',
                'ldap-authorization' => 'Externt autentiserad LDAP',
                'sso' => 'Enkel inloggning (SSO)',
            ],
        ],
        'auth_remember' => [
            'description' => 'Varaktighet för Kom ihåg mig',
            'help' => 'Antal dagar som en användare förblir inloggad när användaren kryssar i Kom ihåg mig.',
        ],
        'authlog_purge' => [
            'description' => 'Autentiseringsloggposter äldre än',
            'help' => 'Rensningen görs av daily.sh',
        ],
        'availablity' => [
            'threshold_ok' => [
                'description' => 'Tröskelvärde för OK-tillgänglighet',
                'help' => 'Tröskel för grön färg',
            ],
            'threshold_warning' => [
                'description' => 'Tröskelvärde för tillgänglighetsvarning',
                'help' => 'Tröskel för orange färg',
            ],
        ],
        'bad_entity_sensor_regex' => [
            'description' => 'Reguljärt uttryck för felaktiga entitetssensorer',
            'help' => 'Reguljärt uttryck som matchar felaktiga entitetssensorer. Dessa sensorer visas inte i webbgränssnittet.',
        ],
        'billing' => [
            '95th_default_agg' => [
                'description' => 'Standardaggregering för 95:e percentilen',
                'help' => 'Ange aggregerad beräkning som standardalternativ för 95:e percentilen.',
            ],
        ],
        'enable_billing' => [
            'description' => 'Aktivera fakturering',
            'help' => 'Aktivera faktureringsmodulen. Med den kan du övervaka portanvändning.',
        ],
        'peering_descr' => [
            'description' => 'Peering-porttyper',
            'help' => 'Portar av de angivna beskrivningstyperna visas under menyposten för peering-portar. Mer information finns i dokumentationen om tolkning av gränssnittsbeskrivningar.',
        ],
        'transit_descr' => [
            'description' => 'Transitporttyper',
            'help' => 'Portar av de angivna beskrivningstyperna visas under menyposten för transitportar. Mer information finns i dokumentationen om tolkning av gränssnittsbeskrivningar.',
        ],
        'collectd_dir' => [
            'description' => 'Collectd-katalog',
            'help' => 'Katalogen där collectd lagrar sina RRD-filer. Den används för att visa data från collectd.',
        ],
        'collectd_sock' => [
            'description' => 'Collectd-socket',
            'help' => 'Socketen som collectd lyssnar på. Den används för att visa data från collectd.',
        ],
        'core_descr' => [
            'description' => 'Kärnporttyper',
            'help' => 'Portar av de angivna beskrivningstyperna visas under menyposten för kärnportar. Mer information finns i dokumentationen om tolkning av gränssnittsbeskrivningar.',
        ],
        'custom_descr' => [
            'description' => 'Anpassade porttyper',
            'help' => 'Portar av de angivna beskrivningstyperna visas under menyposten för anpassade portar. Mer information finns i dokumentationen om tolkning av gränssnittsbeskrivningar.',
        ],
        'custom_map' => [
            'background_type' => [
                'description' => 'Bakgrundstyp',
                'help' => 'Standardbakgrundstyp för nya kartor. Kräver att bakgrundsdata är angivna.',
            ],
            'background_data' => [
                'color' => [
                    'description' => 'Bakgrundsfärg',
                    'help' => 'Startfärg för kartbakgrunden',
                ],
                'lat' => [
                    'description' => 'Bakgrundskartans latitud',
                    'help' => 'Startlatitud för geokartan i bakgrunden',
                ],
                'lng' => [
                    'description' => 'Bakgrundskartans longitud',
                    'help' => 'Startlongitud för geokartan i bakgrunden',
                ],
                'layer' => [
                    'description' => 'Bakgrundskartans lager',
                    'help' => 'Startlager för geokartan i bakgrunden',
                ],
                'zoom' => [
                    'description' => 'Bakgrundskartans zoom',
                    'help' => 'Startzoom för geokartan i bakgrunden',
                ],
            ],
            'edge_font_color' => [
                'description' => 'Kanttextfärg',
                'help' => 'Standardfärg på texten i kantetiketter',
            ],
            'edge_font_face' => [
                'description' => 'Teckensnitt för kanter',
                'help' => 'Standardteckensnitt för kantetiketter',
            ],
            'edge_font_size' => [
                'description' => 'Kanttextstorlek',
                'help' => 'Standardstorlek på texten i kantetiketter',
            ],
            'edge_seperation' => [
                'description' => 'Kantseparation',
                'help' => 'Standardavstånd mellan kanter för nya kartor',
            ],
            'height' => [
                'description' => 'Karthöjd',
                'help' => 'Standardhöjd för nya kartor',
            ],
            'node_align' => [
                'description' => 'Nodjustering',
                'help' => 'Standardnodjustering för nya kartor',
            ],
            'node_background' => [
                'description' => 'Nodbakgrund',
                'help' => 'Standardbakgrundsfärg för nodetiketter',
            ],
            'node_border' => [
                'description' => 'Nodkant',
                'help' => 'Standardfärg på kanten runt nodetiketter',
            ],
            'node_font_color' => [
                'description' => 'Nodtextfärg',
                'help' => 'Standardfärg på texten i nodetiketter',
            ],
            'node_font_face' => [
                'description' => 'Teckensnitt för noder',
                'help' => 'Standardteckensnitt för nodetiketter',
            ],
            'node_font_size' => [
                'description' => 'Nodtextstorlek',
                'help' => 'Standardstorlek på texten i nodetiketter',
            ],
            'node_size' => [
                'description' => 'Nodstorlek',
                'help' => 'Standardstorlek för noder',
            ],
            'node_type' => [
                'description' => 'Nodvisningstyp',
                'help' => 'Standardvisningstyp för noder',
            ],
            'reverse_arrows' => [
                'description' => 'Vänd kantpilarna',
                'help' => 'Standardriktning för pilar. Mot mitten (standard) eller mot ändarna',
            ],
            'width' => [
                'description' => 'Kartbredd',
                'help' => 'Standardbredd för nya kartor',
            ],
        ],
        'customers_descr' => [
            'description' => 'Kundporttyper',
            'help' => 'Portar av de angivna beskrivningstyperna visas under menyposten för kundportar. Mer information finns i dokumentationen om tolkning av gränssnittsbeskrivningar.',
        ],
        'base_url' => [
            'description' => 'Bas-URL',
            'help' => 'Ange detta *endast* om du vill *tvinga* fram ett visst värdnamn eller en viss port. Det hindrar att webbgränssnittet används från något annat värdnamn.',
        ],
        'disabled_sensors' => [
            'description' => 'Inaktiverade sensorer',
            'help' => 'Sensorer som inte ska pollas eller visas i webbgränssnittet.',
        ],
        'disabled_sensors_regex' => [
            'description' => 'Reguljärt uttryck för inaktiverade sensorer',
            'help' => 'Sensorer som matchar det här reguljära uttrycket pollas inte och visas inte i webbgränssnittet.',
        ],
        'discovery_modules' => [
            'arp-table' => [
                'description' => 'ARP-tabell',
            ],
            'applications' => [
                'description' => 'Applikationer',
            ],
            'bgp-peers' => [
                'description' => 'BGP-motparter',
            ],
            'cisco-cef' => [
                'description' => 'Cisco CEF',
            ],
            'mac-accounting' => [
                'description' => 'MAC-redovisning',
            ],
            'cisco-otv' => [
                'description' => 'Cisco OTV',
            ],
            'cisco-qfp' => [
                'description' => 'Cisco QFP',
            ],
            'slas' => [
                'description' => 'Spårning av servicenivåavtal',
            ],
            'cisco-pw' => [
                'description' => 'Cisco PW',
            ],
            'cisco-vrf-lite' => [
                'description' => 'Cisco VRF Lite',
            ],
            'discovery-arp' => [
                'description' => 'ARP-upptäckt',
            ],
            'discovery-protocols' => [
                'description' => 'Upptäcktsprotokoll',
            ],
            'entity-physical' => [
                'description' => 'Fysiska entiteter',
            ],
            'entity-state' => [
                'description' => 'Entiteternas tillstånd',
            ],
            'fdb-table' => [
                'description' => 'FDB-tabell',
            ],
            'hr-device' => [
                'description' => 'HR-enhet',
            ],
            'ipv4-addresses' => [
                'description' => 'IPv4-adresser',
            ],
            'ipv6-addresses' => [
                'description' => 'IPv6-adresser',
            ],
            'isis' => [
                'description' => 'ISIS',
            ],
            'junose-atm-vp' => [
                'description' => 'Junose ATM VP',
            ],
            'loadbalancers' => [
                'description' => 'Lastbalanserare',
            ],
            'mef' => [
                'description' => 'MEF',
            ],
            'mempools' => [
                'description' => 'Mempools',
            ],
            'mpls' => [
                'description' => 'MPLS',
            ],
            'ntp' => [
                'description' => 'NTP',
            ],
            'os' => [
                'description' => 'OS',
            ],
            'ports' => [
                'description' => 'Portar',
            ],
            'ports-stack' => [
                'description' => 'Ports Stack',
            ],
            'processors' => [
                'description' => 'Processorer',
            ],
            'qos' => [
                'description' => 'QoS',
            ],
            'route' => [
                'description' => 'Rutter',
            ],
            'sensors' => [
                'description' => 'Sensorer',
            ],
            'services' => [
                'description' => 'Tjänster',
            ],
            'storage' => [
                'description' => 'Lagring',
            ],
            'stp' => [
                'description' => 'STP',
            ],
            'ucd-diskio' => [
                'description' => 'UCD DiskIO',
            ],
            'vlans' => [
                'description' => 'VLAN',
            ],
            'vminfo' => [
                'description' => 'Hypervisor VM Info',
            ],
            'vrf' => [
                'description' => 'VRF',
            ],
            'wireless' => [
                'description' => 'Trådlöst',
            ],
            'xdsl' => [
                'description' => 'xDSL',
            ],
            'printer-supplies' => [
                'description' => 'Förbrukningsmaterial för skrivare',
            ],
        ],
        'distributed_poller' => [
            'description' => 'Aktivera distribuerad pollning (kräver ytterligare konfiguration)',
            'help' => 'Aktivera distribuerad pollning för hela systemet. Det är avsett för lastdelning, inte för fjärrpollning. Du måste läsa dokumentationen för att kunna aktivera det: https://docs.librenms.org/Extensions/Distributed-Poller/',
        ],
        'default_poller_group' => [
            'description' => 'Standardpollergrupp',
            'help' => 'Standardpollergruppen för alla pollrar när ingen anges i config.php',
        ],
        'device_traffic_iftype' => [
            'description' => 'Gränssnittstyper för enhetstrafik',
            'help' => 'Gränssnittstyper som ska uteslutas från enheternas grafer.',
        ],
        'distributed_poller_memcached_host' => [
            'description' => 'Memcached-värd',
            'help' => 'Värdnamnet eller IP-adressen till memcached-servern. Det krävs för låsning i poller_wrapper.py och daily.sh.',
        ],
        'distributed_poller_memcached_port' => [
            'description' => 'Memcached-port',
            'help' => 'Porten till memcached-servern. Standardvärdet är 11211',
        ],
        'enable_ports_etherlike' => [
            'description' => 'Aktivera etherlike-grafer för portar',
        ],
        'email_auto_tls' => [
            'description' => 'Stöd för automatisk TLS',
            'help' => 'Försöker med TLS först och faller sedan tillbaka på en okrypterad anslutning',
        ],
        'email_smtp_verifypeer' => [
            'description' => 'Verifiera motpartens certifikat',
            'help' => 'Verifiera inte motpartens certifikat för TLS-anslutningar till SMTP-servern',
        ],
        'email_smtp_allowselfsigned' => [
            'description' => 'Tillåt självsignerat certifikat',
            'help' => 'Tillåt ett självsignerat certifikat för TLS-anslutningar till SMTP-servern',
        ],
        'email_attach_graphs' => [
            'description' => 'Bifoga grafbilder',
            'help' => 'Genererar en graf när ett larm utlöses och bäddar in den i e-postmeddelandet.',
        ],
        'email_backend' => [
            'description' => 'Hur e-post ska levereras',
            'help' => 'Backend för e-postleverans. Det kan vara mail, sendmail eller SMTP.',
            'options' => [
                'mail' => 'mail',
                'sendmail' => 'sendmail',
                'smtp' => 'SMTP',
            ],
        ],
        'email_from' => [
            'description' => 'Från e-postadress',
            'help' => 'E-postadressen som används som avsändaradress',
        ],
        'email_html' => [
            'description' => 'Använd HTML-e-post',
            'help' => 'Skicka HTML-e-post',
        ],
        'email_sendmail_path' => [
            'description' => 'Sökväg till binären sendmail',
        ],
        'email_smtp_auth' => [
            'description' => 'SMTP-autentisering',
            'help' => 'Aktivera detta om din SMTP-server kräver autentisering',
        ],
        'email_smtp_host' => [
            'description' => 'SMTP-server',
            'help' => 'IP-adress eller DNS-namn för SMTP-servern som e-posten ska levereras till',
        ],
        'email_smtp_password' => [
            'description' => 'Lösenord för SMTP-autentisering',
        ],
        'email_smtp_port' => [
            'description' => 'SMTP-portinställning',
        ],
        'email_smtp_secure' => [
            'description' => 'Kryptering',
            'options' => [
                '' => 'Inaktiverad',
                'tls' => 'TLS',
                'ssl' => 'SSL',
            ],
        ],
        'email_smtp_timeout' => [
            'description' => 'Timeout-inställning för SMTP',
        ],
        'email_smtp_username' => [
            'description' => 'Användarnamn för SMTP-autentisering',
        ],
        'email_user' => [
            'description' => 'Avsändarnamn',
            'help' => 'Namn som används som en del av avsändaradressen',
        ],
        'enable_clear_discovery' => [
            'description' => 'Aktivera rensning av upptäckt',
            'help' => 'Gör det möjligt att rensa datum och tid för upptäckt av en enhet. Det tvingar fram en ny upptäckt av enheten.',
        ],
        'enable_inventory' => [
            'description' => 'Aktivera inventering',
            'help' => 'Aktiverar inventeringssidan, som visar enheternas hårdvaruinventering.',
        ],
        'enable_lazy_load' => [
            'description' => 'Aktivera Lazy Loading',
            'help' => 'Lat inläsning gör sidorna snabbare. Den läser bara in de data som sidan behöver för tillfället. Inaktivera inställningen om du får problem med den.',
        ],
        'enable_libvirt' => [
            'description' => 'Aktivera Libvirt',
            'help' => 'Aktiverar libvirt-sidan, som visar enheternas virtuella maskiner.',
        ],
        'enable_proxmox' => [
            'description' => 'Aktivera Proxmox',
            'help' => 'Aktiverar Proxmox-sidan, som visar enheternas virtuella maskiner.',
        ],
        'enable_pseudowires' => [
            'description' => 'Aktivera Pseudowires',
            'help' => 'Aktiverar pseudowire-sidan, som visar enheternas pseudowires.',
        ],
        'enable_syslog' => [
            'description' => 'Aktivera Syslog',
            'help' => 'Visar syslog i webbgränssnittet.',
        ],
        'eventlog_purge' => [
            'description' => 'Händelseloggposter äldre än',
            'help' => 'Rensningen görs av daily.sh',
        ],
        'favicon' => [
            'description' => 'Favicon',
            'help' => 'Åsidosätter standardikonen (favicon).',
        ],
        'front_page' => [
            'description' => 'Förstasida',
            'help' => 'Ange en egen förstasida. Det är sidan du ser när du loggar in. Om du till exempel skapar `resources/views/overview/custom/foobar.blade.php` anger du `foobar` som `front_page`.',
        ],
        'front_page_down_box_limit' => [
            'description' => 'Gräns för enheter som är nere',
            'help' => 'Antal enheter som ska visas i rutan med enheter som är nere på förstasidan',
        ],
        'front_page_settings' => [
            'top_devices' => [
                'description' => 'Topplista enheter',
                'help' => 'Antal enheter som ska visas i topplistan på förstasidan',
            ],
            'top_ports' => [
                'description' => 'Topplista portar',
                'help' => 'Antal portar som ska visas i topplistan på förstasidan',
            ],
        ],
        'fping' => [
            'description' => 'Sökväg till fping',
        ],
        'fping6' => [
            'description' => 'Sökväg till fping6',
        ],
        'fping_options' => [
            'count' => [
                'description' => 'Antal fping-paket',
                'help' => 'Antalet ping som ska skickas när det kontrolleras om en värd är uppe eller nere via ICMP',
            ],
            'interval' => [
                'description' => 'fping-intervall',
                'help' => 'Antalet millisekunder som ska gå mellan pingarna',
            ],
            'timeout' => [
                'description' => 'Timeout för fping',
                'help' => 'Antalet millisekunder att vänta på ett ekosvar',
            ],
        ],
        'geoloc' => [
            'api_key' => [
                'description' => 'API-nyckel för kartmotorn',
                'help' => 'API-nyckel för geokodning (krävs för att funktionen ska fungera)',
            ],
            'dns' => [
                'description' => 'Använd DNS-platspost',
                'help' => 'Använd LOC-posten från DNS-servern för att hämta geografiska koordinater för värdnamnet',
            ],
            'engine' => [
                'description' => 'Kartmotor',
                'options' => [
                    'google' => 'Google Maps',
                    'openstreetmap' => 'OpenStreetMap',
                    'mapquest' => 'MapQuest',
                    'bing' => 'Bing Maps',
                    'esri' => 'ESRI ArcGIS',
                ],
            ],
            'latlng' => [
                'description' => 'Försök att geokoda platser',
                'help' => 'Försök slå upp latitud och longitud via ett geokodnings-API under pollningen',
            ],
            'layer' => [
                'description' => 'Kartlager vid start',
                'help' => 'Kartlager som ska visas vid start. *Alla lager är inte tillgängliga för alla kartmotorer.',
                'options' => [
                    'Streets' => 'Gator',
                    'Sattelite' => 'Satellit',
                    'Topography' => 'Topografi',
                ],
            ],
        ],
        'graphite' => [
            'enable' => [
                'description' => 'Aktivera',
                'help' => 'Exporterar mätvärden till Graphite',
            ],
            'host' => [
                'description' => 'Server',
                'help' => 'IP-adressen eller värdnamnet för den Graphite-server som data ska skickas till',
            ],
            'port' => [
                'description' => 'Port',
                'help' => 'Porten som ska användas för att ansluta till Graphite-servern',
            ],
            'prefix' => [
                'description' => 'Prefix (valfritt)',
                'help' => 'Lägger till prefixet i början av alla mätvärden. Prefixet måste vara alfanumeriskt och separeras med punkter.',
            ],
        ],
        'graphing' => [
            'availability' => [
                'description' => 'Varaktighet',
                'help' => 'Beräkna enhetstillgänglighet för de angivna tidsperioderna. (Tidsperioderna anges i sekunder)',
            ],
            'availability_consider_maintenance' => [
                'description' => 'Schemalagt underhåll påverkar inte tillgängligheten',
                'help' => 'Skapar inga avbrott och minskar inte tillgängligheten för enheter i underhållsläge.',
            ],
        ],
        'graphs' => [
            'row' => [
                'normal' => [
                    'options' => [
                        'sixhour' => '6 timmar',
                        'day' => '24 timmar',
                        'twoday' => '48 timmar',
                        'week' => '1 vecka',
                        'twoweek' => '2 veckor',
                        'month' => '1 månad',
                        'twomonth' => '2 månader',
                        'year' => '1 år',
                        'twoyear' => '2 år',
                    ],
                ],
            ],
            'port_speed_zoom' => [
                'description' => 'Zooma portgrafer till porthastighet',
                'help' => 'Zooma portgraferna så att maxvärdet alltid är porthastigheten. När inställningen är inaktiverad zoomar portgraferna till trafiken.',
            ],
        ],
        'graylog' => [
            'base_uri' => [
                'description' => 'Bas-URI',
                'help' => 'Åsidosätt bas-URI:n om du har ändrat Graylogs standardvärde.',
            ],
            'device-page' => [
                'loglevel' => [
                    'description' => 'Loggnivå på enhetsöversikten',
                    'help' => 'Anger den högsta loggnivå som visas på enhetens översiktssida.',
                ],
                'rowCount' => [
                    'description' => 'Antal rader på enhetsöversikten',
                    'help' => 'Anger antalet rader som visas på enhetens översiktssida.',
                ],
            ],
            'password' => [
                'description' => 'Lösenord',
                'help' => 'Lösenord för åtkomst till Graylogs API.',
            ],
            'port' => [
                'description' => 'Port',
                'help' => 'Porten som används för att komma åt Graylogs API. Om du inte anger någon används port 80 för HTTP och 443 för HTTPS.',
            ],
            'server' => [
                'description' => 'Server',
                'help' => 'IP-adressen eller värdnamnet för Graylog-serverns API-slutpunkt.',
            ],
            'timezone' => [
                'description' => 'Visa tidszon',
                'help' => 'Graylog lagrar tider i GMT. Den här inställningen ändrar vilken tidszon som visas. Värdet måste vara en giltig PHP-tidszon.',
            ],
            'username' => [
                'description' => 'Användarnamn',
                'help' => 'Användarnamn för åtkomst till Graylogs API.',
            ],
            'version' => [
                'description' => 'Version',
                'help' => 'Detta används för att skapa base_uri för Graylogs API. Om du har ändrat API-URI:n från standardvärdet väljer du "annan" här och anger din base_uri.',
            ],
            'query' => [
                'field' => [
                    'description' => 'API-fält att fråga på',
                    'help' => 'Ändrar standardfältet som används vid frågor mot Graylogs API.',
                ],
            ],
            'match-any-address' => [
                'description' => 'Matcha vilken adress som helst',
                'help' => 'Matcha valfri adress på en enhet mot källan till ett Graylog-loggmeddelande. Som standard används bara den primära adressen.',
            ],
        ],
        'html' => [
            'device' => [
                'primary_link' => [
                    'description' => 'Primär rullgardinslänk',
                    'help' => 'Ställer in den primära länken i enhetsrullgardinsmenyn',
                ],
            ],
        ],
        'http_auth_header' => [
            'description' => 'Fältnamn som innehåller användarnamn',
            'help' => 'Kan vara ett ENV- eller HTTP-huvudfält som REMOTE_USER eller PHP_AUTH_USER, eller en egen variant',
        ],
        'http_auth_guest' => [
            'description' => 'Gästanvändare för HTTP-autentisering',
            'help' => 'Om värdet anges får alla HTTP-användare autentisera sig, och okända användare tilldelas det angivna lokala användarnamnet',
        ],
        'http_proxy' => [
            'description' => 'HTTP-proxy',
            'help' => 'Ange detta som reserv om miljövariabeln http_proxy inte är tillgänglig.',
        ],
        'https_proxy' => [
            'description' => 'HTTPS-proxy',
            'help' => 'Ange detta som reserv om miljövariabeln https_proxy inte är tillgänglig.',
        ],
        'icmp_check' => [
            'description' => 'ICMP-kontroll',
            'help' => 'Aktivera ICMP-kontrollen för alla enheter. Enheterna pingas för att kontrollera om de är uppe eller nere. Om du inaktiverar detta kan pollningen misslyckas med att bli klar i tid.',
        ],
        'ignore_mount' => [
            'description' => 'Monteringspunkter som ska ignoreras',
            'help' => 'Övervaka inte diskanvändningen för de här monteringspunkterna',
        ],
        'ignore_mount_network' => [
            'description' => 'Ignorera nätverksmonteringspunkter',
            'help' => 'Övervaka inte diskanvändningen för monteringspunkter i nätverket',
        ],
        'ignore_mount_optical' => [
            'description' => 'Ignorera optiska enheter',
            'help' => 'Övervaka inte diskanvändningen för optiska enheter',
        ],
        'ignore_mount_removable' => [
            'description' => 'Ignorera flyttbara enheter',
            'help' => 'Övervaka inte diskanvändningen för flyttbara enheter',
        ],
        'ignore_mount_regexp' => [
            'description' => 'Monteringspunkter som matchar reguljärt uttryck ska ignoreras',
            'help' => 'Övervaka inte diskanvändningen för monteringspunkter som matchar minst ett av dessa reguljära uttryck',
        ],
        'ignore_mount_string' => [
            'description' => 'Monteringspunkter som innehåller sträng ska ignoreras',
            'help' => 'Övervaka inte diskanvändningen för monteringspunkter som innehåller minst en av dessa strängar',
        ],
        'influxdb' => [
            'db' => [
                'description' => 'Databas',
                'help' => 'Namnet på den InfluxDB-databas som mätvärdena ska lagras i',
            ],
            'enable' => [
                'description' => 'Aktivera',
                'help' => 'Exporterar mätvärden till InfluxDB',
            ],
            'host' => [
                'description' => 'Server',
                'help' => 'IP-adressen eller värdnamnet för den InfluxDB-server som data ska skickas till',
            ],
            'password' => [
                'description' => 'Lösenord',
                'help' => 'Lösenord för att ansluta till InfluxDB, om det krävs',
            ],
            'port' => [
                'description' => 'Port',
                'help' => 'Porten som ska användas för att ansluta till InfluxDB-servern',
            ],
            'timeout' => [
                'description' => 'Timeout',
                'help' => 'Hur länge systemet ska vänta på InfluxDB-servern. 0 betyder standardvärdet för timeout',
            ],
            'transport' => [
                'description' => 'Transport',
                'help' => 'Transporten som ska användas för att ansluta till InfluxDB-servern',
                'options' => [
                    'http' => 'HTTP',
                    'https' => 'HTTPS',
                    'udp' => 'UDP',
                ],
            ],
            'username' => [
                'description' => 'Användarnamn',
                'help' => 'Användarnamn för att ansluta till InfluxDB, om det krävs',
            ],
            'batch_size' => [
                'description' => 'Batchstorlek',
                'help' => 'Antal mätvärden som ska skickas i en och samma batch. 0 inaktiverar batchning.',
            ],
            'measurements' => [
                'description' => 'Mätningar',
                'help' => 'Lista över mätningar som ska skickas till InfluxDB. Lämna tom för att skicka alla',
            ],
            'verifySSL' => [
                'description' => 'Verifiera SSL',
                'help' => 'Kontrollera att SSL-certifikatet är giltigt och betrott',
            ],
            'debug' => [
                'description' => 'Felsökning',
                'help' => 'Aktivera eller inaktivera utförlig utskrift till CLI',
            ],
        ],
        'influxdbv2' => [
            'bucket' => [
                'description' => 'Bucket',
                'help' => 'Namnet på den InfluxDB-bucket som mätvärdena ska lagras i',
            ],
            'enable' => [
                'description' => 'Aktivera',
                'help' => 'Exporterar mätvärden till InfluxDB via InfluxDBv2-API:et',
            ],
            'host' => [
                'description' => 'Server',
                'help' => 'IP-adressen eller värdnamnet för den InfluxDB-server som data ska skickas till',
            ],
            'token' => [
                'description' => 'Token',
                'help' => 'Token för att ansluta till InfluxDB, om det krävs',
            ],
            'port' => [
                'description' => 'Port',
                'help' => 'Porten som ska användas för att ansluta till InfluxDB-servern',
            ],
            'transport' => [
                'description' => 'Transport',
                'help' => 'Transporten som ska användas för att ansluta till InfluxDB-servern',
                'options' => [
                    'http' => 'HTTP',
                    'https' => 'HTTPS',
                ],
            ],
            'organization' => [
                'description' => 'Organisation',
                'help' => 'Organisationen som innehåller bucketen på InfluxDB-servern',
            ],
            'allow_redirects' => [
                'description' => 'Tillåt omdirigeringar',
                'help' => 'Tillåt omdirigering från InfluxDB-servern',
            ],
            'debug' => [
                'description' => 'Felsökning',
                'help' => 'Aktivera eller inaktivera utförlig utskrift till CLI',
            ],
            'log_file' => [
                'description' => 'Loggfil',
                'help' => 'Ange en annan loggfil för felsökningen om du vill',
            ],
            'groups-exclude' => [
                'description' => 'Uteslutna enhetsgrupper',
                'help' => 'Enhetsgrupper som ska uteslutas från de data som skickas till InfluxDBv2',
            ],
            'timeout' => [
                'description' => 'Timeout',
                'help' => 'Timeout i sekunder',
            ],
            'verify' => [
                'description' => 'Verifiera',
                'help' => 'Verifiera certifikatet',
            ],
            'batch_size' => [
                'description' => 'Batchstorlek',
                'help' => 'Hur många mätvärden som ska buntas ihop innan de skickas',
            ],
            'max_retry' => [
                'description' => 'Max antal försök',
                'help' => 'Hur många gånger försöket ska göras om',
            ],
        ],
        'kafka' => [
            'enable' => [
                'description' => 'Aktivera',
                'help' => 'Exporterar mätvärden till Kafka med hjälp av idealo/php-rdkafka-ffi',
            ],
            'groups-exclude' => [
                'description' => 'ID för uteslutna enhetsgrupper',
                'help' => 'ID för de enhetsgrupper som ska uteslutas från de data som skickas till Kafka',
            ],
            'measurement-exclude' => [
                'description' => 'Uteslutna mätningar',
                'help' => 'Upptäcktsmoduler som ska uteslutas från de data som skickas till Kafka',
            ],
            'debug' => [
                'description' => 'Felsökning',
                'help' => 'Aktivera detaljerade loggar för Kafkas interna lagringsprocess',
            ],
            'security' => [
                'debug' => [
                    'description' => 'Säkerhetsfelsökning',
                    'help' => 'Visa mer detaljerad information om säkerhetskommunikationen med Kafka-brokrar',
                ],
            ],
            'broker' => [
                'list' => [
                    'description' => 'Lista över Kafka-brokerservrar i formatet host!:port',
                    'help' => 'Lista över Kafka-brokrar i formatet host!:port. https://github.com/confluentinc/librdkafka/blob/master/CONFIGURATION.md',
                ],
            ],
            'idempotence' => [
                'description' => 'Idempotens',
                'help' => 'När inställningen är aktiverad producerar producenten varje meddelande exakt en gång och i ursprunglig ordning',
            ],
            'topic' => [
                'description' => 'Ämne',
                'help' => 'Kategorierna som används för att organisera meddelanden',
            ],
            'ssl' => [
                'enable' => [
                    'description' => 'Aktivera SSL',
                    'help' => 'Aktivera SSL-stöd i Kafka',
                ],
                'protocol' => [
                    'description' => 'SSL-protokoll',
                    'help' => 'Protokoll som används för kommunikationen med brokrar',
                ],
                'ca' => [
                    'location' => [
                        'description' => 'Sökväg till SSL-certifikatutfärdare',
                        'help' => 'Sökväg till fil eller katalog med CA-certifikat för att verifiera brokerns nyckel.',
                    ],
                ],
                'certificate' => [
                    'location' => [
                        'description' => 'Sökväg till SSL-certifikat',
                        'help' => 'Sökväg till klientens publika nyckel (PEM) som används för autentisering.',
                    ],
                ],
                'key' => [
                    'location' => [
                        'description' => 'Sökväg till SSL-certifikatnyckel',
                        'help' => 'Sökväg till klientens privata nyckel (PEM) som används för autentisering.',
                    ],
                    'password' => [
                        'description' => 'Lösenord för SSL-certifikatnyckel',
                        'help' => 'Lösenfras för den privata nyckeln (används tillsammans med kafka.ssl.key.location).',
                    ],
                ],
                'keystore' => [
                    'location' => [
                        'description' => 'Sökväg till SSL-keystore-certifikat',
                        'help' => 'Sökväg till klientens keystore (PKCS#12) som används för autentisering.',
                    ],
                    'password' => [
                        'description' => 'Lösenord för SSL-keystore-nyckeln',
                        'help' => 'Lösenord för klientens keystore (PKCS#12).',
                    ],
                ],
            ],
            'flush' => [
                'timeout' => [
                    'description' => 'Timeout för Kafka-tömning',
                    'help' => 'Den tid Kafka väntar med att tömma meddelanden i kön',
                ],
            ],
            'buffer' => [
                'max' => [
                    'message' => [
                        'description' => 'Maximalt antal meddelanden i Kafka-bufferten i pollerns minne',
                        'help' => 'Maximalt tillåtet antal meddelanden i Kafka-bufferten i pollerns minne',
                    ],
                ],
            ],
            'batch' => [
                'max' => [
                    'message' => [
                        'description' => 'Maximalt antal meddelanden per Kafka-batch som skickas vid varje anrop till Kafka-servrarna',
                        'help' => 'Maximalt antal meddelanden per Kafka-batch som skickas vid varje anrop till Kafka-servrarna',
                    ],
                ],
            ],
            'linger' => [
                'ms' => [
                    'description' => 'Kafkas väntetid i ms för att samla meddelanden i pollerns minne innan batchen skickas',
                    'help' => 'Kafkas väntetid i ms för att samla meddelanden i pollerns minne innan batchen skickas',
                ],
            ],
            'request' => [
                'required' => [
                    'acks' => [
                        'description' => 'Antal ack som krävs för en Kafka-begäran',
                        'help' => 'Antal ack som krävs för en Kafka-begäran',
                    ],
                ],
            ],
        ],
        'int_core' => [
            'description' => 'Aktivera menyn Kärnportar',
            'help' => 'Aktivera menyn för kärnportar i webbgränssnittet',
        ],
        'int_customers' => [
            'description' => 'Aktivera menyn Kundportar',
            'help' => 'Aktivera menyn för kundportar i webbgränssnittet',
        ],
        'int_peering' => [
            'description' => 'Aktivera menyn Peering-portar',
            'help' => 'Aktivera menyn för peering-portar i webbgränssnittet',
        ],
        'int_transit' => [
            'description' => 'Aktivera menyn Transitportar',
            'help' => 'Aktivera menyn för transitportar i webbgränssnittet',
        ],
        'int_l2tp' => [
            'description' => 'Aktivera menyn L2TP-portar',
            'help' => 'Aktivera menyn för L2TP-portar i webbgränssnittet',
        ],
        'ipmitool' => [
            'description' => 'Sökväg till ipmitool',
        ],
        'ipmi.type' => [
            'description' => 'IPMI-typ',
            'help' => 'Typ av IPMI som ska användas. Kan vara `lan`, `lanplus`, `open`, `sol`, `raw` eller `shell`',
        ],
        'ipmi_unit' => [
            'description' => 'IPMI-enhet',
            'help' => 'IPMI-enhetstyper som kan upptäckas.',
        ],
        'libvirt_protocols' => [
            'description' => 'Libvirt-protokoll',
            'help' => 'Protokoll att använda för libvirt-anslutningar.',
        ],
        'libvirt_username' => [
            'description' => 'Användarnamn för libvirt',
            'help' => 'Användarnamn att använda för libvirt-anslutningar.',
        ],
        'location_map' => [
            'description' => 'Specifik platsmappning',
            'help' => 'Mappa ett sysLocation-värde till ett annat värde.',
        ],
        'location_map_regex' => [
            'description' => 'Specifik platsmappning med reguljärt uttryck',
            'help' => 'Mappa ett sysLocation-värde till ett annat värde med hjälp av ett reguljärt uttryck.',
        ],
        'location_map_regex_sub' => [
            'description' => 'Specifik platsmappning med ersättning via reguljärt uttryck',
            'help' => 'Ersätt sysLocation-värdet med hjälp av ett reguljärt uttryck.',
        ],
        'login_message' => [
            'description' => 'Inloggningsmeddelande',
            'help' => 'Visas på inloggningssidan',
        ],
        'mac_oui' => [
            'enabled' => [
                'description' => 'Aktivera MAC OUI-uppslagning',
                'help' => 'Aktivera uppslagning av tillverkare (OUI) för MAC-adresser. Data hämtas av daily.sh.',
            ],
        ],
        'mono_font' => [
            'description' => 'Teckensnitt med fast bredd',
        ],
        'mtr' => [
            'description' => 'Sökväg till mtr',
        ],
        'mtu_options' => [
            'bytes' => [
                'description' => 'Paketstorlek för MTU-test',
                'help' => 'Storlek i byte på paketen i MTU-testet (lämna tomt för att inaktivera MTU-tester)',
            ],
        ],
        'mydomain' => [
            'description' => 'Primär domän',
            'help' => 'Den här domänen används för automatisk nätverksupptäckt och andra processer. Den läggs till efter okvalificerade värdnamn.',
        ],
        'network_map_show_on_worldmap' => [
            'description' => 'Visa nätverkslänkar på kartan',
            'help' => 'Visa nätverkslänkarna mellan platserna på världskartan (i stil med en weathermap)',
        ],
        'network_map_vis_options' => [
            'description' => 'Layoutalternativ för nätverkskartan',
            'help' => 'Avancerade vis.js-alternativ (JSON) som styr layout, fysik och utseende för den automatiskt genererade nätverkskartan. Redigera med försiktighet – innehållet måste förbli giltig JSON.',
        ],
        'network_map_worldmap_show_disabled_alerts' => [
            'description' => 'Visa enheter med inaktiverade larm',
            'help' => 'Visa enheter som har larm inaktiverade på nätverkskartan',
        ],
        'network_map_worldmap_link_type' => [
            'description' => 'Källa för nätverkskartan',
            'help' => 'Välj datakälla för nätverkskartans länkar',
        ],
        'nfsen_enable' => [
            'description' => 'Aktivera NfSen',
            'help' => 'Aktivera integration med NfSen',
        ],
        'nfsen_rrds' => [
            'description' => 'NfSen RRD-kataloger',
            'help' => 'Det här värdet anger var dina NfSen RRD-filer finns.',
        ],
        'nfsen_subdirlayout' => [
            'description' => 'Ange NfSens underkataloglayout',
            'help' => 'Detta måste matcha den underkataloglayout som du har angett i NfSen. Standardvärdet är 1.',
        ],
        'nfsen_last_max' => [
            'description' => 'Högsta värde för Senaste',
        ],
        'nfsen_top_max' => [
            'description' => 'Högsta värde för Topp',
            'help' => 'Högsta topN-värde för statistiken',
        ],
        'nfsen_top_N' => [
            'description' => 'Topp N',
        ],
        'nfsen_top_default' => [
            'description' => 'Standardvärde för Topp N',
        ],
        'nfsen_stats_default' => [
            'description' => 'Standardstatistik',
        ],
        'nfsen_order_default' => [
            'description' => 'Standardordning',
        ],
        'nfsen_last_default' => [
            'description' => 'Standardvärde för Senaste',
        ],
        'nfsen_lasts' => [
            'description' => 'Standardalternativ för Senaste',
        ],
        'nfsen_base' => [
            'description' => 'Baskatalog för NfSen',
            'help' => 'Används för att hitta enhetsspecifika grafer',
        ],
        'nfsen_split_char' => [
            'description' => 'Delningstecken',
            'help' => 'Tecknet som ersätter punkterna `.` i enhetens värdnamn. Vanligtvis `_`.',
        ],
        'nfsen_suffix' => [
            'description' => 'Filnamnssuffix',
            'help' => 'NfSen begränsar enhetsnamn till 21 tecken. Fullständiga domännamn för enheter får ofta inte plats, och därför tas det här suffixet oftast bort.',
        ],
        'no_proxy' => [
            'description' => 'Proxy-undantag',
            'help' => 'Ange detta som reserv om miljövariabeln no_proxy inte är tillgänglig. Kommaseparerad lista över IP-adresser, värdar eller domäner som ska ignoreras.',
        ],
        'opentsdb' => [
            'enable' => [
                'description' => 'Aktivera',
                'help' => 'Exporterar mätvärden till OpenTSDB',
            ],
            'host' => [
                'description' => 'Server',
                'help' => 'IP-adressen eller värdnamnet för den OpenTSDB-server som data ska skickas till',
            ],
            'port' => [
                'description' => 'Port',
                'help' => 'Porten som ska användas för att ansluta till OpenTSDB-servern',
            ],
        ],
        'overview_show_sysDescr' => [
            'description' => 'Visa sysDescr på enhetsöversikt',
            'help' => 'Visa sysDescr på enhetens översiktssida',
        ],
        'own_hostname' => [
            'description' => 'LibreNMS värdnamn',
            'help' => 'Ange det värdnamn eller den IP-adress som LibreNMS-servern är tillagd med',
        ],
        'oxidized' => [
            'default_group' => [
                'description' => 'Ställ in standardgruppen som returneras',
            ],
            'ignore_groups' => [
                'description' => 'Säkerhetskopiera inte dessa Oxidized-grupper',
                'help' => 'Grupper (angivna via variabelmappning) som inte skickas till Oxidized',
            ],
            'enabled' => [
                'description' => 'Aktivera stöd för Oxidized',
            ],
            'features' => [
                'versioning' => [
                    'description' => 'Aktivera åtkomst till konfigurationsversion',
                    'help' => 'Aktivera versionshantering av konfigurationer i Oxidized (kräver git-backend)',
                ],
            ],
            'group_support' => [
                'description' => 'Aktivera att grupper returneras till Oxidized',
            ],
            'ignore_os' => [
                'description' => 'Säkerhetskopiera inte dessa operativsystem',
                'help' => 'Säkerhetskopiera inte de angivna operativsystemen med Oxidized. Operativsystemet måste matcha LibreNMS OS-namn, som skrivs med gemener och utan mellanslag. Endast befintliga OS-namn är tillåtna.',
            ],
            'ignore_types' => [
                'description' => 'Säkerhetskopiera inte dessa enhetstyper',
                'help' => 'Säkerhetskopiera inte de listade enhetstyperna med Oxidized. Tillåter endast befintliga typer.',
            ],
            'reload_nodes' => [
                'description' => 'Läs om Oxidized nodlista varje gång en enhet läggs till',
            ],
            'maps' => [
                'description' => 'Variabelmappning',
                'help' => 'Används för att ange grupp- och andra variabler eller för att mappa OS-namn som skiljer sig åt.',
            ],
            'url' => [
                'description' => 'URL till ditt Oxidized API',
                'help' => 'URL till Oxidized API (till exempel: http://127.0.0.1:8888)',
            ],
        ],
        'page_refresh' => [
            'description' => 'Siduppdatering',
            'help' => 'Hur ofta sidan ska uppdateras, i sekunder. Ange 0 för att inaktivera uppdateringen.',
        ],
        'password' => [
            'min_length' => [
                'description' => 'Minsta lösenordslängd',
                'help' => 'Lösenord som är kortare än den här längden avvisas',
            ],
            'uncompromised' => [
                'description' => 'Kräv att lösenordet inte är läckt',
                'help' => 'Kontrollerar lösenordet mot databasen HaveIBeenPwned med k-anonymitet',
            ],
        ],
        'peeringdb' => [
            'enabled' => [
                'description' => 'Aktivera PeeringDB-sökning',
                'help' => 'Aktivera uppslagning mot PeeringDB. Data hämtas av daily.sh.',
            ],
        ],
        'percentile_value' => [
            'description' => 'Percentilvärde',
            'help' => 'Percentilvärdet som ska användas för trafikgrafer. 0 betyder inaktiverat.',
        ],
        'permission' => [
            'device_group' => [
                'allow_dynamic' => [
                    'description' => 'Aktivera användaråtkomst via dynamiska enhetsgrupper',
                ],
            ],
        ],
        'bad_if' => [
            'description' => 'ifDescr för gränssnitt som ska ignoreras',
            'help' => 'Nätverksgränssnitt (IF-MIB:!:ifDescr) som ska ignoreras',
        ],
        'bad_if_regexp' => [
            'description' => 'Reguljärt uttryck för ifDescr på gränssnitt som ska ignoreras',
            'help' => 'Nätverksgränssnitt (IF-MIB:!:ifDescr) som ska ignoreras, angivna som reguljära uttryck',
        ],
        'bad_ifalias_regexp' => [
            'description' => 'Reguljärt uttryck för ifAlias på gränssnitt som ska ignoreras',
            'help' => 'Nätverksgränssnitt (IF-MIB:!:ifAlias) som ska ignoreras, angivna som reguljära uttryck',
        ],
        'bad_ifname_regexp' => [
            'description' => 'Reguljärt uttryck för ifName på gränssnitt som ska ignoreras',
            'help' => 'Nätverksgränssnitt (IF-MIB:!:ifName) som ska ignoreras, angivna som reguljära uttryck',
        ],
        'bad_ifoperstatus' => [
            'description' => 'ifOperStatus för gränssnitt som ska ignoreras',
            'help' => 'Nätverksgränssnitt (IF-MIB:!:ifOperStatus) som ska ignoreras',
        ],
        'bad_iftype' => [
            'description' => 'ifType för gränssnitt som ska ignoreras',
            'help' => 'Nätverksgränssnitt (IF-MIB:!:ifType) som ska ignoreras',
        ],
        'ping' => [
            'description' => 'Sökväg till ping',
        ],
        'poller_modules' => [
            'unix-agent' => [
                'description' => 'Unix-agent',
            ],
            'os' => [
                'description' => 'OS',
            ],
            'ipmi' => [
                'description' => 'IPMI',
            ],
            'qos' => [
                'description' => 'QoS',
            ],
            'sensors' => [
                'description' => 'Sensorer',
            ],
            'processors' => [
                'description' => 'Processorer',
            ],
            'mempools' => [
                'description' => 'Mempools',
            ],
            'storage' => [
                'description' => 'Lagring',
            ],
            'netstats' => [
                'description' => 'Netstats',
            ],
            'hr-mib' => [
                'description' => 'HR Mib',
            ],
            'ucd-mib' => [
                'description' => 'Ucd Mib',
            ],
            'ipSystemStats' => [
                'description' => 'ipSystemStats',
            ],
            'ports' => [
                'description' => 'Portar',
            ],
            'ports-stack' => [
                'description' => 'Ports Stack',
            ],
            'bgp-peers' => [
                'description' => 'BGP-motparter',
            ],
            'vlans' => [
                'description' => 'VLAN',
            ],
            'junose-atm-vp' => [
                'description' => 'JunOS ATM VP',
            ],
            'ucd-diskio' => [
                'description' => 'UCD DiskIO',
            ],
            'wireless' => [
                'description' => 'Trådlöst',
            ],
            'ospf' => [
                'description' => 'OSPF',
            ],
            'ospfv3' => [
                'description' => 'OSPFv3',
            ],
            'isis' => [
                'description' => 'ISIS',
            ],
            'cisco-ipsec-flow-monitor' => [
                'description' => 'Cisco IPSec Flow Monitor',
            ],
            'cisco-remote-access-monitor' => [
                'description' => 'Cisco Remote Access Monitor',
            ],
            'cisco-cef' => [
                'description' => 'Cisco CEF',
            ],
            'slas' => [
                'description' => 'Spårning av servicenivåavtal',
            ],
            'mac-accounting' => [
                'description' => 'Cisco MAC-redovisning',
            ],
            'cipsec-tunnels' => [
                'description' => 'Cipsec-tunnlar',
            ],
            'cisco-ace-loadbalancer' => [
                'description' => 'Cisco ACE Loadbalancer',
            ],
            'cisco-ace-serverfarms' => [
                'description' => 'Cisco ACE Serverfarms',
            ],
            'cisco-otv' => [
                'description' => 'Cisco OTV',
            ],
            'cisco-qfp' => [
                'description' => 'Cisco QFP',
            ],
            'cisco-vpdn' => [
                'description' => 'Cisco VPDN',
            ],
            'nac' => [
                'description' => 'NAC',
            ],
            'netscaler-vsvr' => [
                'description' => 'Netscaler VSVR',
            ],
            'aruba-controller' => [
                'description' => 'Aruba Controller',
            ],
            'availability' => [
                'description' => 'Tillgänglighet',
            ],
            'entity-physical' => [
                'description' => 'Fysiska entiteter',
            ],
            'entity-state' => [
                'description' => 'Entiteternas tillstånd',
            ],
            'applications' => [
                'description' => 'Applikationer',
            ],
            'stp' => [
                'description' => 'STP',
            ],
            'vminfo' => [
                'description' => 'Hypervisor VM Info',
            ],
            'ntp' => [
                'description' => 'NTP',
            ],
            'loadbalancers' => [
                'description' => 'Lastbalanserare',
            ],
            'mef' => [
                'description' => 'MEF',
            ],
            'mpls' => [
                'description' => 'MPLS',
            ],
            'xdsl' => [
                'description' => 'xDSL',
            ],
            'printer-supplies' => [
                'description' => 'Förbrukningsmaterial för skrivare',
            ],
            'port-security' => [
                'description' => 'Portsäkerhet',
            ],
        ],
        'polling.selected_ports' => [
            'description' => 'Pollning av valda portar',
            'help' => 'Polla endast de portar som är uppe och aktiverade',
        ],
        'ports_fdb_purge' => [
            'description' => 'Port FDB-poster äldre än',
            'help' => 'Rensningen görs av daily.sh',
        ],
        'ports_ipv4_neighbours' => [
            'description' => 'Uppslagsmetod för IPv4-grannar på portar',
            'help' => 'Metod för att slå upp IPv4-grannar när du visar portinformation. ARP använder ARP-tabellen för att hitta enheter med matchande IP- och MAC-adresser. Subnät hittar enheter med IP-adresser i samma subnät.',
        ],
        'ports_nac_purge' => [
            'description' => 'Port NAC-poster äldre än',
            'help' => 'Rensningen görs av daily.sh',
        ],
        'ports_page_default' => [
            'description' => 'Standardflik för portar',
            'help' => 'Fliken som öppnas som standard när du visar portar på enhetssidan',
        ],
        'ports_purge' => [
            'description' => 'Rensa bort borttagna portar',
            'help' => 'Rensningen görs av daily.sh',
        ],
        'processor.default_perc_warn' => [
            'description' => 'Standardvarningsgräns för processoranvändning i procent',
            'help' => 'Andel processoranvändning i procent som standard innan en varning utlöses.',
        ],
        'prometheus' => [
            'enable' => [
                'description' => 'Aktivera',
                'help' => 'Exporterar mätvärden till Prometheus Push Gateway',
            ],
            'url' => [
                'description' => 'URL',
                'help' => 'URL-adressen till Prometheus Push Gateway som data ska skickas till',
            ],
            'Job' => [
                'description' => 'Jobb',
                'help' => 'Jobbetikett för exporterade mätvärden',
            ],
            'attach_sysname' => [
                'description' => 'Bifoga enhetens sysName',
                'help' => 'Bifoga sysName-information till de data som skickas till Prometheus.',
            ],
            'prefix' => [
                'description' => 'Prefix',
                'help' => 'Valfri text som ska läggas till före namnen på exporterade mätvärden',
            ],
        ],
        'public_status' => [
            'description' => 'Visa status offentligt',
            'help' => 'Visar statusen för vissa enheter på inloggningssidan utan autentisering.',
        ],
        'routes_max_number' => [
            'description' => 'Max antal tillåtna rutter för upptäckt',
            'help' => 'Inga rutter upptäcks om routingtabellen är större än det här antalet',
        ],
        'default_port_group' => [
            'description' => 'Standardportgrupp',
            'help' => 'Nyupptäckta portar tilldelas den här portgruppen.',
        ],
        'nets' => [
            'description' => 'Nätverk för automatisk upptäckt',
            'help' => 'Nätverk där enheter upptäcks automatiskt.',
        ],
        'autodiscovery' => [
            'bgp' => [
                'description' => 'Aktivera upptäckt av BGP-grannar',
                'help' => 'Lägg till länkar och grannar utifrån BGP-motparter',
            ],
            'cdp_exclude' => [
                'platform_regexp' => [
                    'description' => 'Reguljärt uttryck för plattformar som CDP ska utesluta',
                    'help' => 'Lägg inte till enheter som hittas via CDP om sysName matchar det här reguljära uttrycket',
                ],
            ],
            'nets-exclude' => [
                'description' => 'Nätverk och IP-adresser som ska ignoreras',
                'help' => 'Nätverk och IP-adresser som inte upptäcks automatiskt. Detta utesluter även IP-adresser i nätverken för automatisk upptäckt.',
            ],
            'ospf' => [
                'description' => 'Aktivera upptäckt av OSPF-grannar',
                'help' => 'Lägg till länkar och grannar utifrån OSPF-motparter',
            ],
            'ospfv3' => [
                'description' => 'Aktivera upptäckt av OSPFv3-grannar',
                'help' => 'Lägg till länkar och grannar utifrån OSPFv3-motparter',
            ],
            'xdp' => [
                'description' => 'Aktivera xDP-upptäcktsprotokoll',
                'help' => 'Använd LLDP, CDP och andra protokoll för att upptäcka nätverkstopologin och grannar, och lägg sedan till dem i LibreNMS',
            ],
            'xdp_exclude' => [
                'sysname_regexp' => [
                    'description' => 'Reguljärt uttryck för sysName som xDP ska utesluta',
                    'help' => 'Lägg inte till enheter om sysName matchar det här reguljära uttrycket',
                ],
                'sysdesc_regexp' => [
                    'description' => 'Reguljärt uttryck för sysDescr som xDP ska utesluta',
                    'help' => 'Lägg inte till enheter om sysDescr matchar det här reguljära uttrycket',
                ],
            ],
        ],
        'radius' => [
            'default_roles' => [
                'description' => 'Standardanvändarroller',
                'help' => 'Anger vilka roller användaren får, såvida inte RADIUS skickar attribut som anger roller',
            ],
            'enforce_roles' => [
                'description' => 'Tvinga fram roller vid inloggning',
                'help' => 'Om inställningen är aktiverad sätts rollerna vid varje inloggning utifrån attributet Filter-ID eller radius.default_roles. Om den är inaktiverad sätts rollerna när användaren skapas och ändras sedan aldrig.',
            ],
        ],
        'rancid_configs' => [
            'description' => 'RANCID-konfigurationer',
            'help' => 'Katalogen med RANCID-konfigurationer, används för att visa konfigurationsdiffar på enhetssidorna',
        ],
        'rancid_repo_type' => [
            'description' => 'Typ av RANCID-arkiv',
            'help' => 'Typ av arkiv som RANCID använder, används för att visa konfigurationsdiffar på enhetssidorna',
        ],
        'rancid_repo_url' => [
            'description' => 'URL till RANCID-arkivet',
            'help' => 'URL till RANCID-arkivet, används för att peka på den GitWeb som visar ett bart Git-arkiv',
        ],
        'rancid_ignorecomments' => [
            'description' => 'Låt RANCID ignorera kommentarer',
            'help' => 'Ignorera kommentarer vid jämförelse av RANCID-konfigurationer, används för att visa konfigurationsdiffar på enhetssidorna',
        ],
        'reporting' => [
            'error' => [
                'description' => 'Skicka felrapporter',
                'help' => 'Skickar vissa fel till LibreNMS för analys och åtgärd',
            ],
            'usage' => [
                'description' => 'Skicka användningsrapporter',
                'help' => 'Rapporterar användning och versioner till LibreNMS. Gå till sidan Om för att ta bort anonym statistik. Du kan se statistiken på https://stats.librenms.org',
            ],
            'dump_errors' => [
                'description' => 'Dumpa felsökningsfel (gör din installation obrukbar)',
                'help' => 'Dumpar fel som normalt är dolda, så att en utvecklare kan hitta och åtgärda problemen.',
            ],
            'throttle' => [
                'description' => 'Begränsa antalet felrapporter',
                'help' => 'Rapporter skickas bara en gång per angivet antal sekunder. Utan den här gränsen kan ett fel i gemensam kod ge upphov till väldigt många rapporter. Ange 0 för att inaktivera begränsningen.',
            ],
        ],
        'rewrite_if' => [
            'description' => 'Skriv om ifDescr',
            'help' => 'Skriv om ifDescr så att gränssnittets typ och nummer tas bort. GigabitEthernet0/1 blir till exempel GigabitEthernet.',
        ],
        'route_purge' => [
            'description' => 'Ruttposter äldre än',
            'help' => 'Rensningen görs av daily.sh',
        ],
        'rrd' => [
            'heartbeat' => [
                'description' => 'Ändra värdet för RRD-heartbeat (standard 600)',
            ],
            'step' => [
                'description' => 'Ändra värdet för RRD-steg (standard 300) (varning!)',
                'help' => 'Varning! Om du ändrar detta utan att åtgärda RRD-filerna och ändra ditt pollningsschema slutar graferna att fungera. Mer information finns i dokumentationen.',
            ],
        ],
        'rrd_dir' => [
            'description' => 'Plats för RRD-filer',
            'help' => 'Platsen för RRD-filerna. Standard är katalogen rrd i LibreNMS-katalogen. Om du ändrar inställningen flyttas inte RRD-filerna.',
        ],
        'rrd_purge' => [
            'description' => 'RRD-filposter äldre än',
            'help' => 'Rensningen görs av daily.sh',
        ],
        'rrd_rra' => [
            'description' => 'RRD-formatinställningar',
            'help' => 'Värdena kan inte ändras utan att dina befintliga RRD-filer tas bort. Du kan öka eller minska storleken på varje RRA av prestandaskäl.',
        ],
        'rrdcached' => [
            'description' => 'Aktivera rrdcached (socket)',
            'help' => 'Ange platsen för rrdcached-socketen för att aktivera rrdcached. Det kan vara en unix- eller nätverkssocket (unix:/run/rrdcached.sock eller localhost:42217).',
        ],
        'rrdtool' => [
            'description' => 'Sökväg till rrdtool',
        ],
        'rrdtool_tune' => [
            'description' => 'Justera alla RRD-portfiler så att maxvärden används',
            'help' => 'Justera automatiskt maxvärdet för RRD-portfiler',
        ],
        'rrdtool_version' => [
            'description' => 'Ställer in versionen av rrdtool på din server',
            'help' => 'Version 1.5.5 och senare stöder alla funktioner som krävs. Ange inte en högre version än den du har installerad.',
        ],
        'schedule_type' => [
            'alerting' => [
                'description' => 'Larm',
                'help' => 'Schemaläggningsmetod för larmuppgifter. Legacy använder cron om crontab-posten finns. Legacy använder dispatcher-tjänsten om den äldre konfigurationsinställningen service_billing_enabled är satt till true.',
                'options' => [
                    'legacy' => 'Legacy (obegränsad)',
                    'cron' => 'Cron (alerts.php)',
                    'dispatcher' => 'Dispatcher-tjänsten',
                ],
            ],
            'billing' => [
                'description' => 'Fakturering',
                'help' => 'Schemaläggningsmetod för faktureringsuppgifter. Legacy använder cron om crontab-posten finns. Legacy använder dispatcher-tjänsten om den äldre konfigurationsinställningen service_billing_enabled är satt till true.',
                'options' => [
                    'legacy' => 'Legacy (obegränsad)',
                    'cron' => 'Cron (poll-billing.php och billing-calculate.php)',
                    'dispatcher' => 'Dispatcher-tjänsten',
                ],
            ],
            'discovery' => [
                'description' => 'Upptäckt',
                'help' => 'Schemaläggningsmetod för upptäcktsuppgifter. Legacy använder cron om crontab-posten finns. Legacy använder dispatcher-tjänsten om den äldre konfigurationsinställningen service_discovery_enabled är satt till true.',
                'options' => [
                    'legacy' => 'Legacy (obegränsad)',
                    'cron' => 'Cron (lnms device:discover)',
                    'dispatcher' => 'Dispatcher-tjänsten',
                ],
            ],
            'ping' => [
                'description' => 'Snabb ping',
                'help' => 'Schemaläggningsmetod för uppgifter med snabb ping. Legacy använder cron om crontab-posten finns. Legacy använder dispatcher-tjänsten om den äldre konfigurationsinställningen service_ping_enabled är satt till true.',
                'options' => [
                    'legacy' => 'Legacy (obegränsad)',
                    'disabled' => 'Inaktiverad (pingar endast under pollningen)',
                    'cron' => 'Cron (ping.php)',
                    'dispatcher' => 'Dispatcher-tjänsten',
                ],
            ],
            'poller' => [
                'description' => 'Poller',
                'help' => 'Schemaläggningsmetod för polleruppgifter. Legacy använder cron om crontab-posten finns. Legacy använder dispatcher-tjänsten om den äldre konfigurationsinställningen service_poller_enabled är satt till true.',
                'options' => [
                    'legacy' => 'Legacy (obegränsad)',
                    'cron' => 'Cron (poller.php)',
                    'dispatcher' => 'Dispatcher-tjänsten',
                ],
            ],
            'services' => [
                'description' => 'Tjänster',
                'help' => 'Schemaläggningsmetod för tjänsteuppgifter. Legacy använder cron om crontab-posten finns. Legacy använder dispatcher-tjänsten om den äldre konfigurationsinställningen service_services_enabled är satt till true.',
                'options' => [
                    'legacy' => 'Legacy (obegränsad)',
                    'cron' => 'Cron (check-services.php)',
                    'dispatcher' => 'Dispatcher-tjänsten',
                ],
            ],
        ],
        'sensors' => [
            'guess_limits' => [
                'description' => 'Gissa sensorgränser',
                'help' => 'Om inställningen är aktiverad gissas sensorgränserna utifrån sensorns typ och värde. Det är inte alltid korrekt och kan ge felaktiga gränser.',
            ],
        ],
        'service_master_timeout' => [
            'description' => 'Timeout för huvuddispatchern',
            'help' => 'Tiden innan huvudlåset löper ut. Om huvudnoden stannar tar en annan nod över efter den här tiden. Om det tar längre tid än timeouten att fördela arbetet får du flera huvudnoder.',
        ],
        'service_ping_frequency' => [
            'description' => 'Pingfrekvens',
            'help' => 'Hur ofta snabb ping ska köras mot alla enheter.',
        ],
        'service_poller_workers' => [
            'description' => 'Pollerprocesser',
            'help' => 'Antal pollerprocesser som ska startas. Anger standardvärdet för alla noder.',
        ],
        'service_poller_frequency' => [
            'description' => 'Pollerfrekvens (varning!)',
            'help' => 'Hur ofta enheter ska pollas. Anger standardvärdet för alla noder. Varning! Det här bör normalt lämnas tomt så att det följer inställningen för RRD-steg, annars kan graferna sluta fungera. Mer information finns i dokumentationen.',
        ],
        'service_poller_down_retry' => [
            'description' => 'Nytt försök när enheten är nere',
            'help' => 'Hur länge systemet ska vänta innan ett nytt försök görs när en enhet är nere vid pollningen. Anger standardvärdet för alla noder.',
        ],
        'service_discovery_workers' => [
            'description' => 'Upptäcktsprocesser',
            'help' => 'Antal upptäcktsprocesser som ska köras. Ett för högt värde kan orsaka överbelastning. Anger standardvärdet för alla noder.',
        ],
        'service_discovery_frequency' => [
            'description' => 'Upptäcktsfrekvens',
            'help' => 'Hur ofta enhetsupptäckt ska köras. Anger standardvärdet för alla noder. Standard är 4 gånger per dygn.',
        ],
        'service_services_workers' => [
            'description' => 'Tjänsteprocesser',
            'help' => 'Antal tjänsteprocesser. Anger standardvärdet för alla noder.',
        ],
        'service_services_frequency' => [
            'description' => 'Tjänstefrekvens',
            'help' => 'Hur ofta tjänster ska köras. Detta måste stämma överens med pollerfrekvensen. Anger standardvärdet för alla noder.',
        ],
        'service_billing_frequency' => [
            'description' => 'Faktureringsfrekvens',
            'help' => 'Hur ofta faktureringsdata ska samlas in. Anger standardvärdet för alla noder.',
        ],
        'service_billing_calculate_frequency' => [
            'description' => 'Frekvens för faktureringsberäkning',
            'help' => 'Hur ofta fakturaunderlaget ska beräknas. Anger standardvärdet för alla noder.',
        ],
        'service_alerting_frequency' => [
            'description' => 'Larmfrekvens',
            'help' => 'Hur ofta larmreglerna ska kontrolleras. Data uppdateras endast med pollerfrekvensen. Anger standardvärdet för alla noder.',
        ],
        'service_update_enabled' => [
            'description' => 'Dagligt underhåll aktiverat',
            'help' => 'Kör underhållsskriptet daily.sh och starta om dispatcher-tjänsten efteråt. Anger standardvärdet för alla noder.',
        ],
        'service_update_frequency' => [
            'description' => 'Underhållsfrekvens',
            'help' => 'Hur ofta dagligt underhåll ska köras. Standard är 1 dygn. Ändra inte detta. Anger standardvärdet för alla noder.',
        ],
        'service_loglevel' => [
            'description' => 'Loggnivå',
            'help' => 'Loggnivå för dispatcher-tjänsten. Anger standardvärdet för alla noder.',
        ],
        'service_watchdog_enabled' => [
            'description' => 'Watchdog aktiverad',
            'help' => 'Watchdog övervakar loggfilen och startar om tjänsten om loggfilen inte uppdateras. Anger standardvärdet för alla noder.',
        ],
        'service_watchdog_log' => [
            'description' => 'Loggfil att övervaka',
            'help' => 'Standardvärdet är LibreNMS loggfil. Anger standardvärdet för alla noder.',
        ],
        'service_health_file' => [
            'description' => 'Hälsofil för tjänsten',
            'help' => 'Sökväg till den hälsofil som bekräftar att dispatcher-tjänsten körs',
        ],
        'shorthost_target_length' => [
            'description' => 'Maxlängd för kortat värdnamn',
            'help' => 'Förkortar värdnamnet till den här maxlängden, men behåller hela subdomändelar',
        ],
        'show_locations' => [
            'description' => 'Visa platser i navigering',
            'help' => 'Visa platsen i navigeringsfältet',
        ],
        'show_locations_dropdown' => [
            'description' => 'Visa platser i rullgardinsmenyn',
            'help' => 'Visa platsen i rullgardinsmenyn',
        ],
        'show_services' => [
            'description' => 'Visa tjänster i navigering',
            'help' => 'Visa tjänsterna i navigeringsfältet',
        ],
        'site_style' => [
            'description' => 'Standardtema',
            'options' => [
                'device' => 'Enhet',
                'blue' => 'Blå',
                'dark' => 'Mörk',
                'light' => 'Ljus',
                'mono' => 'Mono',
            ],
        ],
        'snmp' => [
            'transports' => [
                'description' => 'Transport (prioritet)',
                'help' => 'Välj vilka transporter som ska vara aktiverade och lägg dem i den ordning de ska provas.',
            ],
            'version' => [
                'description' => 'Version (prioritet)',
                'help' => 'Välj vilka versioner som ska vara aktiverade och lägg dem i den ordning de ska provas.',
            ],
            'community' => [
                'description' => 'Community-strängar (prioritet)',
                'help' => 'Ange community-strängarna för v1 och v2c och lägg dem i den ordning de ska provas',
            ],
            'max_oid' => [
                'description' => 'Max antal OID:er',
                'help' => 'Maximalt antal OID:er per fråga. Du kan åsidosätta detta på OS- och enhetsnivå.',
            ],
            'max_repeaters' => [
                'description' => 'Max antal repeaters',
                'help' => 'Ange hur många repeaters som ska användas vid SNMP bulk-förfrågningar',
            ],
            'oids' => [
                'no_bulk' => [
                    'description' => 'Inaktivera SNMP bulk för OID:er',
                    'help' => 'Inaktivera SNMP bulk-åtgärden för vissa OID:er. Vanligtvis bör detta anges på ett OS i stället. Formatet är MIB::OID.',
                ],
                'unordered' => [
                    'description' => 'Tillåt SNMP-svar i fel ordning för OID:er',
                    'help' => 'Ignorera OID:er som kommer i fel ordning i SNMP-svar för vissa OID:er. OID:er i fel ordning kan orsaka en OID-loop under en snmpwalk. Vanligtvis bör detta anges på ett OS i stället. Formatet är MIB::OID.',
                ],
            ],
            'port' => [
                'description' => 'Port',
                'help' => 'Ange den TCP-/UDP-port som ska användas för SNMP',
            ],
            'timeout' => [
                'description' => 'Timeout',
                'help' => 'SNMP-timeout i sekunder',
            ],
            'retries' => [
                'description' => 'Antal försök',
                'help' => 'Hur många gånger frågan ska göras om',
            ],
            'v3' => [
                'description' => 'SNMP v3-autentisering (prioritet)',
                'help' => 'Ange variablerna för v3-autentisering och lägg dem i den ordning de ska provas',
                'auth' => 'Autentisering',
                'crypto' => 'Kryptering',
                'fields' => [
                    'authalgo' => 'Algoritm',
                    'authlevel' => 'Nivå',
                    'authname' => 'Användarnamn',
                    'authpass' => 'Lösenord',
                    'cryptoalgo' => 'Algoritm',
                    'cryptopass' => 'Lösenord',
                ],
                'level' => [
                    'noAuthNoPriv' => 'Ingen autentisering, ingen sekretess',
                    'authNoPriv' => 'Autentisering, ingen sekretess',
                    'authPriv' => 'Autentisering och sekretess',
                ],
            ],
        ],
        'snmpbulkwalk' => [
            'description' => 'Sökväg till snmpbulkwalk',
        ],
        'snmpget' => [
            'description' => 'Sökväg till snmpget',
        ],
        'snmpgetnext' => [
            'description' => 'Sökväg till snmpgetnext',
        ],
        'snmptranslate' => [
            'description' => 'Sökväg till snmptranslate',
        ],
        'snmptraps' => [
            'eventlog' => [
                'description' => 'Skapa händelseloggpost för SNMP-trappar',
                'help' => 'Oberoende av den åtgärd som är kopplad till trappen',
            ],
            'eventlog_detailed' => [
                'description' => 'Aktivera detaljerade loggar',
                'help' => 'Lägg till alla OID:er som tas emot med trappen i händelseloggen',
            ],
        ],
        'snmpwalk' => [
            'description' => 'Sökväg till snmpwalk',
        ],
        'ssl_certificates' => [
            'auto_discover' => [
                'description' => 'Upptäck SSL-certifikat automatiskt',
                'help' => 'Upptäck SSL-certifikat automatiskt',
            ],
            'skip_hosts' => [
                'description' => 'Hoppa över värdar',
                'help' => 'Hoppa över värdar vid upptäckt av SSL-certifikat',
            ],
            'days_until_expiry_warning' => [
                'description' => 'Varning (dagar)',
                'help' => 'Antal dagar före certifikatets utgång då en varning ska utlösas',
            ],
            'days_until_expiry_danger' => [
                'description' => 'Fara (dagar)',
                'help' => 'Antal dagar före certifikatets utgång då ett kritiskt larm ska utlösas',
            ],
        ],
        'sso' => [
            'create_users' => [
                'description' => 'Skapa användare',
                'help' => 'Skapa nya användare vid inloggning.',
            ],
            'descr_attr' => [
                'description' => 'Attribut för användarbeskrivning',
                'help' => 'Attributet som innehåller en beskrivning av användaren.',
            ],
            'email_attr' => [
                'description' => 'E-postattribut',
                'help' => 'Attributet som innehåller användarens e-postadress.',
            ],
            'group_attr' => [
                'description' => 'Gruppattribut',
                'help' => 'Attributet som innehåller gruppinformationen om mappning används.',
            ],
            'group_delimiter' => [
                'description' => 'Gruppavgränsare',
                'help' => 'Avgränsaren som ska användas för gruppinformation om gruppstrategin mappning används.',
            ],
            'group_filter' => [
                'description' => 'Reguljärt uttryck för gruppfilter',
                'help' => 'Används för att filtrera gruppinformation om gruppstrategin mappning används.',
            ],
            'group_level_map' => [
                'description' => 'Mappning av grupper till nivåer',
                'help' => 'Mappning från grupp till roll.',
            ],
            'group_strategy' => [
                'description' => 'Gruppstrategi',
                'help' => 'Metoden för gruppmappning.',
            ],
            'level_attr' => [
                'description' => 'Nivåattribut',
                'help' => 'Attributet som ska användas om gruppstrategin attribut används.',
            ],
            'mode' => [
                'description' => 'Läge',
                'help' => 'Använd miljövariablerna eller HTTP-huvudet.',
            ],
            'realname_attr' => [
                'description' => 'Attribut för riktigt namn',
                'help' => 'Attributet som innehåller användarens riktiga namn.',
            ],
            'static_level' => [
                'description' => 'Statisk nivå',
                'help' => 'Om statisk nivå används: rollnivåvärdet för alla med åtkomst.',
            ],
            'trusted_proxies' => [
                'description' => 'Betrodda proxyservrar',
                'help' => 'En lista över betrodda proxyservrar.',
            ],
            'update_users' => [
                'description' => 'Uppdatera användare',
                'help' => 'Uppdatera användare vid inloggning.',
            ],
            'user_attr' => [
                'description' => 'Användarattribut',
                'help' => 'Attributet som innehåller användarnamnet.',
            ],
        ],
        'storage_perc_warn' => [
            'description' => 'Standardvarningsgräns för lagringsanvändning i procent',
            'help' => 'Andel använt lagringsutrymme i procent som standard innan en varning utlöses. 0 inaktiverar varningen.',
        ],
        'syslog_filter' => [
            'description' => 'Filtrera bort syslog-meddelanden som innehåller',
        ],
        'syslog_purge' => [
            'description' => 'Syslog-poster äldre än',
            'help' => 'Rensningen görs av daily.sh',
        ],
        'title_image' => [
            'description' => 'Titelbild',
            'help' => 'Åsidosätter standardtitelbilden. En SVG från samma server bäddas in, och den SVG:n kan använda currentColor för att matcha det aktuella temat.',
        ],
        'traceroute' => [
            'description' => 'Sökväg till traceroute',
        ],
        'twofactor' => [
            'description' => 'Tvåfaktorsautentisering',
            'help' => 'Tillåt användare att aktivera och använda tidsbaserade (TOTP) eller räknarbaserade (HOTP) engångslösenord (OTP)',
        ],
        'twofactor_lock' => [
            'description' => 'Spärrtid för tvåfaktorsautentisering (sekunder)',
            'help' => 'Spärrtid i sekunder efter tre misslyckade tvåfaktorsförsök i rad. Användaren uppmanas att vänta så länge. Ange 0 för permanent kontolåsning, med ett meddelande om att kontakta en administratör.',
        ],
        'unimus' => [
            'api_version' => [
                'description' => 'Unimus API-version',
            ],
            'enabled' => [
                'description' => 'Aktivera Unimus-support',
                'help' => 'Visa säkerhetskopior av enheternas konfiguration från Unimus på enhetens konfigurationsflik',
            ],
            'token' => [
                'description' => 'Unimus API-token',
                'help' => 'API-token som har skapats i Unimus (grundläggande eller skrivskyddad åtkomst räcker)',
            ],
            'url' => [
                'description' => 'Unimus URL',
                'help' => 'Bas-URL till din Unimus-server, till exempel: http://unimus.example.com:8085',
            ],
        ],
        'unix-agent' => [
            'connection-timeout' => [
                'description' => 'Anslutningstimeout för Unix-agenten',
            ],
            'port' => [
                'description' => 'Standardport för Unix-agenten',
                'help' => 'Standardport för Unix-agenten (check_mk)',
            ],
            'read-timeout' => [
                'description' => 'Lästimeout för Unix-agenten',
            ],
        ],
        'update' => [
            'description' => 'Aktivera uppdateringar i ./daily.sh',
        ],
        'update_channel' => [
            'description' => 'Uppdateringskanal',
            'options' => [
                'master' => 'Dagligen',
                'release' => 'Månadsvis',
            ],
        ],
        'update_on_days' => [
            'description' => 'Kör bara uppdateringar dessa dagar',
            'help' => 'Om värdet är angivet kör daily.sh bara koduppdateringar när dagens datum matchar något av dessa värden: monday-sunday eller mon-sun. Lämna tomt för att tillåta uppdateringar varje dag.',
        ],
        'uptime_warning' => [
            'description' => 'Visa enheten som varning om drifttiden understiger (sekunder)',
            'help' => 'Visar en enhet som varning om drifttiden understiger det här värdet. Statusen på anpassade kartor använder också den här inställningen. 0 inaktiverar varningen. Standard är 24 h.',
        ],
        'virsh' => [
            'description' => 'Sökväg till virsh',
        ],
        'web_mouseover' => [
            'description' => 'Aktivera mouseover',
            'help' => 'Aktiverar graferna som visas när du håller muspekaren över i webbgränssnittet',
        ],
        'webui' => [
            'scheduled_maintenance_default_behavior' => [
                'description' => 'Standardbeteende',
                'help' => 'Standardalternativet för fältet Beteende när du hanterar schemalagt underhåll.',
            ],
            'alert_map_compact' => [
                'description' => 'Kompakt vy för larmkartan',
                'help' => 'Larmkartvy med små indikatorer',
            ],
            'alert_map_sort_status' => [
                'description' => 'Sortera efter status',
                'help' => 'Sortera larm efter status',
            ],
            'alert_map_use_device_groups' => [
                'description' => 'Använd filter för enhetsgrupper',
                'help' => 'Aktivera användning av filter för enhetsgrupper',
            ],
            'alert_map_box_size' => [
                'description' => 'Bredd på larmrutan',
                'help' => 'Rutornas bredd i pixlar i fullständig vy',
            ],
            'availability_map_box_size' => [
                'description' => 'Bredd på tillgänglighetsrutan',
                'help' => 'Rutornas bredd i pixlar i fullständig vy',
            ],
            'availability_map_compact' => [
                'description' => 'Kompakt vy för tillgänglighetskartan',
                'help' => 'Tillgänglighetskartvy med små indikatorer',
            ],
            'availability_map_sort_status' => [
                'description' => 'Sortera efter status',
                'help' => 'Sortera enheter och tjänster efter status',
            ],
            'availability_map_use_device_groups' => [
                'description' => 'Använd filter för enhetsgrupper',
                'help' => 'Aktivera användning av filter för enhetsgrupper',
            ],
            'custom_css' => [
                'description' => 'Egen CSS',
                'help' => 'Lägg till egen CSS i webbgränssnittet',
            ],
            'default_dashboard_id' => [
                'description' => 'Standardinstrumentpanel',
                'help' => 'Globalt standardvärde för dashboard_id för alla användare som inte har angett ett eget',
            ],
            'dynamic_graphs' => [
                'description' => 'Aktivera dynamiska grafer',
                'help' => 'Aktivera dynamiska grafer. Dynamiska grafer stöder zoomning och panorering.',
            ],
            'global_search_result_limit' => [
                'description' => 'Ställ in maxgränsen för sökresultat',
                'help' => 'Gräns för globala sökresultat',
            ],
            'global_search.arp' => [
                'description' => 'Global sökning i ARP',
                'help' => 'Sök i enheternas ARP-cachar för att hitta var enheter är anslutna',
            ],
            'global_search.fdb' => [
                'description' => 'Global sökning i FDB-poster',
                'help' => 'Sök i enheternas vidarebefordringstabeller för att hitta var enheter är anslutna',
            ],
            'global_search.eventlogs' => [
                'description' => 'Global sökning i händelseloggar',
                'help' => 'Hitta matchande händelseloggar i de globala sökresultaten',
            ],
            'global_search.health' => [
                'description' => 'Global sökning i hälsodata',
                'help' => 'Hitta matchande hälsosensorer i de globala sökresultaten',
            ],
            'global_search.ports' => [
                'description' => 'Global sökning i portar',
                'help' => 'Hitta matchande portar i de globala sökresultaten',
            ],
            'global_search.routing' => [
                'description' => 'Global sökning i routingmotparter',
                'help' => 'Hitta matchande routingmotparter i de globala sökresultaten',
            ],
            'graph_stacked' => [
                'description' => 'Använd staplade grafer',
                'help' => 'Visa staplade grafer i stället för inverterade grafer',
            ],
            'graph_type' => [
                'description' => 'Ställ in graftypen',
                'help' => 'Ställ in standardgraftypen',
                'options' => [
                    'png' => 'PNG',
                    'svg' => 'SVG',
                ],
            ],
            'min_graph_height' => [
                'description' => 'Ställ in den lägsta grafhöjden',
                'help' => 'Minsta grafhöjd (standard: 300)',
            ],
            'graph_stat_percentile_disable' => [
                'description' => 'Inaktivera percentil för statistikgrafer globalt',
                'help' => 'Döljer percentilvärdena och percentillinjerna i de grafer som visar dem',
            ],
        ],
        'device_display_default' => [
            'description' => 'Standardmall för enhetsvisningsnamn',
            'help' => 'Anger standardvisningsnamnet för alla enheter. Du kan åsidosätta det per enhet. Värdnamn/IP visar det värdnamn eller den IP-adress som enheten lades till med. sysName visar sysName från SNMP. Värdnamn eller sysName visar värdnamnet, eller sysName om värdnamnet är en IP-adress.',
            'options' => [
                'hostname' => 'Värdnamn / IP',
                'sysName_fallback' => 'Värdnamn, med sysName som reserv för IP-adresser',
                'sysName' => 'sysName',
                'ip' => 'IP (från värdnamnets IP-adress eller uppslagen)',
            ],
        ],
        'device_location_map_open' => [
            'description' => 'Platskartan öppen',
            'help' => 'Platskartan visas som standard',
        ],
        'device_location_map_show_devices' => [
            'description' => 'Visa enheter på platskartan',
            'help' => 'Visa alla enheter på platskartan när den visas',
        ],
        'device_location_map_show_device_dependencies' => [
            'description' => 'Visa enhetsberoenden på platskartan',
            'help' => 'Visa länkar mellan enheter på platskartan utifrån beroenden till överordnade enheter',
        ],
        'device_stats_avg_factor' => [
            'description' => 'Medelvärdesfaktor',
            'help' => 'Ett glidande medelvärde beräknas med en exponentiellt viktad funktion för glidande medelvärde. Faktorn styr hur mycket det aktuella värdet påverkar medelvärdet. Värden närmare 1 gör att medelvärdet ändras snabbare.',
        ],
        'smokeping.integration' => [
            'description' => 'Aktivera',
            'help' => 'Aktivera integration med Smokeping',
        ],
        'smokeping.dir' => [
            'description' => 'Sökväg till RRD-filerna',
            'help' => 'Fullständig sökväg till Smokepings RRD-filer',
        ],
        'smokeping.pings' => [
            'description' => 'Pingar',
            'help' => 'Antal pingar konfigurerade i Smokeping',
        ],
        'smokeping.url' => [
            'description' => 'URL till Smokeping',
            'help' => 'Fullständig URL till Smokepings gränssnitt',
        ],
    ],
    'twofactor' => [
        'description' => 'Aktivera tvåfaktorsautentisering',
        'help' => 'Aktiverar den inbyggda tvåfaktorsautentiseringen. Du måste konfigurera varje konto för att den ska bli aktiv.',
    ],
    'units' => [
        'days' => 'dagar',
        'ms' => 'ms',
        'seconds' => 'sekunder',
        'percent' => '%',
    ],
    'validate' => [
        'boolean' => ':value är inte ett giltigt booleskt värde',
        'color' => ':value är inte en giltig hex-färgkod',
        'email' => ':value är inte en giltig e-postadress',
        'float' => ':value är inte ett flyttal',
        'integer' => ':value är inte ett heltal',
        'password' => 'Lösenordet är felaktigt',
        'select' => ':value är inte ett tillåtet värde',
        'text' => ':value är inte tillåtet',
        'array' => 'Ogiltigt format',
        'password-array' => 'Ogiltigt format',
        'executable' => ':value är inte en giltig körbar fil',
        'directory' => ':value är inte en giltig katalog',
    ],
];
