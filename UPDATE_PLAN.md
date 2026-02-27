# LibreNMS Update Script — Operator Runbook

## Overview

`scripts/update.sh` is a standalone updater for LibreNMS with pre-flight
checks, composer/pip pre-caching, maintenance mode, and automatic rollback.
It replaces the update logic previously embedded in `daily.sh`.

## Prerequisites

- LibreNMS installed via git clone
- PHP >= 8.2
- Composer (managed via `scripts/composer_wrapper.php`)
- Python 3 with pip3
- MySQL/MariaDB client (for pre-flight DB check and migration rollback)
- Running as the LibreNMS user (default: `librenms`)

## Configuration (.env)

Add these to your `.env` file:

```
UPDATE_ENABLED=true       # Set to false to disable updates
UPDATE_CHANNEL=nightly    # 'nightly' (track master) or 'stable' (track tags)
```

## CLI Flags

| Flag | Description |
|------|-------------|
| `--help` | Show help and exit |
| `--verbose` | Verbose output |
| `--quiet` | Suppress non-error output |
| `--dry-run` | Show plan without changes |
| `--force` | Override Docker/disabled checks |
| `--pre-flight-only` | Run checks only |
| `--status` | Show current status |
| `--rollback-migrations` | Enable DB rollback on failure |
| `--rollback-only` | Manual rollback to saved state |
| `--skip-composer` | Skip composer install |
| `--skip-migrations` | Skip DB migrations |

## Exit Codes

| Code | Meaning |
|------|---------|
| 0 | Success |
| 1 | Disabled or general failure |
| 2 | Lock conflict |
| 3 | Pre-flight failure |
| 4 | Update failure |
| 5 | Rollback failure |

## Examples

```bash
# Check system readiness
./scripts/update.sh --pre-flight-only --verbose

# See what would happen
./scripts/update.sh --dry-run

# Normal update (nightly channel)
./scripts/update.sh --verbose

# Force update in Docker
./scripts/update.sh --force --verbose

# Manual rollback
./scripts/update.sh --rollback-only --rollback-migrations
```

## Cron Setup

Replace the daily.sh update cron with:

```cron
# LibreNMS update (runs daily at 3am)
0 3 * * * librenms /opt/librenms/scripts/update.sh --quiet 2>&1 | logger -t librenms-update

# Keep daily.sh for DB cleanup and peeringdb caching
15 0 * * * librenms /opt/librenms/daily.sh
```

## Troubleshooting

### Lock file conflict (exit code 2)
Another update is running. Check `storage/app/private/update.lock` for the
PID. If stale (process not running), the script auto-removes it on next run.

### Pre-flight failure (exit code 3)
Run `--pre-flight-only --verbose` to see which check failed. Common causes:
- Low disk space (< 1GB)
- Uncommitted git changes
- PHP version too old
- Database unreachable

### Update failure (exit code 4)
The script automatically rolls back git changes and restores composer packages.
Check `logs/update.log` for details.

### Rollback failure (exit code 5)
Manual intervention required. Check `logs/update.log` and restore from the
backup in `storage/app/private/update-backup/`.

## Migration from daily.sh

1. Add `UPDATE_ENABLED=true` and `UPDATE_CHANNEL=nightly` to `.env`
2. Replace the daily.sh cron entry with `scripts/update.sh`
3. Keep `daily.sh` for DB cleanup and peeringdb caching
4. Run `./scripts/update.sh --pre-flight-only --verbose` to verify setup

## Rollback Procedures

### Automatic rollback
On update or post-update failure, the script automatically:
1. Disables maintenance mode
2. Resets git to the pre-update HEAD
3. Re-installs composer packages
4. Optionally rolls back migrations (with `--rollback-migrations`)

### Manual rollback
```bash
./scripts/update.sh --rollback-only --rollback-migrations
```
This reads the saved HEAD and migration state from
`storage/app/private/update-backup/` and reverses the update.
