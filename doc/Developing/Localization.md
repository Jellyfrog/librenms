## Translating LibreNMS

LibreNMS uses [Laravel Localization](https://laravel.com/docs/localization) for translations.

- Common strings (and some others) are in `lang/<locale>.json`.
- Most strings are in `lang/<locale>/<group>.php`. The PHP files return an array. The
  system flattens it to dot notation (e.g., `['nav' => ['devices' => 'Devices']]` in the file menu.php
  becomes `menu.nav.devices`).

### Finding untranslated strings

Note: The Lost in Translation tool comes from a development dependency. Make sure that the Composer dev requirements are installed before you run it:

```bash
./scripts/composer_wrapper.php install --dev
```

Use the Lost in Translation command to show the missing strings for a locale:
```bash
./artisan lost-in-translation:find <locale>
```

You can also start it through lnms, if available in your environment:

```bash
./lnms lost-in-translation:find <locale>
```

### Updating frontend translations

To manually update the frontend translations, you can run:

```bash
./lnms translation:generate
```

This procedure runs during an update. Thus, usual users do not have to run this.
