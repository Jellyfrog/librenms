#!/usr/bin/env bash
# Run every database variant on this runner, one after the other, and time
# readiness and migrations. usage: db.sh
set -euo pipefail

now() { date +%s.%N; }
since() { echo "$(now) - $1" | bc; }
sql() {
  if [ "$DB" = mysql ]; then mysql --user=root --password=root 2> /dev/null; else docker exec -i database mariadb --user=root; fi
}

start_db() {
  local v=$1
  if [[ $v == mysql* ]]; then
    DB=mysql
    sudo rm -f /etc/mysql/mysql.conf.d/zz-ci.cnf
    if [[ $v == *tuned ]]; then
      printf '[mysqld]\nskip-log-bin\ninnodb_flush_log_at_trx_commit=0\ninnodb_doublewrite=OFF\n' | sudo tee /etc/mysql/mysql.conf.d/zz-ci.cnf > /dev/null
    fi
    if [[ $v == *tmpfs* ]]; then
      sudo mount -t tmpfs -o size=4g tmpfs /var/lib/mysql
      sudo cp -a /var/lib/mysql.disk/. /var/lib/mysql/
      sudo chown mysql:mysql /var/lib/mysql
    fi
    sudo systemctl start mysql
  else
    DB=mariadb
    local args=() cmd=()
    [[ $v == *tmpfs* ]] && args+=(--tmpfs /var/lib/mysql:rw)
    [[ $v == *tuned ]] && cmd+=(--innodb-flush-log-at-trx-commit=0 --innodb-doublewrite=0)
    docker run -d --name database -p 3306:3306 -e MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=1 "${args[@]}" mariadb:lts "${cmd[@]}" > /dev/null
    until docker exec database healthcheck.sh --connect --innodb_initialized 2> /dev/null; do sleep 0.2; done
  fi
  sql <<'SQL'
CREATE DATABASE IF NOT EXISTS librenms_phpunit_78hunjuybybh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'librenms'@'%' IDENTIFIED BY 'librenms';
GRANT ALL PRIVILEGES ON librenms_phpunit_78hunjuybybh.* TO 'librenms'@'%';
SQL
}

stop_db() {
  local v=$1
  if [[ $v == mysql* ]]; then
    sudo systemctl stop mysql
    if [[ $v == *tmpfs* ]]; then sudo umount /var/lib/mysql; fi
  else
    docker rm -f database > /dev/null
  fi
}

# keep a pristine copy of the runner's MySQL datadir for the tmpfs variants
sudo cp -a /var/lib/mysql /var/lib/mysql.disk
docker pull -q mariadb:lts > /dev/null

for v in mysql mysql-tuned mysql-tmpfs mysql-tmpfs-tuned mariadb mariadb-tuned mariadb-tmpfs mariadb-tmpfs-tuned; do
  s=$(now); start_db "$v"; ready=$(since "$s")
  dump=()
  for i in 1 2 3; do s=$(now); php artisan migrate:fresh --force > /dev/null; dump+=("$(since "$s")"); done
  mv database/schema/mysql-schema.sql /tmp/
  full=()
  for i in 1 2; do s=$(now); php artisan migrate:fresh --force > /dev/null; full+=("$(since "$s")"); done
  mv /tmp/mysql-schema.sql database/schema/
  stop_db "$v"
  line=$(printf 'RESULT %-20s ready=%5.1fs dump=%s full=%s' "$v" "$ready" \
    "$(printf '%.1f/' "${dump[@]}")" "$(printf '%.1f/' "${full[@]}")")
  echo "$line"
  echo "$line" >> "$GITHUB_STEP_SUMMARY"
done
