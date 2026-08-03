## Matrix

To use the Matrix transport, you must create a room on the Matrix-server.
The given Auth_token belongs to a user who is a member of this room.
You can build the Message sent to the matrix-room from the variables in
[Template-Syntax](../Templates.md#syntax), but without the 'alert->' prefix.
See API-Transport. The variable ``` $msg ``` contains the result of
the Alert template. The system cuts the Matrix-Server URL before the
start of the ``_matrix/client/r0/...`` API-part.

**Example:**

| Config | Example |
| ------ | ------- |
| Matrix-Server URL | <https://matrix.example.com/> |
| Room | !ajPbbPalmVbNuQoBDK:example.com |
| Auth_token: | MDAyYmxvY2F0aW9uI...z1DCn6lz_uOhtW3XRICg |
| Message: | Alert: {{ $msg }} https://librenms.example.com |