# GateOne integration

There is simple integration for
[GateOne](https://github.com/liftoff/GateOne). The system sends you
to your Gateone command line frontend to get access to your
equipment. (At this time, this operates only with SSH)

GateOne itself is not included in LibreNMS. You must
install it separately, on the same infrastructure as LibreNMS,
or as a fully  separate appliance. The installation is not part
of this document.

The config is simple. Include this in your `config.php`:

```php
$config['gateone']['server'] = 'http://<your_gateone_url/';
```

**Note:** You *must* use the full url including the trailing `/`!

The system can also add the Librenms user that is logged in to the start of the
SSH connection URL that it creates, for example, `ssh://admin@localhost`\ To
enable this, put this in your `config.php`:

```php
$config['gateone']['use_librenms_user'] = true;
```
