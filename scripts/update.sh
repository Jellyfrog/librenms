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
TARGET_REF=""
SAVED_HEAD=""
SAVED_MIGRATION=""
REQUIREMENTS_CHANGED=false


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
# CONFIG RESOLUTION
########################################################################

#######################################
# Resolve update configuration from .env and CLI overrides
#######################################
resolve_update_config() {
    # Read from .env (already sourced)
    UPDATE_ENABLED="${UPDATE_ENABLED:-true}"
    UPDATE_CHANNEL="${UPDATE_CHANNEL:-nightly}"

    # Normalize channel names
    case "$UPDATE_CHANNEL" in
        master|nightly)   UPDATE_CHANNEL=nightly ;;
        release|stable)   UPDATE_CHANNEL=stable ;;
    esac

    # Check if updates are enabled
    if [[ "$UPDATE_ENABLED" == "false" ]]; then
        if [[ "$FLAG_FORCE" == "true" ]]; then
            log_warn "Updates are disabled in .env but --force was specified, continuing"
        else
            log_info "Updates are disabled (UPDATE_ENABLED=false in .env). Use --force to override."
            exit "$EXIT_DISABLED"
        fi
    fi

    log_verbose "Update config: channel=${UPDATE_CHANNEL}, enabled=${UPDATE_ENABLED}"
}
#######################################
# Show current update status
#######################################
show_status() {
    local current_head current_tag last_update_time

    current_head=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
    current_tag=$(git describe --exact-match --tags HEAD 2>/dev/null || echo "none")

    if [[ -f "$LOG_FILE" ]]; then
        last_update_time=$(grep "Update completed successfully" "$LOG_FILE" 2>/dev/null | tail -1 | grep -oP '^\[\K[^]]+' || echo "never")
    else
        last_update_time="never"
    fi

    echo "LibreNMS Update Status"
    echo "======================"
    echo "  Channel:     ${UPDATE_CHANNEL}"
    echo "  Enabled:     ${UPDATE_ENABLED}"
    echo "  Current HEAD: ${current_head}"
    echo "  Current tag:  ${current_tag}"
    echo "  Last update:  ${last_update_time}"
    echo "  Branch:       $(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo 'unknown')"
}

########################################################################
# PRE-FLIGHT CHECKS
########################################################################

#######################################
# Check available disk space (require 1GB free)
#######################################
preflight_disk_space() {
    local free_mb
    free_mb=$(df -m "$LIBRENMS_DIR" | awk 'NR==2 {print $4}')
    if [[ -z "$free_mb" ]]; then
        log_warn "Could not determine free disk space"
        return 1
    fi
    if (( free_mb < 1024 )); then
        log_error "Insufficient disk space: ${free_mb}MB free, 1024MB required"
        return 1
    fi
    log_verbose "Disk space check passed: ${free_mb}MB free"
    return 0
}

#######################################
# Check git state
#######################################
preflight_git_state() {
    if [[ ! -d "${LIBRENMS_DIR}/.git" ]]; then
        log_error "Not a git repository: ${LIBRENMS_DIR}"
        return 1
    fi

    if ! command -v git &>/dev/null; then
        log_error "git command not found"
        return 1
    fi

    # Check for clean working tree (allow untracked files)
    if ! git diff --quiet 2>/dev/null; then
        log_error "Git working tree has uncommitted changes. Commit or stash them first."
        return 1
    fi
    if ! git diff --cached --quiet 2>/dev/null; then
        log_error "Git index has staged changes. Commit or reset them first."
        return 1
    fi

    log_verbose "Git state check passed"
    return 0
}

#######################################
# Check write permissions on key directories
#######################################
preflight_permissions() {
    local dirs=("logs" "storage" "vendor" "bootstrap/cache")
    local failed=false

    for dir in "${dirs[@]}"; do
        local full_path="${LIBRENMS_DIR}/${dir}"
        if [[ ! -w "$full_path" ]]; then
            log_error "Directory not writable: ${full_path}"
            failed=true
        fi
    done

    if [[ "$failed" == "true" ]]; then
        return 1
    fi
    log_verbose "Permission check passed"
    return 0
}

#######################################
# Check PHP version (>= 8.2)
#######################################
preflight_php_version() {
    local php_version
    if ! command -v php &>/dev/null; then
        log_error "php command not found"
        return 1
    fi

    php_version=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;' 2>/dev/null)
    if [[ -z "$php_version" ]]; then
        log_error "Could not determine PHP version"
        return 1
    fi

    local major minor
    IFS='.' read -r major minor <<< "$php_version"
    if (( major < 8 )) || (( major == 8 && minor < 2 )); then
        log_error "PHP ${php_version} is too old. PHP >= 8.2 is required."
        return 1
    fi

    log_verbose "PHP version check passed: ${php_version}"
    return 0
}

#######################################
# Check Python dependencies
#######################################
preflight_python_deps() {
    if [[ ! -f "${LIBRENMS_DIR}/scripts/check_requirements.py" ]]; then
        log_warn "scripts/check_requirements.py not found, skipping Python check"
        return 0
    fi

    if "${LIBRENMS_DIR}/scripts/check_requirements.py" &>/dev/null; then
        log_verbose "Python dependency check passed"
        return 0
    fi

    log_warn "Python dependencies are missing; will attempt to install after update"
    return 0
}

#######################################
# Check database connectivity
#######################################
preflight_db() {
    local db_host db_port db_database db_username db_password

    db_host="${DB_HOST:-localhost}"
    db_port="${DB_PORT:-3306}"
    db_database="${DB_DATABASE:-librenms}"
    db_username="${DB_USERNAME:-librenms}"
    db_password="${DB_PASSWORD:-}"

    if command -v mysql &>/dev/null; then
        if mysql -h "$db_host" -P "$db_port" -u "$db_username" -p"$db_password" "$db_database" -e "SELECT 1" &>/dev/null; then
            log_verbose "Database connectivity check passed"
            return 0
        fi
        log_error "Cannot connect to database at ${db_host}:${db_port}"
        return 1
    fi

    # Try php artisan as fallback
    if php "${LIBRENMS_DIR}/artisan" db:monitor --max=100 &>/dev/null; then
        log_verbose "Database connectivity check passed (via artisan)"
        return 0
    fi

    log_warn "Could not verify database connectivity (mysql client not available)"
    return 0
}

#######################################
# Check for Docker environment
#######################################
preflight_docker() {
    if [[ -f "/.dockerenv" ]] || [[ -n "${DOCKER_LIBRENMS:-}" ]]; then
        if [[ "$FLAG_FORCE" == "true" ]]; then
            log_warn "Docker environment detected, proceeding with --force"
            return 0
        fi
        log_error "Docker environment detected. Updates should be managed via container image. Use --force to override."
        return 1
    fi
    log_verbose "Docker check passed (not in Docker)"
    return 0
}

#######################################
# Run all pre-flight checks
# Returns: 0 if all pass, 1 if any fail
#######################################
preflight_checks() {
    local failed=0

    log_info "Running pre-flight checks..."

    preflight_docker || (( failed++ ))
    preflight_disk_space || (( failed++ ))
    preflight_git_state || (( failed++ ))
    preflight_permissions || (( failed++ ))
    preflight_php_version || (( failed++ ))
    preflight_python_deps || (( failed++ ))
    preflight_db || (( failed++ ))

    if (( failed > 0 )); then
        log_error "Pre-flight checks failed (${failed} issue(s))"
        return 1
    fi

    log_info "All pre-flight checks passed"
    return 0
}

########################################################################
# TARGET DETERMINATION
########################################################################

#######################################
# Determine the target ref for the update
#######################################
determine_target() {
    log_info "Determining update target (channel: ${UPDATE_CHANNEL})..."

    if [[ "$UPDATE_CHANNEL" == "nightly" ]]; then
        log_verbose "Fetching origin/master..."
        if ! git fetch origin master 2>/dev/null; then
            log_error "Failed to fetch origin/master"
            return 1
        fi
        TARGET_REF="origin/master"

        local current_head target_head
        current_head=$(git rev-parse HEAD)
        target_head=$(git rev-parse "$TARGET_REF")

        if [[ "$current_head" == "$target_head" ]]; then
            log_info "Already up to date (${current_head:0:8})"
            return 2
        fi

        log_info "Target: ${TARGET_REF} (${target_head:0:8})"
    else
        # Stable channel
        log_verbose "Fetching tags..."
        if ! git fetch --tags 2>/dev/null; then
            log_error "Failed to fetch tags"
            return 1
        fi

        local latest_hash latest_tag
        latest_hash=$(git rev-list --tags --max-count=1 2>/dev/null)
        if [[ -z "$latest_hash" ]]; then
            log_error "No tags found"
            return 1
        fi
        latest_tag=$(git describe --exact-match --tags "$latest_hash" 2>/dev/null)
        TARGET_REF="$latest_tag"

        # Check if already at latest
        local current_tag
        current_tag=$(git describe --exact-match --tags HEAD 2>/dev/null || echo "")
        if [[ "$current_tag" == "$latest_tag" ]]; then
            log_info "Already at latest release (${latest_tag})"
            return 2
        fi

        log_info "Target: ${TARGET_REF}"
    fi

    return 0
}

########################################################################
# COMPOSER PRE-CHECK
########################################################################

#######################################
# Check composer platform requirements against target ref
#######################################
preflight_composer_reqs() {
    local tmp_dir
    tmp_dir=$(mktemp -d)

    log_verbose "Checking composer platform requirements for target ref..."

    # Extract future composer files from target ref
    if ! git show "${TARGET_REF}:composer.json" > "${tmp_dir}/composer.json" 2>/dev/null; then
        log_warn "Could not extract composer.json from ${TARGET_REF}, skipping platform check"
        rm -rf "$tmp_dir"
        return 0
    fi
    git show "${TARGET_REF}:composer.lock" > "${tmp_dir}/composer.lock" 2>/dev/null || true

    # Run platform requirements check
    local output
    if output=$(eval "$COMPOSER check-platform-reqs --working-dir=${tmp_dir} --no-dev" 2>&1); then
        log_verbose "Composer platform requirements satisfied"
        rm -rf "$tmp_dir"
        return 0
    fi

    log_error "Composer platform requirements not met for target version:"
    echo "$output" >&2
    rm -rf "$tmp_dir"
    return 1
}

########################################################################
# COMPOSER PRE-CACHE
########################################################################

#######################################
# Pre-download composer packages for the target ref
#######################################
precache_composer() {
    local tmp_dir
    tmp_dir=$(mktemp -d)

    log_info "Pre-caching composer packages..."

    # Extract future composer files from target ref
    git show "${TARGET_REF}:composer.json" > "${tmp_dir}/composer.json" 2>/dev/null || {
        log_warn "Could not extract composer.json from ${TARGET_REF}"
        rm -rf "$tmp_dir"
        return 0
    }
    git show "${TARGET_REF}:composer.lock" > "${tmp_dir}/composer.lock" 2>/dev/null || true

    # Download packages to cache without installing
    if eval "$COMPOSER install --no-dev --download-only --working-dir=${tmp_dir}" &>/dev/null; then
        log_verbose "Composer packages pre-cached"
    else
        log_warn "Composer pre-cache had issues (non-fatal, install will retry)"
    fi

    rm -rf "$tmp_dir"
    return 0
}

########################################################################
# PYTHON PRE-CHECK AND PRE-DOWNLOAD
########################################################################

#######################################
# Check and pre-download Python packages for the target ref
#######################################
preflight_python_reqs() {
    local tmp_dir
    tmp_dir=$(mktemp -d)

    # Extract future requirements.txt from target ref
    if ! git show "${TARGET_REF}:requirements.txt" > "${tmp_dir}/requirements.txt" 2>/dev/null; then
        log_verbose "No requirements.txt in target ref, skipping Python pre-check"
        rm -rf "$tmp_dir"
        return 0
    fi

    # Compare with current requirements.txt
    if [[ -f "${LIBRENMS_DIR}/requirements.txt" ]]; then
        if diff -q "${LIBRENMS_DIR}/requirements.txt" "${tmp_dir}/requirements.txt" &>/dev/null; then
            log_verbose "Python requirements unchanged"
            rm -rf "$tmp_dir"
            return 0
        fi
    fi

    REQUIREMENTS_CHANGED=true
    log_info "Python requirements changed, pre-downloading packages..."

    # pip3 download acts as platform check too — fails if packages don't support current Python
    if pip3 download -r "${tmp_dir}/requirements.txt" -d "${tmp_dir}/pip-cache" &>/dev/null; then
        log_verbose "Python packages pre-downloaded"
    else
        log_warn "Some Python packages could not be pre-downloaded (will retry during install)"
    fi

    rm -rf "$tmp_dir"
    return 0
}

########################################################################
# DRY-RUN SUPPORT
########################################################################

#######################################
# Show what would happen without making changes
#######################################
show_dry_run() {
    echo ""
    echo "=== DRY RUN ==="
    echo "Channel:      ${UPDATE_CHANNEL}"
    echo "Current HEAD: $(git rev-parse --short HEAD 2>/dev/null)"
    echo "Target ref:   ${TARGET_REF}"

    if [[ "$REQUIREMENTS_CHANGED" == "true" ]]; then
        echo "Python:       requirements.txt changed (will install)"
    else
        echo "Python:       requirements.txt unchanged"
    fi

    echo ""
    echo "The following steps would be performed:"
    echo "  1. Clear caches (artisan optimize:clear)"
    echo "  2. De-optimize autoloader (composer dump-autoload)"
    if [[ "$UPDATE_CHANNEL" == "nightly" ]]; then
        echo "  3. Git pull (merge origin/master)"
    else
        echo "  3. Git checkout ${TARGET_REF}"
    fi
    echo "  4. Clear caches again"
    echo "  5. Composer install --no-dev"
    echo "  6. Optimize autoloader"
    echo "  7. Maintenance mode ON"
    echo "  8. Run database migrations"
    if [[ "$REQUIREMENTS_CHANGED" == "true" ]]; then
        echo "  9. Install Python requirements"
    fi
    echo " 10. Reset opcache"
    echo " 11. Maintenance mode OFF"
    echo " 12. Final cache clear"
    echo ""
    echo "=== END DRY RUN ==="
}

########################################################################
# PRE-UPDATE STEPS
########################################################################

#######################################
# Clear caches and de-optimize autoloader before git update
#######################################
pre_update() {
    log_info "Preparing for update..."

    # Clear old code caches
    log_verbose "Clearing caches before update..."
    php "${LIBRENMS_DIR}/artisan" optimize:clear &>/dev/null || log_warn "Cache clear had issues (non-fatal)"

    # De-optimize autoloader: removes optimized classmap, falls back to PSR-4 discovery
    # This keeps the site working during the git pull
    log_verbose "De-optimizing autoloader for safe update..."
    eval "$COMPOSER dump-autoload" &>/dev/null || log_warn "Autoloader de-optimization had issues (non-fatal)"
}

########################################################################
# CORE UPDATE — GIT OPERATIONS
########################################################################

#######################################
# Perform the git update
# Returns: 0 on success, non-zero on failure
#######################################
perform_update() {
    SAVED_HEAD=$(git rev-parse HEAD)
    log_verbose "Saved current HEAD: ${SAVED_HEAD:0:8}"

    if [[ "$UPDATE_CHANNEL" == "nightly" ]]; then
        # Handle detached HEAD case
        local current_branch
        current_branch=$(git rev-parse --abbrev-ref HEAD 2>/dev/null)
        if [[ "$current_branch" == "HEAD" ]]; then
            log_verbose "Detached HEAD detected, checking out master first..."
            if ! git checkout master 2>/dev/null; then
                log_error "Failed to checkout master from detached HEAD"
                return 1
            fi
        fi

        log_info "Pulling latest changes..."
        if ! git pull --quiet 2>/dev/null; then
            log_error "Git pull failed"
            return 1
        fi
    else
        # Stable: checkout the target tag
        log_info "Checking out ${TARGET_REF}..."

        # Restore composer files before checkout to avoid conflicts
        git checkout --quiet -- composer.json composer.lock 2>/dev/null || true

        if ! git checkout "$TARGET_REF" 2>/dev/null; then
            log_error "Git checkout failed for ${TARGET_REF}"
            return 1
        fi
    fi

    local new_head
    new_head=$(git rev-parse HEAD)
    log_info "Updated: ${SAVED_HEAD:0:8} -> ${new_head:0:8}"
    return 0
}

########################################################################
# POST-UPDATE STEPS
########################################################################

#######################################
# Run post-update tasks (composer, migrations, etc.)
#######################################
post_update() {
    log_info "Running post-update steps..."

    # Clear caches for new code
    log_verbose "Clearing caches after update..."
    php "${LIBRENMS_DIR}/artisan" optimize:clear &>/dev/null || log_warn "Post-update cache clear had issues"

    # Re-add user-installed plugins
    if [[ -f "${LIBRENMS_DIR}/composer.plugins.json" ]]; then
        local plugins
        plugins=$(php -r "
            \$p = json_decode(file_get_contents('${LIBRENMS_DIR}/composer.plugins.json'), true);
            if (is_array(\$p) && isset(\$p['require'])) {
                foreach (\$p['require'] as \$pkg => \$ver) echo \$pkg . ':' . \$ver . ' ';
            }
        " 2>/dev/null)

        if [[ -n "$plugins" ]]; then
            log_verbose "Re-adding user plugins: ${plugins}"
            # shellcheck disable=SC2086
            FORCE=1 eval "$COMPOSER require --update-no-dev --no-install $plugins" &>/dev/null || log_warn "Plugin re-add had issues"
        fi
    fi

    # Composer install (fast — packages already cached from pre-cache step)
    log_info "Installing composer packages..."
    if ! eval "$COMPOSER install --no-dev" 2>/dev/null; then
        log_error "Composer install failed"
        return 1
    fi

    # Re-optimize autoloader for production
    log_verbose "Optimizing autoloader..."
    eval "$COMPOSER dump-autoload --optimize" &>/dev/null || log_warn "Autoloader optimization had issues"

    # Maintenance mode ON
    log_verbose "Enabling maintenance mode..."
    php "${LIBRENMS_DIR}/artisan" down &>/dev/null || log_warn "Failed to enable maintenance mode"

    # Database migrations
    log_info "Running database migrations..."
    if ! "${LIBRENMS_DIR}/lnms" migrate --force --no-interaction --isolated 2>/dev/null; then
        log_error "Database migration failed"
        # Maintenance mode OFF before returning failure
        php "${LIBRENMS_DIR}/artisan" up &>/dev/null
        return 1
    fi

    # Install Python requirements if changed
    if [[ "$REQUIREMENTS_CHANGED" == "true" ]]; then
        log_info "Installing Python requirements..."
        pip3 install -r "${LIBRENMS_DIR}/requirements.txt" &>/dev/null || log_warn "pip3 install had issues"
    fi

    # Reset CLI opcache
    log_verbose "Resetting opcache..."
    php -r "if(function_exists('opcache_reset')) opcache_reset();" &>/dev/null || true

    # Maintenance mode OFF
    log_verbose "Disabling maintenance mode..."
    php "${LIBRENMS_DIR}/artisan" up &>/dev/null || log_warn "Failed to disable maintenance mode"

    # Final cache clear with new code + deps
    log_verbose "Final cache clear..."
    php "${LIBRENMS_DIR}/artisan" optimize:clear &>/dev/null || log_warn "Final cache clear had issues"

    log_info "Post-update steps completed"
    return 0
}

########################################################################
# ROLLBACK
########################################################################

#######################################
# Rollback to pre-update state
#######################################
rollback() {
    if [[ -z "$SAVED_HEAD" ]]; then
        log_error "No saved HEAD to rollback to"
        return 1
    fi

    log_info "Rolling back to ${SAVED_HEAD:0:8}..."

    # Ensure maintenance mode is OFF
    php "${LIBRENMS_DIR}/artisan" up &>/dev/null || true

    # Git reset to saved HEAD
    if ! git reset --hard "$SAVED_HEAD" 2>/dev/null; then
        log_error "Git reset failed"
        return 1
    fi

    # Restore composer packages
    if ! eval "$COMPOSER install --no-dev" 2>/dev/null; then
        log_error "Composer install failed during rollback"
        return 1
    fi

    # Re-optimize autoloader
    eval "$COMPOSER dump-autoload --optimize" &>/dev/null || true

    # Rollback migrations if requested
    if [[ "$FLAG_ROLLBACK_MIGRATIONS" == "true" ]]; then
        if [[ -n "$SAVED_MIGRATION" ]]; then
            local db_host db_port db_database db_username db_password rollback_steps
            db_host="${DB_HOST:-localhost}"
            db_port="${DB_PORT:-3306}"
            db_database="${DB_DATABASE:-librenms}"
            db_username="${DB_USERNAME:-librenms}"
            db_password="${DB_PASSWORD:-}"

            rollback_steps=$(mysql -h "$db_host" -P "$db_port" -u "$db_username" -p"$db_password" "$db_database" -sN -e \
                "SELECT COUNT(DISTINCT batch) FROM migrations WHERE id > (SELECT id FROM migrations WHERE migration = '${SAVED_MIGRATION}' LIMIT 1)" 2>/dev/null || echo "0")

            if (( rollback_steps > 0 )); then
                log_info "Rolling back ${rollback_steps} migration batch(es)..."
                "${LIBRENMS_DIR}/lnms" migrate:rollback --step="$rollback_steps" --force 2>/dev/null || log_warn "Migration rollback had issues"
            fi
        else
            log_warn "No saved migration found, skipping migration rollback"
        fi
    fi

    # Clear caches
    php "${LIBRENMS_DIR}/artisan" optimize:clear &>/dev/null || true

    log_info "Rollback completed"
    return 0
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

    # Resolve configuration
    resolve_update_config

    # --status mode
    if [[ "$FLAG_STATUS" == "true" ]]; then
        show_status
        exit "$EXIT_SUCCESS"
    fi

    # --rollback-only mode
    if [[ "$FLAG_ROLLBACK_ONLY" == "true" ]]; then
        acquire_lock
        log_info "Manual rollback requested"
        if rollback; then
            log_info "Manual rollback completed successfully"
            exit "$EXIT_SUCCESS"
        else
            log_error "Manual rollback failed"
            exit "$EXIT_ROLLBACK_FAIL"
        fi
    fi

    # Acquire lock
    acquire_lock

    # Pre-flight checks
    if ! preflight_checks; then
        exit "$EXIT_PREFLIGHT"
    fi

    # --pre-flight-only mode
    if [[ "$FLAG_PRE_FLIGHT_ONLY" == "true" ]]; then
        log_info "Pre-flight checks complete (--pre-flight-only)"
        exit "$EXIT_SUCCESS"
    fi

    # Determine target
    local target_result
    determine_target
    target_result=$?

    if (( target_result == 1 )); then
        log_error "Failed to determine update target"
        exit "$EXIT_UPDATE_FAIL"
    fi

    if (( target_result == 2 )); then
        # Already up to date
        exit "$EXIT_SUCCESS"
    fi

    # Composer platform pre-check
    if ! preflight_composer_reqs; then
        log_error "Composer platform requirements not met. Aborting update."
        exit "$EXIT_PREFLIGHT"
    fi

    # Pre-cache composer packages
    precache_composer

    # Python pre-check and pre-download
    preflight_python_reqs

    # --dry-run mode
    if [[ "$FLAG_DRY_RUN" == "true" ]]; then
        show_dry_run
        exit "$EXIT_SUCCESS"
    fi

    # Pre-update steps
    pre_update

    # Perform the git update
    if ! perform_update; then
        log_error "Update failed, attempting rollback..."
        if rollback; then
            log_warn "Rolled back successfully after update failure"
        else
            log_error "Rollback also failed!"
            exit "$EXIT_ROLLBACK_FAIL"
        fi
        exit "$EXIT_UPDATE_FAIL"
    fi

    # Post-update steps
    if ! post_update; then
        log_error "Post-update steps failed, attempting rollback..."
        if rollback; then
            log_warn "Rolled back successfully after post-update failure"
        else
            log_error "Rollback also failed!"
            exit "$EXIT_ROLLBACK_FAIL"
        fi
        exit "$EXIT_UPDATE_FAIL"
    fi

    log_info "Update completed successfully"
    exit "$EXIT_SUCCESS"
}

main "$@"
