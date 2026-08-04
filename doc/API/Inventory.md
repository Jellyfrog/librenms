### `get_inventory`

Get the inventory for a device. If you call this without
parameters, you get only part of the inventory. The cause:
many devices nest each component. For example, first there is
the chassis, in it the ports - 1 is an sfp
cage, and then the sfp itself. The design of this API call permits
a recursive lookup. The first call gets the root
entry. This response includes entPhysicalIndex. You can
then call for entPhysicalContainedIn, which returns the next
layer of results.  To get all items together, see
[get_inventory_for_device](#get_inventory_for_device).

Route: `/api/v0/inventory/:hostname`

- hostname can be the device hostname or the device id

Input:

- entPhysicalClass: This limits the class of the
  inventory. For example, you can specify chassis to return only items
  in the inventory that have the label chassis.
- entPhysicalContainedIn: This gets items in the
  inventory attached to a component before it. For example, when you specify
  the chassis (entPhysicalIndex), you get all items where the
  chassis is the parent.

Example:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' https://foo.example/api/v0/inventory/localhost?entPhysicalContainedIn=65536
```

Output:

```json
{
    "status": "ok",
    "message": "",
    "count": 1,
    "inventory": [
        {
            "entPhysical_id": "2",
            "device_id": "32",
            "entPhysicalIndex": "262145",
            "entPhysicalDescr": "Linux 3.3.5 ehci_hcd RB400 EHCI",
            "entPhysicalClass": "unknown",
            "entPhysicalName": "1:1",
            "entPhysicalHardwareRev": "",
            "entPhysicalFirmwareRev": "",
            "entPhysicalSoftwareRev": "",
            "entPhysicalAlias": "",
            "entPhysicalAssetID": "",
            "entPhysicalIsFRU": "false",
            "entPhysicalModelName": "0x0002",
            "entPhysicalVendorType": "zeroDotZero",
            "entPhysicalSerialNum": "rb400_usb",
            "entPhysicalContainedIn": "65536",
            "entPhysicalParentRelPos": "-1",
            "entPhysicalMfgName": "0x1d6b",
            "ifIndex": "0"
        }
    ]
}
```

### `get_inventory_for_device`

Get the flattened inventory for a device.  This gets all
inventory items for a device, independently of their structure. It can be
more useful for devices with nested components.

Route: `/api/v0/inventory/:hostname/all`

- hostname can be the device hostname or the device id

Example:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' https://foo.example/api/v0/inventory/localhost/all?entPhysicalContainedIn=65536
```

Output:

```json
{
    "status": "ok",
    "message": "",
    "count": 1,
    "inventory": [
        {
            "entPhysical_id": "2",
            "device_id": "32",
            "entPhysicalIndex": "262145",
            "entPhysicalDescr": "Linux 3.3.5 ehci_hcd RB400 EHCI",
            "entPhysicalClass": "unknown",
            "entPhysicalName": "1:1",
            "entPhysicalHardwareRev": "",
            "entPhysicalFirmwareRev": "",
            "entPhysicalSoftwareRev": "",
            "entPhysicalAlias": "",
            "entPhysicalAssetID": "",
            "entPhysicalIsFRU": "false",
            "entPhysicalModelName": "0x0002",
            "entPhysicalVendorType": "zeroDotZero",
            "entPhysicalSerialNum": "rb400_usb",
            "entPhysicalContainedIn": "65536",
            "entPhysicalParentRelPos": "-1",
            "entPhysicalMfgName": "0x1d6b",
            "ifIndex": "0"
        }
    ]
}
```
