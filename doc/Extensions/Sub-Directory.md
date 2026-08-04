To run LibreNMS under a subdirectory on your Apache server, put the
directives for the LibreNMS directory in the base server
configuration, or in a virtual host container of your selection. If
you use a virtual host, put the directives in the file where the
virtual host is configured. If you use the base server on RHEL
distributions (CentOS, Scientific Linux, etc.), you can put the directives
in `/etc/httpd/conf.d/librenms.conf`. For Debian distributions
(Ubuntu, etc.), put the directives in
`/etc/apache2/sites-available/default`.

```apache
#These directives can be inside a virtual host or in the base server configuration
AllowEncodedSlashes On
Alias /librenms /opt/librenms/html

<Directory "/opt/librenms/html">
    AllowOverride All
    Options FollowSymLinks MultiViews
</Directory>
```

You must rewrite the `RewriteBase` directive in `html/.htaccess` to
refer to the subdirectory name. If LibreNMS runs at
<http://example.com/librenms/>, you must change `RewriteBase /`
to `RewriteBase /librenms`.

Last, set `APP_URL=/librenms/` in .env and `lnms config:set base_url '/librenms/'`.
