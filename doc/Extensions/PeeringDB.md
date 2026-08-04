# PeeringDB Support

LibreNMS has integration with PeeringDB. It matches your BGP sessions
with the peering exchanges to which you are connected.

Enable the integration in the WebUI

!!! setting "external/peeringdb"
    ```bash
    lnms config:set peeringdb.enabled true
    ```

The system collects the data the next time that daily.sh runs. You can
also start this manually with `php daily.php -f peeringdb`. The
initial collection has a delay of a random length of time, to prevent
too much load on the PeeringDB API.

When this is enabled, you have one more menu item, under Routing -> PeeringDB
