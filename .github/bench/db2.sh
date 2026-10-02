#!/usr/bin/env bash
# Try MySQL settings one at a time on tmpfs and compare with MariaDB.
# Times a direct client load of the schema dump (database only) and artisan migrate:fresh.
set -euo pipefail
now() { date +%s.%N; }
since() { echo "$(now) - $1" | bc; }
SCHEMA=database/schema/mysql-schema.sql
BASE='skip-log-bin
innodb_flush_log_at_trx_commit=0
innodb_doublewrite=OFF'

declare -A EXTRA=(
  [base]=''
  [no-perf-schema]='performance_schema=OFF'
  [no-persistent-stats]='innodb_stats_persistent=OFF'
  [no-file-per-table]='innodb_file_per_table=OFF'
  [flush-fsync]='innodb_flush_method=fsync'
  [no-log-writer-threads]='innodb_log_writer_threads=OFF'
  [big-redo]='innodb_redo_log_capacity=1G'
  [no-adaptive-flush]='innodb_adaptive_flushing=OFF
innodb_flush_neighbors=0'
  [all]='performance_schema=OFF
innodb_stats_persistent=OFF
innodb_file_per_table=OFF
innodb_flush_method=fsync
innodb_log_writer_threads=OFF
innodb_redo_log_capacity=1G'
)

sudo cp -a /var/lib/mysql /var/lib/mysql.disk
docker pull -q mariadb:lts > /dev/null

load() { # $1 = client command
  local t=()
  for i in 1 2 3; do
    $1 -e 'DROP DATABASE IF EXISTS bench; CREATE DATABASE bench CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
    local s=$(now); $1 bench < $SCHEMA; t+=("$(since "$s")")
  done
  printf '%.2f/' "${t[@]}"
}
artisan() {
  local t=()
  for i in 1 2; do local s=$(now); php artisan migrate:fresh --force > /dev/null; t+=("$(since "$s")"); done
  printf '%.2f/' "${t[@]}"
}
grant='CREATE DATABASE IF NOT EXISTS librenms_phpunit_78hunjuybybh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS "librenms"@"%" IDENTIFIED BY "librenms"; GRANT ALL PRIVILEGES ON librenms_phpunit_78hunjuybybh.* TO "librenms"@"%";'

for name in base no-perf-schema no-persistent-stats no-file-per-table flush-fsync no-log-writer-threads big-redo no-adaptive-flush all; do
  printf '[mysqld]\n%s\n%s\n' "$BASE" "${EXTRA[$name]}" | sudo tee /etc/mysql/mysql.conf.d/zz-ci.cnf > /dev/null
  sudo mount -t tmpfs -o size=4g tmpfs /var/lib/mysql
  sudo cp -a /var/lib/mysql.disk/. /var/lib/mysql/
  sudo chown mysql:mysql /var/lib/mysql
  sudo systemctl start mysql || { sudo journalctl -u mysql -n 20 --no-pager; exit 1; }
  my="mysql --user=root --password=root"
  $my -e "$grant" 2> /dev/null
  line=$(printf 'RESULT mysql %-22s client=%s artisan=%s' "$name" "$(load "$my" 2> /dev/null)" "$(artisan)")
  echo "$line"; echo "$line" >> "$GITHUB_STEP_SUMMARY"
  sudo systemctl stop mysql
  sudo umount /var/lib/mysql
done

docker run -d --name database -p 3306:3306 -e MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=1 --tmpfs /var/lib/mysql:rw mariadb:lts > /dev/null
until docker exec database healthcheck.sh --connect --innodb_initialized 2> /dev/null; do sleep 0.2; done
ma="docker exec -i database mariadb --user=root"
$ma -e "$grant"
line=$(printf 'RESULT mariadb %-20s client=%s artisan=%s' "tmpfs" "$(load "$ma")" "$(artisan)")
echo "$line"; echo "$line" >> "$GITHUB_STEP_SUMMARY"
docker exec database mariadb --user=root -e "SELECT @@performance_schema, @@innodb_flush_method, @@innodb_stats_persistent, @@innodb_file_per_table, @@log_bin" | tee -a "$GITHUB_STEP_SUMMARY"
