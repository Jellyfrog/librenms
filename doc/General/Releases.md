# Choosing a release

We try to make sure that changes do not cause breakage. To do this, we
use automated code checks, syntax checks, and unit tests, together
with manual code review. But bugs can occur, and large changes to make
the code better can also cause problems.

Two branches are available for you to use. The default is the `master` branch.

## Development branch

Our `master` branch is our development branch. We commit to this
branch frequently, and we frequently merge multiple commits each day.
Thus, some changes can cause unwanted problems. If this occurs, we
usually repair or remove those changes quickly.

We thank all persons who run this branch. You are a second set of
testers, after the automated tests and the manual tests that we do
when we merge changes.

To configure your installation to use this branch (this is the
default), set:

!!! setting "system/updates"
    ```bash
    lnms config:set update_channel master
    ```

Then make sure that you are on the master branch:

```bast
cd /opt/librenms
git checkout master
./daily.sh
```

## Stable branch

We also supply a monthly stable release. We release it at
approximately the middle of the month, usually on a weekday. We
usually stop merges of pull requests (bug fixes are an exception) some
days before the release. This makes sure that the branch is clean and
serviceable at that point.

We update the [changelog](Changelog.md) with the release number and
the date. There you can see the changes since the last release.

To use the stable branches, set:

!!! setting "system/updates"
    ```bash
    lnms config:set update_channel release
    ```

This stops updates until the next stable release. At that time,
LibreNMS updates to the stable release. After that, it updates only to
stable releases.

!!! warning
    LibreNMS does not support downgrades. A downgrade can cause bugs.
