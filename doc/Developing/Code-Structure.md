# Code structure

This document gives an overview of the code structure
in LibreNMS. We go through the main directories
and give information about how and when they are used.
LibreNMS now uses [Laravel](https://laravel.com/docs/) for much of
its frontend (webui) and database code. Much of the Laravel
documentation applies: <https://laravel.com/docs/structure>

The (filtered) structure tree below shows the
directories that are the most interesting during development:

```text
.
├─ app
├─ database
│  └─ migrations
├─ doc
├─ html
│  ├─ css
│  │  └─ custom
│  └─ js
├─ includes
│  ├─ definitions
│  ├─ discovery
│  ├─ html
│  │  ├─ forms
│  │  ├─ graphs
│  │  ├─ pages
│  │  └─ reports
│  └─ polling
├─ LibreNMS
├─ logs
├─ mibs
└─ rrd
```

### doc/

This is the location of all the documentation for LibreNMS. It is in
GitHub markdown format. You can see it [online](@= config.site_url =@)

### app/

Most Laravel and Eloquent classes must be under this directory.

### LibreNMS/

Classes that do not belong to the Laravel application belong in this
directory, with a directory structure that matches the namespace.  One
class for each file. Refer to [PSR-0](http://www.php-fig.org/psr/psr-0/) for details.

### html/

All legacy web accessible files are here. New pages must
obey the Laravel conventions.

### html/api_v0.php

This is the API routing file. It sends users to the correct API
function, from the API endpoint call.

### html/index.php

This is the main file through which all links in LibreNMS are parsed.
It loads most of the applicable includes that the
control panel needs. It also loads CSS and JS files.

### html/css/

All used CSS files are here.

### html/css/custom/

You can put custom css files into this directory. They do not cause problems with auto updates

### html/js/

All used JS files are here.

### includes/

This directory is large. It contains all the files that make the cli
and polling / discovery operate.  This code is not
accessible from Laravel code at this time (this is intentional).

### includes/discovery/, includes/polling/

All the discovery and polling code. The format for discovery and
polling is usually almost the same. Both contain modules, and
the files in the applicable directories match that module. For
example, to update the os detection for a device,
look in `includes/discovery/os/` for a file with the name of the
operating system, such as linux:
`includes/discovery/linux.inc.php`. In this file, you update or
add support for newer OS'. This is the same for polling.

### includes/html/

Most of the website core files are
here. These files usually contain functions, or frequently used
code segments. You can include them where necessary, and
not make copies of the code.

### includes/html/forms/

This directory contains all the files that an ajax call to
ajax/form includes dynamically.

### includes/html/api_functions.inc.php

All the functions and calls for the API are here.

### includes/html/functions.inc.php

This contains most of the functions used through the standard
web ui.

### includes/html/graphs/

This directory contains global and OS specific graph definitions.

### includes/html/reports/

This contains the files that make the PDF reports available to
the user. `html/pdf.php` calls them dynamically, from
the report that the user asks for.

### includes/html/table/

This directory contains all the ajax calls that make the
table of data. Most are converted. Thus, if you plan to
add a new table of data, do it here for all the back
end data calls.

### includes/html/pages/

This directory contains the URL structure of the Web UI. For
example, `/devices/` is a call to
`includes/html/pages/devices.inc.php`, and `/device/tab=ports/` is
`includes/html/pages/device/ports.inc.php`.

### logs/

Contains the main librenms.log file by default. It can also contain
the logs of your web server, poller logs, and other items.

### mibs/

All the mibs are here.  Usually, standard mibs
must be in the root directory, and vendor mibs must be in
their own subdirectory.

### rrd/

The system creates all the rrd files here. It
keeps them in a directory with the name of the device hostname.

### database/migrations

Contains all the database migrations.  Refer to the Laravel docs for more
information: <https://laravel.com/docs/migrations>

Usually, to create a new table, run:

```bash
php artisan make:model ModelName -m -c -r
```
