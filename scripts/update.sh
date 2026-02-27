#!/usr/bin/env bash
################################################################################
# LibreNMS Update Script
#
# Standalone updater with pre-flight checks, composer/pip pre-caching,
# maintenance mode, and automatic rollback.
#
# Copyright (C) 2025 LibreNMS Contributors
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.
################################################################################

set -o pipefail

#######################################
# CONSTANTS
#######################################
SCRIPT_PATH=$(readlink -f "$0")
LIBRENMS_DIR=$(dirname "$(dirname "$SCRIPT_PATH")")
COMPOSER="php ${LIBRENMS_DIR}/scripts/composer_wrapper.php --no-interaction"

# Exit codes
readonly EXIT_SUCCESS=0
readonly EXIT_DISABLED=1
readonly EXIT_LOCKED=2
readonly EXIT_PREFLIGHT=3
readonly EXIT_UPDATE_FAIL=4
readonly EXIT_ROLLBACK_FAIL=5


#######################################
# CLI FLAGS (defaults)
#######################################
FLAG_VERBOSE=false
FLAG_QUIET=false
FLAG_DRY_RUN=false
FLAG_FORCE=false
FLAG_PRE_FLIGHT_ONLY=false
FLAG_STATUS=false
FLAG_ROLLBACK_MIGRATIONS=false
FLAG_ROLLBACK_ONLY=false

#######################################
# STATE
#######################################
UPDATE_ENABLED=true
UPDATE_CHANNEL=nightly
LIBRENMS_USER=""

#######################################
# Show usage information
#######################################
show_help() {
    cat <<'HELP'
Usage: scripts/update.sh [OPTIONS]

Standalone LibreNMS updater with pre-flight checks, pre-caching,
maintenance mode, and automatic rollback.

Options:
  --help                  Show this help message and exit
  --verbose               Enable verbose output
  --quiet                 Suppress non-error output
  --dry-run               Show what would happen without making changes
  --force                 Override safety checks (Docker, UPDATE_ENABLED=false)
  --pre-flight-only       Run pre-flight checks and exit
  --status                Show current update status and exit
  --rollback-migrations   Enable DB migration rollback on failure
  --rollback-only         Manually trigger a rollback to saved state

Exit codes:
  0  Success
  1  Updates disabled or general failure
  2  Lock file conflict
  3  Pre-flight check failure
  4  Update failure
  5  Rollback failure

Configuration (.env):
  UPDATE_ENABLED=true      Enable/disable updates
  UPDATE_CHANNEL=nightly   Update channel: stable or nightly
HELP
}

#######################################
# Parse command-line arguments
#######################################
parse_args() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --help)
                show_help
                exit "$EXIT_SUCCESS"
                ;;
            --verbose)          FLAG_VERBOSE=true ;;
            --quiet)            FLAG_QUIET=true ;;
            --dry-run)          FLAG_DRY_RUN=true ;;
            --force)            FLAG_FORCE=true ;;
            --pre-flight-only)  FLAG_PRE_FLIGHT_ONLY=true ;;
            --status)           FLAG_STATUS=true ;;
            --rollback-migrations) FLAG_ROLLBACK_MIGRATIONS=true ;;
            --rollback-only)    FLAG_ROLLBACK_ONLY=true ;;
            *)
                echo "Unknown option: $1" >&2
                echo "Run with --help for usage information." >&2
                exit "$EXIT_DISABLED"
                ;;
        esac
        shift
    done
}

#######################################
# Load .env configuration
#######################################
load_env() {
    local env_file="${LIBRENMS_DIR}/.env"
    if [[ ! -f "$env_file" ]]; then
        echo "ERROR: .env file not found at ${env_file}" >&2
        exit "$EXIT_DISABLED"
    fi

    # shellcheck source=.env.example
    source "$env_file"
    LIBRENMS_USER="${LIBRENMS_USER:-librenms}"
}

#######################################
# Verify running as correct user
#######################################
check_user() {
    if [[ "$LIBRENMS_USER" == "root" ]]; then
        return 0
    fi

    local expected_uid
    expected_uid=$(id -u "$LIBRENMS_USER" 2>/dev/null)
    if [[ -z "$expected_uid" ]]; then
        echo "ERROR: User '${LIBRENMS_USER}' does not exist" >&2
        exit "$EXIT_DISABLED"
    fi

    if [[ "$EUID" -ne "$expected_uid" ]]; then
        echo "ERROR: This script must be run as ${LIBRENMS_USER} (uid ${expected_uid}), not uid ${EUID}" >&2
        exit "$EXIT_DISABLED"
    fi
}

########################################################################
# MAIN
########################################################################

main() {
    parse_args "$@"

    cd "$LIBRENMS_DIR" || exit "$EXIT_DISABLED"

    load_env
    check_user

    exit "$EXIT_SUCCESS"
}

main "$@"
