# Get ready to contribute to LibreNMS

This document helps you set up your local environment,
so that you can contribute code to the LibreNMS project.

## Setting up a development environment

When you start to develop, it is attractive to make changes on
your production server. But that makes work harder for you.
Use some time to set up a location to work on code changes. This
helps very much.

Possible options:

- A Linux computer, VM, or container
- Another directory on your LibreNMS server
- Windows Subsystem for Linux

### Set up your development git clone

1. Follow the [documentation on using git](Using-Git.md)

1. Install development dependencies `./scripts/composer_wrapper.php install`

1. Set variables in `.env`, which include the database settings.  This can be
   a local or remote MySQL server, which includes your production DB.

    ```dotenv
    APP_ENV=local
    APP_DEBUG=true
    ...
    ```

1. Start a development webserver `./lnms serve`

1. Access the Web UI at <http://localhost:8000>

### Automated testing

LibreNMS uses continuous integration to test code changes. This helps
decrease bugs.  It also helps make sure that the changes that you contribute
do not break in the future. More information is in our [Validating Code Documentation](Validating-Code.md)

The default database connection for automated testing is `testing`.

To replace the database parameters for unit tests, configure your
`.env` file. The default values (from `config/database.php`)
are:

```dotenv
DB_TEST_DRIVER="mysql"   # PDO driver
DB_TEST_HOST="localhost" # hostname or IP address
DB_TEST_PORT=""          # port
DB_TEST_DATABASE="librenms_phpunit_78hunjuybybh" # database
DB_TEST_USERNAME="root"  # username
DB_TEST_PASSWORD=""      # password
DB_TEST_SOCKET=""        # unix socket path
```

### Polling debug output

To see detailed information, run your polling code in debug
mode. Add `-vv`, which tries to hide sensitive data. `-vvv`
gives the full debug output.

```bash
lnms device:discover -vv HOSTNAME
lnms device:poll -vv HOSTNAME
```

### Inspecting variables

Sometimes you want to see what a variable contains (such as the
data that an snmpwalk returns). You can dump one or more variables and
stop execution with the dd() function.

```php
dd($variable1, $variable2);
```

### Inspecting web pages

When you install the development dependencies and set APP_DEBUG, this enables
the [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar)
With it, you can examine page generation and errors directly in
your web browser.

### Better code completion in IDEs and editors

You can make some files that make code completion better. (These files
do not get updates automatically. Thus, run these commands
again at intervals)

```bash
./lnms ide-helper:generate
./lnms ide-helper:models -N
```

### Emulating devices

You can capture and emulate devices with
[Snmpsim](https://github.com/etingof/snmpsim).  LibreNMS has a set of
scripts that make it easier to work with snmprec files.
[LibreNMS Snmpsim helpers](https://github.com/librenms/librenms-snmpsim)

### Laravel documentation

You can learn much about how LibreNMS operates from the [Laravel Documentation](https://laravel.com/docs/)
