## Elasticsearch

LibreNMS can send alerts to an elasticsearch database. The system
sends each fault as a separate document.

**Example:**

| Config | Example |
| ------ | ------- |
| Host | 127.0.0.1 |
| Port | 9200 |
| Index Pattern | \l\i\b\r\e\n\m\s-Y.m.d |