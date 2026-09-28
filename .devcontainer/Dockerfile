FROM mcr.microsoft.com/devcontainers/php:1-8.4-bookworm

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions gd gmp intl ldap memcached mysqli pdo_mysql sockets zip

RUN apt-get update \
    && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y --no-install-recommends \
        acl \
        fping \
        graphviz \
        imagemagick \
        mariadb-client \
        mtr-tiny \
        nmap \
        python3 \
        python3-pip \
        python3-venv \
        rrdtool \
        snmp \
        whois \
    && rm -rf /var/lib/apt/lists/*
