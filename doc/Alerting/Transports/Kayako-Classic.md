## Kayako Classic

LibreNMS can send alerts to the Kayako Classic API. Kayako then
converts them to tickets. To use this module, the REST API feature
must be enabled in Kayako Classic, and an email account must be configured in LibreNMS. To
enable this, do this:

AdminCP -> REST API -> Settings -> Enable API (Yes)

You must also know the department id, to send tickets to the
applicable department, and a user email, which becomes the
ticket author.  To get the department id: go to the applicable
department name on the departments list page in Admin CP, and look at the
number at the end of the url. Example:
<http://servicedesk.example.com/admin/Base/Department/Edit/17>. The Department
ID is 17

You must also know the API Url, the API Key and the API Secret to
connect to the servicedesk

[Kayako REST API Docs](https://classic.kayako.com/article/1502-kayako-rest-api)

**Example:**

| Config | Example |
| ------ | ------- |
| Kayako URL | <http://servicedesk.example.com/api/> |
| Kayako API Key | 8cc02f38-7465-4a0c-8730-bb3af122167b |
| Kayako API Secret | Y2NhZDIxNDMtNjVkMi0wYzE0LWExYTUtZGUwMjJiZDI0ZWEzMmRhOGNiYWMtNTU2YS0yODk0LTA1MTEtN2VhN2YzYzgzZjk5 |
| Kayako Department | 1 |