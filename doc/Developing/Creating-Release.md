# Creating a release

### GitHub

You can create a new release on [GitHub](https://github.com/librenms/librenms/releases/new).

Enter the tag version for that month, i.e for September 2016, enter `201609`.

Enter a title. We usually use `August 2016 Release`

Enter a placeholder for the body. We edit this later.

### Create changelog

For this, the master branch is the base to create the release against.

We make the changelog with the GitHub API itself. Thus, the state of
your local branch is not important, if it has
the code that makes the changelog.

With the GitHub API, we can use the labels attached to
merged pull requests to put the changelog in categories. We also record
who made the pull request, to thank them in the changelog itself.

The command asks for a GitHub personal access token. You can make
this [here](https://github.com/settings/tokens). No permissions are
necessary. Only give it a name and click `Generate Token`. You can
then export the token as the  environment variable `GH_TOKEN`, or put
it in your `.env` file.

Run the basic command with `artisan`. Here, you pass the `new
tag` (1.41) and the `previous tag` (1.40). For more  help, run `php
artisan release:tag --help`. This makes a changelog up to the
latest master branch. If you want  a different end point,
pass the latest pull request number with `--pr $PR_NUMBER`.

```bash
php artisan release:tag 1.41 1.40
```

- Now commit and push the change made to `doc/General/Changelog.md`.
- When the pull request for the Changelog is merged, you can
  create a new release on
  [GitHub](https://github.com/librenms/librenms/releases/new).
- Create two threads on the community site:
  - A changelog thread [example](https://community.librenms.org/t/v1-40-release-changelog-may-2018/4228/1)
  - An info thread [example](https://community.librenms.org/t/v1-40-may-2018-info/4229/)
- [Tweet it](https://twitter.com/librenms)
- [Facebook it](https://www.facebook.com/LibreNMS/)
- [Google Plus it](https://plus.google.com/u/1/b/110467424837711353117/)
- [LinkedIn it](https://www.linkedin.com/company/librenms/)
