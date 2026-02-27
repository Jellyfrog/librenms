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

LOCK_FILE="${LIBRENMS_DIR}/storage/app/private/update.lock"
LOG_FILE="${LIBRENMS_DIR}/logs/update.log"


# Color codes (set in init_colors)
COLOR_RED=""
COLOR_GREEN=""
COLOR_YELLOW=""
COLOR_RESET=""

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
LOCK_ACQUIRED=false


#######################################
# Initialize ANSI color codes
# Colors are enabled when stdout is a terminal and --quiet is not set
#######################################
init_colors() {
    if [[ -t 1 ]] && [[ "$FLAG_QUIET" != "true" ]]; then
        COLOR_RED=$'\033[0;31m'
        COLOR_GREEN=$'\033[0;32m'
        COLOR_YELLOW=$'\033[0;33m'
        COLOR_RESET=$'\033[0m'
    fi
}

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
# LOGGING
########################################################################

#######################################
# Log a message to file and optionally to stdout
# Arguments:
#   $1 - level (INFO, WARN, ERROR)
#   $2 - message
#######################################
log_msg() {
    local level="$1"
    local message="$2"
    local timestamp
    timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
    local log_line="[${timestamp}] [${level}] ${message}"

    # Always write to log file
    echo "$log_line" >> "$LOG_FILE" 2>/dev/null

    # Console output based on flags and level
    if [[ "$FLAG_QUIET" == "true" && "$level" != "ERROR" ]]; then
        return
    fi

    local color=""
    case "$level" in
        INFO)
            if [[ "$FLAG_QUIET" == "true" ]]; then
                return
            fi
            color="$COLOR_GREEN"
            ;;
        WARN)  color="$COLOR_YELLOW" ;;
        ERROR) color="$COLOR_RED" ;;
    esac

    if [[ "$FLAG_VERBOSE" == "true" ]]; then
        echo "${color}[${level}]${COLOR_RESET} ${message}"
    elif [[ "$level" != "INFO" ]]; then
        echo "${color}[${level}]${COLOR_RESET} ${message}"
    fi
}

log_info()  { log_msg "INFO" "$1"; }
log_warn()  { log_msg "WARN" "$1"; }
log_error() { log_msg "ERROR" "$1"; }
log_verbose() {
    if [[ "$FLAG_VERBOSE" == "true" ]]; then
        log_msg "INFO" "$1"
    else
        # Still write to log file even when not verbose
        local timestamp
        timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
        echo "[${timestamp}] [INFO] $1" >> "$LOG_FILE" 2>/dev/null
    fi
}

########################################################################
# LOCK MECHANISM
########################################################################

#######################################
# Acquire an exclusive lock file
# Creates storage/app/private/ if needed
#######################################
acquire_lock() {
    local lock_dir
    lock_dir=$(dirname "$LOCK_FILE")

    if [[ ! -d "$lock_dir" ]]; then
        mkdir -p "$lock_dir" 2>/dev/null || {
            log_error "Cannot create lock directory: ${lock_dir}"
            exit "$EXIT_LOCKED"
        }
    fi

    # Check for stale lock
    if [[ -f "$LOCK_FILE" ]]; then
        local old_pid
        old_pid=$(cat "$LOCK_FILE" 2>/dev/null)
        if [[ -n "$old_pid" ]] && kill -0 "$old_pid" 2>/dev/null; then
            log_error "Another update is running (PID ${old_pid}). Lock file: ${LOCK_FILE}"
            exit "$EXIT_LOCKED"
        fi
        log_warn "Removing stale lock file (PID ${old_pid} is not running)"
        rm -f "$LOCK_FILE"
    fi

    # Write our PID atomically
    echo $$ > "$LOCK_FILE" 2>/dev/null || {
        log_error "Cannot create lock file: ${LOCK_FILE}"
        exit "$EXIT_LOCKED"
    }
    LOCK_ACQUIRED=true
    log_verbose "Lock acquired (PID $$)"
}

#######################################
# Release the lock file
#######################################
# shellcheck disable=SC2329
release_lock() {
    if [[ "$LOCK_ACQUIRED" == "true" && -f "$LOCK_FILE" ]]; then
        rm -f "$LOCK_FILE"
        LOCK_ACQUIRED=false
        log_verbose "Lock released"
    fi
}

#######################################
# Signal handler — release lock and exit
#######################################
# shellcheck disable=SC2329
handle_signal() {
    log_warn "Caught signal, releasing lock and exiting"
    release_lock
    exit "$EXIT_DISABLED"
}

########################################################################
# MAIN
########################################################################

main() {
    parse_args "$@"

    cd "$LIBRENMS_DIR" || exit "$EXIT_DISABLED"

    load_env
    init_colors
    check_user

    log_info "LibreNMS update script started"

    # Register signal handlers
    trap handle_signal INT TERM
    trap release_lock EXIT

    # Acquire lock
    acquire_lock

    log_info "Update completed successfully"
    exit "$EXIT_SUCCESS"
}

main "$@"
