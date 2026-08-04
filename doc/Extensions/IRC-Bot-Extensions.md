# IRC Bot Extensions

Okay this is a very quick walk-through in writing your own `commands` for the IRC-Bot.

First, create a file in `includes/ircbot`. The file-name must
be in this format: `command.inc.php`.

When editing the file, do not open nor close PHP-tags.
The system discards each variable that you assign as soon as your command returns.
Some variables, specially all listed under `$this->`, have special meanings or effects.
Before a command runs, the IRC-Bot makes sure that the
MySQL-Socket is working, that `$this->user` points to the right user
and that the user is authenticated.
Below is a table with related functions and attributes.
You can chain-load any built-in command by calling `$this->_command("My Parameters")`.
You cannot chain-load external commands.

To enable your command, edit your `config.php` and add something like this:

```php
   ...
   $config['irc_external'][] = "mycommand";
   ...
```

See: [Example](#example)

## Functions and Attributes

... that are accessible from within an extension

### Functions

Function( (Type) $Variable [= Default] [,...] ) | Returns | Description
--- | --- | ---
`$this->getChan( )` | `String` | Returns `channel` of current event.
`$this->getData( (boolean) $Block = false )` | `String/Boolean` | Returns a `line` from the IRC-Buffer if it does not match a different `command`. If `$Block` is `true`, it waits until an applicable line is returned.
`$this->getUser( )` | `String` | Returns `nick` of current user. Not to confuse with `$this->user`!
`$this->get_user( )` | `Array` | See `$this->user` in Attributes.
`$this->irc_raw( (string) $Protocol )` | `Boolean` | Sends raw IRC-Protocol.
`$this->isAuthd( )` | `Boolean` | `true` if the user is authenticated.
`$this->joinChan( (string) $Channel )` | `Boolean` | Joins given `$Channel`.
`$this->log( (string) $Message )` | `Boolean` | Logs given `$Message` into `STDOUT`.
`$this->read( (string) $Buffer )` | `String/Boolean` | Returns a `line` from the given `$Buffer`, or `false` if there is nothing applicable in the Buffer. Use `$this->getData()` to get data safely in handlers.
`$this->respond( (string) $Message )` | `Boolean` | Responds to the `request` auto-detecting channel or private message.

### Attributes

Attribute | Type | Description
--- | --- | ---
`$params` | `String` | Contains all arguments that are passed to the `.command`.
`$this->chan` | `Array` | Channels that are configured.
`$this->commands` | `Array` | Contains accessible `commands`.
`$this->config` | `Array` | Contains `$config` from `config.php`.
`$this->data` | `String` | Contains raw IRC-Protocol.
`$this->debug` | `Boolean` | Debug-Flag.
`$this->external` | `Array` | Contains loaded extra `commands`.
`$this->nick` | `String` | Bot's `nick` on the IRC.
`$this->pass` | `String` | IRC-Server's passphrase.
`$this->port` | `Int` | IRC-Server's port-number.
`$this->server` | `String` | IRC-Server's hostname.
`$this->ssl` | `Boolean` | SSL-Flag.
`$this->tick` | `Int` | Interval to check buffers in microseconds.
`$this->user` | `Array` | Array containing details about the `user` that sent the `request`.

## Example

`includes/ircbot/join-ng.inc.php`

```php
   if( $this->user['level'] != 10 ) {
      return $this->respond("Sorry only admins can make me join.");
   }
   if( $this->getChan() == "#noc") {
      $this->respond("Joining $params");
      $this->joinChan($params);
   } else {
      $this->respond("Sorry, only people from #noc can make join.");
   }
```

`config.php`

```php
   ...
   $config['irc_external'][] = "join-ng";
   ...
```
