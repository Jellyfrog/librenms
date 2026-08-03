## Philips Hue

Do you want a more interesting noc life? LibreNMS can flash all lights
connected to your philips hue bridge each time an alert starts.

To set this up, go to <http://`your-bridge-ip`/debug/clip.html>

- Update the "URL:" field to `/api`
- Paste this in the "Message Body" {"devicetype":"librenms"}
- Push the round button on your `philips Hue Bridge`
- Click on `POST`
- In the `Command Response`, you see output with your
  username. Copy this without the quotes

More Info: [Philips Hue Documentation](https://www.developers.meethue.com/documentation/getting-started)

**Example:**

| Config | Example |
| ------ | ------- |
| Host | http://your-bridge-ip |
| Hue User | username |
| Duration | 1 Second |
