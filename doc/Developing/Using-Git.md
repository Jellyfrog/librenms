Git is not easy to learn. But continue with it, because it is
good to know the [basics][1][2] as a minimum.

If you want to help develop LibreNMS, and you did not use Git
before, this short introduction helps you start.

This applies when:

- The work is done on a Linux box.
- LibreNMS is installed in /opt/librenms
- You have git installed.
- You have a [GitHub Account](https://github.com/).
- You are using ssh to connect to GitHub (If not, replace
  git@github.com:/yourusername/librenms.git with <https://github.com/yourusername/librenms.git>.

** Replace yourusername with your GitHub username. **

#### Fork LibreNMS repo

You do this directly in
[GitHub](https://github.com/librenms/librenms/fork). Click the 'Fork'
button near the top right.

If you are a member of multiple organisations in GitHub,
it is possible that you must select the account to push the fork to.

#### Prepare your git environment

These are the recommended default values.

```bash
git config branch.autosetupmerge true
git config --global user.name "John Doe"
git config --global user.email johndoe@example.com
```

#### Clone the repo

When the repo is forked, you must clone it to
your local installation. There, you can make the necessary changes and submit them back.

```bash
cd /opt/
git clone git@github.com:/yourusername/librenms.git
```

#### Add Upstream repo

To pull changes from the master LibreNMS repo, you must
set it up on your system.

```bash
git remote add upstream https://github.com/librenms/librenms.git
```

Now you have two configured remotes:

- origin: This is your repository. You can push and pull changes here.
- upstream: This is the main LibreNMS repository. You can only pull changes.

#### Workflow guide

When you know the system better, you can possibly find a better workflow for
your needs. Until then, this is a safe workflow for you.

Before you start work on a new branch / feature, make sure that you are up
to date.

```bash
cd /opt/librenms
git checkout master
git pull upstream master
git push origin master
```

Note: some standard checks occur when you submit a pull request. You can run
these checks [yourself](Validating-Code.md), to make sure that there are no problems
in your  pull request.

Now, create a new branch to work on. This is important. With it, you can
work on more than one feature at a time,
and submit each one as a separate pull request. If you do all your
work in the master branch, it becomes unclear!

You must give your branch a name. If an issue is open (or closed on
GitHub), you can use that. In this example, if the issue number is
123, we use issue-123. If a post exists on the community
forum, you can use the post id, such as community-123. You can also
use a different name for your branch. But try to make it
applicable to what the branch is.

```bash
git checkout -b issue-123
```

Now, write your code. Make the necessary changes, test, change and test again
:) When you are ready to submit the updates as a pull request, commit.

```bash
git add path/to/new/files/or/folders
git commit -a -m 'Added feature to do X, Y and Z'
git push origin issue-123
```

If you must rebase against master, you can do this with:

```bash
git pull upstream master
git push origin issue-123
```

If merge conflicts occur after this, you must resolve
them before you continue.

Try to squash all commits into one. This is not mandatory, because we
can do this when we merge. But it helps if you do this before
you submit your pull request.

Now you are ready to submit a pull request from GitHub. To
do this, go to your GitHub page for the LibreNMS repo. Select the
branch that you worked on (issue-123) from the drop down to
the left. Then click 'Pull Request'. Fill in the details that
tell about your work, and click 'Create pull request'.

Thank you for your first pull request :)

This is sufficient to start you on the contribution path. If you have
other questions, go to our [Discord Server](https://t.libren.ms/discord)

### Hints and tips

Undo last commit

`git reset --soft 'HEAD^'`

Remove specific commit

`git revert <HASH>`

Restore deleted file

`git checkout $(git rev-list -n 1 HEAD -- "$file")^ -- "$file"`

Merge last two commits

`git rebase --interactive HEAD~2`

In the text file that opens, change the last commit from pick to
squash. Then save and exit.

For more tips, refer to [Oh shit, git!](http://ohshitgit.com/)

[1]: http://gitready.com
[2]: http://git-scm.com/book
