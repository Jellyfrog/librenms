# Docker

An official LibreNMS Docker image is available on
[DockerHub](https://hub.docker.com/r/librenms/librenms/). The image is
based on Alpine Linux and Nginx.

# Documentation

The full installation and configuration documentation is in the [GitHub repository](https://github.com/librenms/docker).

# Quick install
1. Install Docker: https://docs.docker.com/engine/install/
2. Download the compose files and unzip them:
```
mkdir librenms
cd librenms
wget https://github.com/librenms/docker/archive/refs/heads/master.zip
unzip master.zip
cd docker-master/examples/compose
```
3. Set a new MariaDB/MySQL password in .env (`MARIADB_PASSWORD` or `MYSQL_PASSWORD`). Then examine compose.yml
4. Start the Docker containers
```
sudo docker compose -f compose.yml up -d
```
5. Open the web UI to complete the configuration. `http://localhost:8000` (use the correct IP address or name, not localhost)
