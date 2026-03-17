#!/usr/bin/env bash
# Pre-build script for Zensical documentation.
# Handles dynamic content that Zensical doesn't natively support:
# - Environment variable substitution in markdown files
# - Vendor list generation from OS detection definitions
# - Redirect page generation from redirect map

set -euo pipefail
cd "$(dirname "$0")/.."

# Default values (can be overridden via environment)
: "${PHP_VERSION_MIN:=8.2}"
: "${SITE_URL:=https://docs.librenms.org}"
export PHP_VERSION_MIN SITE_URL

# --- Variable substitution ---
# Replace ${VAR} placeholders in markdown files that use them
for file in doc/Installation/Install-LibreNMS.md doc/Developing/Code-Structure.md; do
  if [ -f "$file" ]; then
    envsubst '${PHP_VERSION_MIN} ${SITE_URL}' < "$file" > "$file.tmp"
    mv "$file.tmp" "$file"
  fi
done

# --- Generate vendor list ---
grep -h "^text: " resources/definitions/os_detection/*.yaml \
| sed -E "s/^text: *[\"']?([^\"']+).*/\1/" \
| sort -f -u \
| awk '{
  if (last != tolower(substr($0, 0, 1))) {
    print "\n### "toupper(substr($0,0,1))"\n* "$0; last = tolower(substr($1, 0, 1))
  } else {
    print "* "$0
  }
}' > doc/Support/vendors.md

# --- Generate redirect pages ---
# These were previously handled by mkdocs-redirects plugin.
# Each redirect is an HTML file with a meta refresh tag.
declare -A REDIRECTS=(
  ["Alerting/Introduction.md"]="Alerting/index.md"
  ["API/API-Docs.md"]="API/index.md"
  ["Extensions/Alerting.md"]="Alerting/index.md"
  ["Extensions/Email-Alerting.md"]="Alerting/Transports.md"
  ["Extensions/Graphite.md"]="Extensions/metrics/Graphite.md"
  ["Extensions/InfluxDB.md"]="Extensions/metrics/InfluxDB.md"
  ["Extensions/InfluxDBv2.md"]="Extensions/metrics/InfluxDBv2.md"
  ["Extensions/Kafka.md"]="Extensions/metrics/Kafka.md"
  ["Extensions/OpenTSDB.md"]="Extensions/metrics/OpenTSDB.md"
  ["Extensions/Poller-Service.md"]="Extensions/Dispatcher-Service.md"
  ["Extensions/Port-Description-Parser.md"]="Extensions/Interface-Description-Parsing.md"
  ["Extensions/Prometheus.md"]="Extensions/metrics/Prometheus.md"
  ["General/Contributing.md"]="Developing/Getting-Started.md"
  ["Installation/CentOS-image.md"]="Installation/Install-LibreNMS.md"
  ["Installation/index.md"]="Installation/Install-LibreNMS.md"
  ["Installation/Installation-CentOS-6-Apache-Nginx.md"]="Installation/Install-LibreNMS.md"
  ["Installation/Installation-CentOS-7-Apache.md"]="Installation/Install-LibreNMS.md"
  ["Installation/Installation-CentOS-7-Nginx.md"]="Installation/Install-LibreNMS.md"
  ["Installation/Installation-Debian-11-Nginx.md"]="Installation/Install-LibreNMS.md"
  ["Installation/Installation-Ubuntu-1604-Apache.md"]="Installation/Install-LibreNMS.md"
  ["Installation/Installation-Ubuntu-1604-Nginx.md"]="Installation/Install-LibreNMS.md"
  ["Installation/Installation-Ubuntu-1804-Apache.md"]="Installation/Install-LibreNMS.md"
  ["Installation/Installation-Ubuntu-1804-Nginx.md"]="Installation/Install-LibreNMS.md"
  ["Installation/Installing-LibreNMS.md"]="Installation/Install-LibreNMS.md"
  ["Installation/Ubuntu-image.md"]="Installation/Images.md"
  ["Support/Support-New-OS.md"]="Developing/Support-New-OS.md"
)

for src in "${!REDIRECTS[@]}"; do
  dest="${REDIRECTS[$src]}"
  # Convert .md to directory-style URL path
  src_dir="out/${src%.md}/"
  dest_url="${SITE_URL}/${dest%.md}/"
  mkdir -p "$src_dir"
  cat > "${src_dir}index.html" <<REDIRECT_EOF
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Redirecting</title>
  <link rel="canonical" href="${dest_url}">
  <meta http-equiv="refresh" content="0; url=${dest_url}">
</head>
<body>
  <p>This page has moved. If you are not redirected, <a href="${dest_url}">click here</a>.</p>
</body>
</html>
REDIRECT_EOF
done

echo "Pre-build complete."
