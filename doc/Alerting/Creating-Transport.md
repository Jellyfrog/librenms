# Creating a new Transport

## File location

All transports are in `LibreNMS\Alert\Transport`. The files
have the name of the Transport. I.e `Discord.php` for Discord.

## Transport structure

A new transport must have these functions to pass the unit tests:

`deliverAlert()` - Alerts call this function to start the
transport. Here, do the necessary processing of the transport
config to make it ready for use.

`contact$Transport()` - This has the name of the transport. For
Discord, it is `contactDiscord()`. This function
speaks with the 3rd party API, starts the mail command, or does what
you want your alert to do.

`configTemplate()` - This sets the form that accepts
the transport config in the webui, and also which data the system must
validate and how. Validation uses
[Laravel validation](https://laravel.com/docs/validation)

## Documentation

Do not forget to create a documentation file `doc/Alerting/Transports/$Transport.md`
with the details of your new transport.

Supply a table that shows the form values that we ask for,
with examples. I.e:

|Config | Example|
------ | -------
Discord URL | <https://discordapp.com/api/webhooks/4515489001665127664/82-sf4385ysuhfn34u2fhfsdePGLrg8K7cP9wl553Fg6OlZuuxJGaa1d54fe>|
Options | username=myname|

Also make sure that you give links to the applicable 3rd party
documentation. This helps users understand how to use the transport.
