# Environment Variables

You can set some LibreNMS settings through the environment or
through the .env file.

## Database

Set the variables to connect to the database.  The default values are shown below.

```dotenv
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=librenms
DB_USERNAME=librenms
DB_PASSWORD=
DB_SOCKET=
```

## Trusted Reverse Proxies

A comma separated list of trusted reverse proxy IPs or CIDR.

The default value is '127.0.0.1'. This permits reverse proxies only on the localhost.

Do not use these options, because they decrease security.
`'*'` means trust all proxies
`'**'` means trust all proxies up the chain.

```dotenv
APP_TRUSTED_PROXIES=192.168.1.0/24,192.167.8.20
```

## Base url

Set the base url for generated urls.

This is necessary when you use signed graph urls for alerting. It can
be necessary when you use reverse proxies together with a subdirectory.

Usually, LibreNMS makes correct URLs (specially if your
proxy variables are set correctly)

```dotenv
APP_URL=http://librenms/
```

## User / Group

The user and group that LibreNMS operates as.
If the group is not set, the default is the same as the user.

```dotenv
LIBRENMS_USER=librenms
LIBRENMS_GROUP=librenms
```

## Debug

Increases the quantity of information shown when an error occurs.

> WARNING: This can release private information. Do not keep this enabled.

```dotenv
APP_DEBUG=true
```
