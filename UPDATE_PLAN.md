# Plan: New Standalone LibreNMS Update Script (`scripts/update.sh`)

## Context

The current update system is embedded inside `daily.sh` — a monolithic script mixing code updates with DB cleanup. It lacks automatic rollback, pre-flight validation, lock files, and stores update config in the database. Legacy PHP 5.6/7.x branch-switching code is still present. This creates a standalone, robust updater with .env-based config, pre-flight checks, composer/pip pre-caching, maintenance mode, and automatic rollback.

---

## All Design Decisions

- **Pure bash** orchestration — no Laravel/PHP boot for script logic
- **Config from .env**: `UPDATE_ENABLED`, `UPDATE_CHANNEL` (stable/nightly)
- **Standalone** — not called from daily.sh, can be cron'd or run manually
- **Update scope only** — no DB cleanup tasks (daily.sh handles those)
- **Composer via wrapper**: all composer calls use `php scripts/composer_wrapper.php` (handles install/proxy/updates of composer.phar)
- **Composer pre-check**: checkout future composer.json/lock from target ref, `composer check-platform-reqs`
- **Composer pre-cache**: `composer install --download-only` from target files
- **Autoloader strategy**:
  - BEFORE git pull: `composer dump-autoload` (removes optimized classmap, falls back to PSR-4 discovery — site stays working)
  - AFTER composer install: `composer dump-autoload --optimize` (re-optimize for production)
- **Python pre-check**: extract future requirements.txt, `pip3 download` (to default pip cache), install after pull
- **Auto pip install**: if requirements.txt changed, `pip3 install -r requirements.txt` (from cache)
- **Maintenance mode**: `artisan down` before migrations, `artisan up` after
- **Cache clearing**: `artisan optimize:clear` before AND after git update
- **Opcache**: `php -r "opcache_reset();"` after update; future web endpoint for FPM opcache
- **DB rollback**: off by default, `--rollback-migrations` flag to enable
- **Migration tracking**: save last migration name (not batch count)
- **Docker**: warn if detected, allow with `--force`
- **Colors**: ANSI colors when stdout is terminal, plain text when piped/quiet
- **Lock in `storage/app/private/`**: file-based lock with stale PID detection
- **Signals**: SIGINT/SIGTERM → release lock and exit (no rollback)
- **Permissions**: trust git (running as correct user)
- **No submodules**, no network pre-check, no hooks, no cooldown
- **Logging**: append to `logs/update.log`
- **Notifications**: failures only (via existing daily.php handler)
- **Legacy cleanup**: remove `check_dependencies()` from daily.sh
- **Incremental commits**: each implementation step gets its own commit
- **Pip has no `check-platform-reqs`** — `pip3 download` acts as de facto platform check (fails if packages don't support current Python version)

---

## Implementation Checklist (one commit per step)

### Commit 1: Write plan to project root
- [ ] Create `UPDATE_PLAN.md` in project root with the implementation plan
- [ ] Commit as the first step so the plan is tracked in git

### Commit 2: Scaffold `scripts/update.sh` with argument parsing
- [ ] Create `scripts/update.sh` (executable)
- [ ] Add shebang, license header, constants (exit codes, paths)
- [ ] Define `$COMPOSER` = `php ${LIBRENMS_DIR}/scripts/composer_wrapper.php --no-interaction`
- [ ] Implement `parse_args()` with all flags
- [ ] Implement `load_env()` — source .env
- [ ] Implement `check_user()` — verify LIBRENMS_USER
- [ ] Implement `main()` skeleton that calls parse_args, load_env, check_user
- [ ] Add `--help` output listing all flags
- [ ] Make the script exit cleanly with appropriate codes

### Commit 3: Remove legacy check_dependencies() from daily.sh
- [ ] Remove `check_dependencies()` function (lines 132-197)
- [ ] Remove call at line 286-287 and `$php_ver_ret` usage
- [ ] Remove call at line 350 in post-pull case
- [ ] Remove call at line 363 in post-pull case
- [ ] Adjust conditionals that used `$php_ver_ret`
- [ ] Test that daily.sh still runs cleanup tasks correctly

### Commit 4: Add lock mechanism
- [ ] Implement `acquire_lock()` — file-based atomic lock in `storage/app/private/`
- [ ] Implement `release_lock()` — rm lock
- [ ] Add stale lock detection (read PID, check `kill -0`)
- [ ] Register `trap release_lock EXIT INT TERM`
- [ ] Wire into main()

### Commit 5: Add structured logging
- [ ] Implement `log_msg(level, message)` — `[ISO-8601] [LEVEL] message`
- [ ] Write to `logs/update.log` (append)
- [ ] Auto-detect terminal for colored output (green INFO, red ERROR, yellow WARN)
- [ ] Respect `--verbose` and `--quiet` flags

### Commit 6: Add config resolution
- [ ] Implement `resolve_update_config()` — read UPDATE_ENABLED, UPDATE_CHANNEL from .env
- [ ] Normalize channel names (master→nightly, release→stable)
- [ ] Apply CLI overrides (`--channel`, `--force`)
- [ ] Handle UPDATE_ENABLED=false exit (code 1)
- [ ] Implement `--status` output (channel, current HEAD, current tag, last update time)

### Commit 7: Add .env.example entries
- [ ] Append `#UPDATE_ENABLED=true` and `#UPDATE_CHANNEL=nightly` to `.env.example`

### Commit 8: Add pre-flight checks (system)
- [ ] Implement `preflight_disk_space()` — df -m, require 1GB free
- [ ] Implement `preflight_git_state()` — .git exists, git binary, clean working tree
- [ ] Implement `preflight_permissions()` — test -w on logs/, storage/, vendor/, bootstrap/cache/
- [ ] Implement `preflight_php_version()` — php -v >= 8.2 via bash string compare (early fail before fetching target; `preflight_composer_reqs` catches it too but runs later)
- [ ] Implement `preflight_python_deps()` — scripts/check_requirements.py
- [ ] Implement `preflight_db()` — mysql CLI "SELECT 1" using .env creds
- [ ] Implement `preflight_docker()` — check /.dockerenv, DOCKER_LIBRENMS env
- [ ] Wire all into `preflight_checks()` with pass/fail summary
- [ ] Support `--pre-flight-only` exit after checks

### Commit 9: Add target determination and version comparison
- [ ] Copy `version_compare()` from daily.sh (lines 209-243)
- [ ] Implement `determine_target()`
  - nightly: `git fetch origin master`, TARGET_REF=origin/master
  - stable: `git fetch --tags`, find latest tag
- [ ] Log determined target ref

### Commit 10: Add composer platform pre-check
- [ ] Implement `preflight_composer_reqs()`
- [ ] Extract composer.json + composer.lock from TARGET_REF via `git show`
- [ ] Write to temp dir
- [ ] Run `$COMPOSER check-platform-reqs --working-dir=$TMPDIR --no-dev` (via composer_wrapper.php)
- [ ] Fail early with clear message if platform requirements not met
- [ ] Clean up temp dir

### Commit 11: Add composer pre-cache (download only)
- [ ] Implement `precache_composer()`
- [ ] Reuse temp dir with future composer.json/lock
- [ ] Run `$COMPOSER install --no-dev --download-only --working-dir=$TMPDIR` (via composer_wrapper.php)
- [ ] Log cache population result
- [ ] Clean up temp dir

### Commit 12: Add Python pre-check and pre-download
- [ ] Implement `preflight_python_reqs()`
- [ ] Extract future requirements.txt from TARGET_REF via `git show`
- [ ] Compare with current requirements.txt (diff)
- [ ] If changed: `pip3 download -r $TMPDIR/requirements.txt` (to default pip cache)
- [ ] This also acts as a platform check — pip download fails if packages don't support current Python
- [ ] Log result

### Commit 13: Add dry-run support
- [ ] Implement `--dry-run` output showing what would happen
- [ ] Show: channel, target ref, current HEAD, pre-flight results
- [ ] Show: composer platform check result
- [ ] Show: python changes detected (if any)
- [ ] Exit after showing plan

### Commit 14: Add pre-update steps (cache clearing + autoloader de-optimization)
- [ ] Add `php artisan optimize:clear` call BEFORE git update (clear old code caches)
- [ ] Add `$COMPOSER dump-autoload` BEFORE git pull (removes optimized classmap, falls back to PSR-4 — site stays working during pull)
- [ ] Wire into main() between preflight and perform_update

### Commit 15: Add core update (git operations)
- [ ] Implement `perform_update()`
- [ ] nightly: `git pull --quiet` (or `git merge origin/master`)
- [ ] stable: `git checkout $TARGET_REF`
- [ ] Handle detached HEAD case for nightly (checkout master first)
- [ ] Log old HEAD → new HEAD

### Commit 16: Add post-update steps
- [ ] Implement `post_update()`
- [ ] `php artisan optimize:clear` (clear caches for new code)
- [ ] Read composer.plugins.json, re-add plugins via `FORCE=1 $COMPOSER require --update-no-dev --no-install`
- [ ] `$COMPOSER install --no-dev` (fast — packages already cached)
- [ ] `$COMPOSER dump-autoload --optimize` (re-optimize autoloader for production)
- [ ] `php artisan down` (maintenance mode ON)
- [ ] `./lnms migrate --force --no-interaction --isolated`
- [ ] If requirements.txt changed: `pip3 install -r requirements.txt` (from pip cache)
- [ ] `php -r "if(function_exists('opcache_reset')) opcache_reset();"` (CLI opcache clear)
- [ ] `php artisan up` (maintenance mode OFF)
- [ ] `php artisan optimize:clear` (final cache clear with new code + deps)
- [ ] All composer calls use `$COMPOSER` (= `php scripts/composer_wrapper.php --no-interaction`)

### Commit 17: Add rollback mechanism
- [ ] Implement `rollback()`
- [ ] `php artisan up` (ensure maintenance mode OFF)
- [ ] `git reset --hard <saved_head>` (HEAD saved before perform_update)
- [ ] `$COMPOSER install --no-dev` (via composer_wrapper.php)
- [ ] `$COMPOSER dump-autoload --optimize` (re-optimize after rollback)
- [ ] If `--rollback-migrations`:
  - Read saved last_migration name
  - Query DB: `SELECT COUNT(DISTINCT batch) FROM migrations WHERE id > (SELECT id FROM migrations WHERE migration = '<saved>' LIMIT 1)`
  - `./lnms migrate:rollback --step=N --force`
- [ ] `php artisan optimize:clear`
- [ ] Implement `--rollback-only` for manual rollback
- [ ] Wire rollback into main() failure paths

### Commit 18: Add notification and cleanup
- [ ] Implement `notify_result()` — call `php daily.php -f handle_notifiable -t update -r <0|1>`
- [ ] Implement `cleanup()` — release lock
- [ ] Wire into main() success/failure paths

### Commit 19: Update UPDATE_PLAN.md (operator runbook)
- [ ] Update `UPDATE_PLAN.md` in project root
- [ ] Document: prerequisites, .env configuration
- [ ] Document: all CLI flags with examples
- [ ] Document: exit codes and what they mean
- [ ] Document: cron setup example
- [ ] Document: troubleshooting guide
- [ ] Document: rollback procedures
- [ ] Document: migration from daily.sh to update.sh

---

### Optional/droppable commits (easily revertable if unnecessary)

### Commit 20 (optional): Add --version flag for specific version targeting
- [ ] Add `--version=X.Y.Z` argument parsing
- [ ] In `determine_target()` for stable channel: verify tag exists, checkout specific tag
- [ ] Add downgrade protection (block unless `--force`)
- [ ] Log version targeting

### Commit 21 (optional): Add backup of last migration name
- [ ] Implement `create_backup()` — save git HEAD hash and last migration name to `storage/app/private/update-backup/`
- [ ] Save last migration name via mysql query: `SELECT migration FROM migrations ORDER BY id DESC LIMIT 1`
- [ ] Use in rollback to calculate migration rollback steps
- [ ] Alternative: just rely on `git reset --hard` + `composer install` without migration-specific rollback

### Commit 22 (optional): Add poller wait mechanism
- [ ] Implement `wait_for_pollers()`
- [ ] pgrep for: poller.php, discovery.php, lnms device:poll, lnms device:discover
- [ ] Wait loop with 10-second intervals, up to 5 minutes (300 seconds)
- [ ] Log when waiting, log when pollers finish or timeout
- [ ] On timeout: warn and proceed

### Commit 23 (optional): Add --skip-composer and --skip-migrations flags
- [ ] Add `--skip-composer` to skip composer install step in post_update()
- [ ] Add `--skip-migrations` to skip DB migration step in post_update()
- [ ] Useful for debugging or partial updates

---

## Key Files

| File | Action | Purpose |
|------|--------|---------|
| `scripts/update.sh` | Create | Main update script |
| `.env.example` | Modify | Add UPDATE_ENABLED, UPDATE_CHANNEL |
| `daily.sh` | Modify | Remove check_dependencies() |
| `UPDATE_PLAN.md` | Create | Operator runbook |

---

## Verification

1. `./scripts/update.sh --pre-flight-only --verbose` — all checks pass
2. `./scripts/update.sh --dry-run` — shows plan + composer platform check
3. `./scripts/update.sh --status` — shows channel, HEAD, tag info
4. Lock test: two simultaneous runs, second exits code 2
5. Nightly update: `UPDATE_CHANNEL=nightly` + `--verbose`
6. Stable update: `UPDATE_CHANNEL=stable` + `--verbose`
7. Disabled: `UPDATE_ENABLED=false` → exit 1; with `--force` → proceeds
8. Composer pre-check: remove a PHP extension → fails before any git changes
9. Python pre-check: modify requirements.txt on target → downloads packages
10. Rollback: corrupt something post-pull → automatic rollback
11. Docker warning with `/.dockerenv`
12. Maintenance mode: `artisan down` during migrations, `artisan up` after
13. `daily.sh` works after `check_dependencies()` removal
14. SIGINT during update → lock released, no rollback attempt
