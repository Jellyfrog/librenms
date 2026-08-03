# Command line tools

This is a short list of command line tools. Possibly, some are missing.
If you think that a tool is missing, ask us or send a pull request :-)

# purge-ports.php

This script gives CLI access to the "delete port" function of the WebUI.
This is useful when you remove old ports after large changes in the
network, or when you work on the poller/discovery functions.

```
LibreNMS Port purge tool
-p port_id  Purge single port by it's port-id
-f file     Purge a list of ports, read port-ids from _file_, one on each line
            A filename of - means reading from STDIN.
```

# Querying port IDs from the database

One easy method to get port IDs is a query on the SQL database.

To get all deleted ports from the database, use this
query:

```bash
echo 'SELECT port_id, hostname, ifDescr FROM ports, devices WHERE devices.device_id = ports.device_id AND deleted = 1' | mysql -h your_DB_server -u your_DB_user -p --skip-column-names your_DB_name
```

When you are sure that the list of ports is correct, and you want to
delete all of them, write the list into a file. Then start
purge-ports.php with that file as input:

```
echo 'SELECT port_id FROM ports, devices WHERE devices.device_id = ports.device_id AND deleted = 1' | mysql -h your_DB_server -u your_DB_user -p --skip-column-names your_DB_name > ports_to_delete
./purge-port.php -f ports_to_delete
```
