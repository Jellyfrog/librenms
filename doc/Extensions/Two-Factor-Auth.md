# Two-Factor Authentication

Over the last couple of years, the primary attack vector for internet
accounts has been static passwords. Therefore static passwords are no
longer sufficient to protect unauthorized access to accounts. Two
Factor Authentication adds a variable part in authentication
procedures. A user is now required to supply a changing 6-digit
passcode in addition to their password to obtain access to the account.

LibreNMS has a RFC4226 conformant implementation of both Time and Counter
based One-Time-Passwords. With it, the administrator can also
configure a throttle time to enforce after 3 failures exceeded. Unlike
RFC4226 recommendations, this throttle time does not stack on the quantity of
failures.

## Types

In general, these two types do not differ in algorithmic terms.
The types only differ in the variable being used to derive the passcodes from.
The underlying HMAC-SHA1 remains the same for both types, security
advantages or disadvantages of each are discussed further down.

### Timebased One-Time-Password (TOTP)

Like the name suggests, this type uses the current Time or a subset of
it to generate the passcodes. These passcodes solely rely on the
secrecy of their Secretkey to give passcodes. An attacker
only needs to guess that Secretkey and the other variable part is any
given time, presumably the time upon login. RFC4226 suggests a
resynchronization attempt in case the passcode mismatches, providing
the attacker a range of up to +/- 3 Minutes to create passcodes.

### Counterbased One-Time-Password (HOTP)

This type uses an internal counter that needs to be in sync with the
server's counter to successfully authenticate the passcodes. The main
advantage over timebased OTP: the attacker must not only know
the Secretkey, but also the server Counter, to create valid
passcodes. RFC4226 suggests a resynchronization attempt in case the
passcode mismatches, providing the attacker a range of up to +4
increments from the actual counter to create passcodes.

## Configuration

### WebUI

Enable 'Two-Factor' through Global Settings in the Web UI under
Authentication -> General Authentication Settings.

Optionally, enter a throttle timer in seconds. This unlocks an account 
after this time, when it failed 3 tries to authenticate. Set it to 0 (default) 
to disable this feature. Then accounts stay locked after 3 tries, 
and an administrator must clear them.

### CLI

Enable Two-Factor:

`./lnms config:set twofactor true`


Set throttle-time (in seconds):

`./lnms config:set twofactor_lock 300`

## User Administration

If Two-Factor is enabled, the Settings -> Manage Users grid shows a '2FA' column 
containing a green tick for users with active 2FA.

There is no functionality to mandate 2FA for users.

If a user has failed 3 attempts, their account can be unlocked or 2FA disabled by 
editing the user from the Manage Users table.

If a throttle timer is set, it unlocks accounts after this time. If it is set to the 
default of 0, an administrator must manually unlock accounts after 3 
failed attempts.

Locked accounts tell the user to wait for the throttle time period,
or to contact the administrator if no timer set.

## End-User Enrolment

These steps imply that Two-Factor has been enabled system wide as above under Configuration.

Each user enables 2FA when they are logged in as usual:

- Go to 'My Settings' (/preferences/)
- Choose TwoFactor type
- Click on 'Generate TwoFactor Secret Key'
- If your browser did not reload, reload manually
- Scan provided QR or click on 'Manual' to see the Key

### Authenticator Apps

- [Google Authenticator](https://support.google.com/accounts/answer/1066447?hl=en).
- [LastPass Authenticator](https://support.logmeininc.com/lastpass/help/lastpass-authenticator-lp030014).
- [Enpass Authenticator](https://support.enpass.io/app/item/generating_one_time_code_in_enpass.htm)).
