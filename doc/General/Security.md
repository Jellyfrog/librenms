# General

Security is important to us. But bugs do get into the software, and
the code base that we received also has a history. Our procedure for
identified vulnerabilities shows that security is important to us.

## Securing your install

As with all systems of this type, we strongly recommend that you use a
firewall or a VPN to restrict access to the installation.

Make sure that your installation is [up to date](Updating.md).

### Enable HTTPS

We also strongly recommend that you use an SSL certificate for
protection of the web interface, for example a certificate from
[LetsEncrypt](http://www.letsencrypt.org).

### Secure Session Cookies

After you enable HTTPS for your installation, set `SESSION_SECURE_COOKIE=true`
in your .env file.  Then the system sends cookies only through a secure protocol.
This prevents MiM attacks on the cookies.

### Trusted Proxies

If you use a reverse proxy, you can specify the hosts that have
permission to forward headers to LibreNMS. Because of legacy reasons,
the default configuration permits all proxies.

Set APP_TRUSTED_PROXIES in your .env to an empty string, or to the
URLs of the proxies that have permission to forward.

## Reporting vulnerabilities

We thank all persons who do the work to find flaws in software. You
are welcome to do this with LibreNMS. This makes the software better
and more secure for everyone.

If you think that you found a vulnerability, and you want to speak
about it with the core team, contact us on
[Discord](https://discord.com/invite/librenms). We try to reply as
quickly as possible, usually in 24 hours.

We are glad to give credit for the findings. But we ask for time to
patch a vulnerability before public disclosure. Then our users can
update as soon as a repair is available.
