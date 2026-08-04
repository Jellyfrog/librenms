## Versioning

Versioning an API is not easy. We examined many
options for how to do this.

At this time, we use versioning in the API end point
itself: `/api/v0`. The API is new and in active
development. Thus, we decided that v0 is the best start point,
to show that it is in development.

## Tokens

To get access to the token end points, you must
authenticate with a token. You can create tokens directly in
the LibreNMS web interface. Go to `/api-access/`.

- Click on 'Create API access token'.
- Select the user for whom you make the token.
- Enter an optional description.
- Click Create API Token.

## Endpoints

This documentation tells about the end points and shows examples.
But the design of the API lets you go
through it without knowledge of the available API routes.

To do this, first call `/api/v0`:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' https://librenms.org/api/v0
```

Output:

```json
{
 "list_bgp": "https://librenms.org/api/v0/bgp",
  ...
 "edit_rule": "https://librenms.org/api/v0/rules"
}
```

## Input

There are three different methods to give input to the API. Sometimes
you use a combination of two or three of them.

- Pass parameters in the api route. For example, to get
  the details of a device, you pass the hostname of the device in the route: `/api/v0/devices/:hostname`.
- Pass parameters in the query string. For example, you can list
  all devices on your installation, but limit the output to devices that are
  down: `/api/v0/devices?type=down`
- Pass data in JSON. This is mostly for when you add or
  update information through the API, for example when you add a new device:

```curl
curl -X POST -d '{"hostname":"localhost.localdomain","version":"v1","community":"public"}' -H 'X-Auth-Token: YOURAPITOKENHERE' https://librenms.org/api/v0/devices
```

## Output

The API gives two output types:

- JSON: Most API responses give json, as shown in the example for
  the API endpoint call.
- PNG: This is for requests for an image, such as a graph for a switch port.

## Endpoint Categories

- [Devices](Devices.md)
- [DeviceGroups](DeviceGroups.md)
- [Ports](Ports.md)
- [Port_Groups](Port_Groups.md)
- [PortGroups](PortGroups.md)
- [PortSecurity](PortSecurity.md)
- [Alerts](Alerts.md)
- [Routing](Routing.md)
- [Switching](Switching.md)
- [Inventory](Inventory.md)
- [Bills](Bills.md)
- [ARP](ARP.md)
- [Services](Services.md)
- [Logs](Logs.md)
- [System](System.md)
- [Pollers](Pollers.md)
- [Locations](Locations.md)
