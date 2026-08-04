# Merging Pull Requests

### GitHub

We build the monthly change log from our GitHub commits. When
you merge a commit, make  sure that you:

- Click the `Merge pull request` button
- Give the merge a short title that tells what it does
- Add one of these tags at the start of the commit message. Then
  the pull request shows in the changelog:
  - devices: or newdevice: For new device support.
  - feature: or feat: To show that this is a new or updated feature
  - webui: or web: To show that this is an update to the WebUI
  - fix: or bugfix: To show that this is a bug fix.
  - refactoring: or refactor: When the changes refactor a large
    portion of code
- You can refer to an issue number with `#xyz`, that is, `#1234`
- Use the `Confirm squash and merge` button to merge.

### Example commits

#### Feature

feature: Added new availability map #4401

#### New device

newdevice: Added support for Cisco ASA #4402
