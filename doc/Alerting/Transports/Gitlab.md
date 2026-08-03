## GitLab

LibreNMS creates issues for warning and critical level alerts.
But it sets only the title and the description. It uses Personal access
tokens for authentication with GitLab, and it keeps the token in cleartext.

**Example:**

| Config | Example |
| ------ | ------- |
| Host | <http://gitlab.host.tld> |
| Project ID | 1 |
| Personal Access Token | AbCdEf12345 |