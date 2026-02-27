#!/usr/bin/env bash
################################################################################
# Copyright (C) 2015 Daniel Preussker, QuxLabs UG <preussker@quxlabs.com>
# Copyright (C) 2016 Layne "Gorian" Breitkreutz <Layne.Breitkreutz@thelenon.com>
# Copyright (C) 2017 Tony Murray <murraytony@gmail.com>
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
################################################################################

#######################################
# CONSTANTS
#######################################
# define DAILY_SCRIPT as the full path to this script and LIBRENMS_DIR as the directory this script is in
DAILY_SCRIPT=$(readlink -f "$0")
LIBRENMS_DIR=$(dirname "$DAILY_SCRIPT")
COMPOSER="php ${LIBRENMS_DIR}/scripts/composer_wrapper.php --no-interaction"

# set log_file, using librenms 'log_dir' config setting, if set
# otherwise we default to <LibreNMS Install Directory>/logs
LOG_DIR=$(php -r "@include '${LIBRENMS_DIR}/config.php'; echo isset(\$config['log_dir']) ? \$config['log_dir'] : '${LIBRENMS_DIR}/logs';")

# get the librenms user
# shellcheck source=.env.example
source "${LIBRENMS_DIR}/.env"
LIBRENMS_USER="${LIBRENMS_USER:-librenms}"
LIBRENMS_USER_ID=$(id -u "$LIBRENMS_USER")

#######################################
# Fancy-Print and run commands
# Globals:
#   LOG_DIR
# Arguments:
#   Text
#   Command
# Returns:
#   Exit-Code of Command
#######################################
status_run() {
    # Explicitly define our arguments
    local args arg_text arg_command log_file exit_code tmp log_file

    args=("$@")
    arg_text=$1
    arg_command=$2
    log_file=${LOG_DIR}/daily.log

    # set log_file, using librenms $config['log_dir'], if set
    # otherwise we default to ./logs/daily.log

    printf "%-50s" "${arg_text}"
    echo "${arg_text}" >> "${log_file}"
    tmp=$(bash -c "${arg_command}" 2>&1)
    exit_code=$?
    echo "${tmp}" >> "${log_file}"
    echo "Returned: ${exit_code}" >> "${log_file}"

    # print OK if the command ran successfully
    # or FAIL otherwise (non-zero exit code)
    if [[ "${exit_code}" == "0" ]]; then
        printf " \\033[0;32mOK\\033[0m\\n"
    else
        printf " \\033[0;31mFAIL\\033[0m\\n"
        if [[ -n "${tmp}" ]]; then
            # print output in case of failure
            echo "${tmp}"
        fi
    fi
    return ${exit_code}
}

#######################################
# Call daily.php
# Globals:
#   LIBRENMS_DIR
# Arguments:
#   args:
#        Array of arguments to pass to
#        daily.php
# Returns:
#   Exit-Code of Command
#######################################
call_daily_php() {
    local args

    args=("$@")

    for arg in "${args[@]}"; do
        php "${LIBRENMS_DIR}/daily.php" -f "${arg}"
    done
}


#######################################
# Entry into program
# Globals:
#   LIBRENMS_DIR
# Arguments:
#
# Returns:
#   Exit-Code of Command
#######################################
main () {
    local arg options

    arg="$1"

    cd "${LIBRENMS_DIR}" || exit 1

    # if not running as $LIBRENMS_USER (unless $LIBRENMS_USER = root), relaunch
    if [[ "$LIBRENMS_USER" != "root" ]]; then
        if [[ "$EUID" -ne "$LIBRENMS_USER_ID" ]]; then
            printf "\\033[0;91mERROR\\033[0m: You must run this script as %s\\n" "${LIBRENMS_USER}"
            exit 1
        fi
    fi

    # make sure autoload.php exists before trying to run any php that may require it
    if [ ! -f "${LIBRENMS_DIR}/vendor/autoload.php" ]; then
        ${COMPOSER} install --no-dev
    fi

    if [[ -z "$arg" ]]; then
        # Default: run cleanup and peeringdb caching
        # Code updates and migrations are handled by scripts/update.sh
        status_run 'Cleaning up DB' "'$DAILY_SCRIPT' cleanup"
        status_run 'Caching PeeringDB data' "$DAILY_SCRIPT peeringdb"
    else
        case $arg in
            cleanup)
                # Cleanups
                options=("refresh_alert_rules"
                               "refresh_device_groups"
                               "recalculate_device_dependencies"
                               "eventlog"
                               "authlog"
                               "callback"
                               "purgeusers"
                               "bill_data"
                               "alert_log"
                               "rrd_purge"
                               "ports_fdb"
                               "ports_nac"
                               "route"
                               "ports_purge")
                call_daily_php "${options[@]}"
            ;;
            peeringdb)
                options=("peeringdb")
                call_daily_php "${options[@]}"
        esac
    fi
}

main "$@"
