## Pushover

To change the default [notification
sound](https://pushover.net/api#sounds) for all notifications, you
can add this in Pushover Options:

`sound=falling`

You can also change the sound for each severity. The system uses sound_ok for recovery notifications:
`sound_critical=falling`
`sound_warning=siren`
`sound_ok=magic`

It is easy to enable Pushover support. There are only two mandatory parameters.

First, create a new Application (with the name LibreNMS, for
example) in your account on the Pushover website ([https://pushover.net/apps](https://pushover.net/apps)).

Then get your API Key from the new Application,
and your User Key or Group Key.
Then set up the transport.

[Pushover Docs](https://pushover.net/api)

**Example:**

| Config | Example |
| ------ | ------- |
| Api Key | APPLICATIONAPIKEYGOESHERE |
| User/Group Key | USERORGROUPKEYGOESHERE |
| Pushover Options | sound_critical=falling <br/> sound_warning=siren <br/> sound_ok=magic |
