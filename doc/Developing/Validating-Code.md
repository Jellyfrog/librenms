#### Validating Code

As part of the pull request procedure with GitHub, we run automated
build tests. These make sure that  the code has no errors, that it obeys the standards,
and that our test suite builds correctly.

You do not have to submit a pull request and wait for the results. You can
run these checks yourself for  an easier merge.

> Run all of these commands from the librenms
> directory. You can run them as the librenms user,  unless we say differently.

Install composer (this is not necessary if composer is already installed).

`curl -sS https://getcomposer.org/installer | php`

This installs composer into /opt/librenms/composer.phar.

Now install the necessary dependencies:

`./composer.phar install`

When composer is installed, you can run the code validation script:

`./lnms dev:check`

If you see `Tests ok, submit away :)`, all is correct. Other
output contains  what you need to correct the problems. Then test again.

#### Git Hooks

Git has a hook system. With it, you can start checks at different
stages. With `./lnms dev:check`,  you can make this a part of your
commit procedure.

Add `./lnms dev:check` to your `.git/hooks/pre-commit`:

    echo "/opt/librenms/lnms dev:check" >> /opt/librenms/.git/hooks/pre-commit
    chmod +x /opt/librenms/.git/hooks/pre-commit
