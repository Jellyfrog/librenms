## GLPI

The GLPI transport creates a ticket in GLPI each time an alert starts.

 - For each alert type on a device, the transport creates a ticket.
 - If multiple alerts of the same type start, the transport adds follow-ups to the current ticket.
 - If the current ticket is closed, the transport creates a new ticket.

The user that the user token identifies becomes the creator and the requester of the ticket. If a device with the same name exists in GLPI, the transport attaches it to the ticket.

To set it up:
 - **User token**: Go to User preferences > API in GLPI.
 - **App token**: Go to Configuration > General > API in GLPI.

**Example:**

| Config | Example |
| ------ | ------- |
| GLPI API URL | <http://localhost/glpi/apirest.php> |
| User Token | A1b2C3d4E5f6G7h8I9j0K1l2M3n4O5p6Q7r8S9t0 |
| App Token | Z9y8X7w6V5u4T3s2R1q0P9o8N7m6L5k4J3i2H1g |
