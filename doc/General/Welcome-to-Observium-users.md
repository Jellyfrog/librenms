LibreNMS is a fork of Observium.  The reason for the fork is not
related to Observium's [move to community vs. paid versions][1].  The
reason is that our priorities and values are different from those of
the Observium development team.  We made the fork because we like
Observium, but we want to work together on a community project with IT
professionals who think the same.  Refer to [README.md][2] and the
references there for more information about the type of community that
we want to make.

LibreNMS was forked from [the last GPL-licensed version of Observium][3].

One of our users, Dan Brown, wrote a [migration script][10]. With this
script, you can easily move your Observium installation to LibreNMS.
The script can also move data from one CPU architecture to a different
one.

The differences between LibreNMS and Observium:

- We have an open community. You can ask simple questions, and you can
  ask for functions that are not on the roadmap.  If you want a new
  function, add or comment on the applicable topic in our
  [Community forum][9].
- The community makes the development decisions.  We want to make
  software that does what its users need.
- There is no plan for a paid version, now or in the future.
- There is no plan for paid support at this time. If the demand is
  sufficient, we can add it later.
- We use git for version control and GitHub for hosting. This makes it
  easy to create forked or private versions.

Possible reasons to use Observium and not LibreNMS:

- You have a financial investment in Observium, and community
  contributions are not important to you.
- You do not like the [GNU General Public License, version 3][5] or the
  [philosophy of Free Software/copyleft][6].

Possible reasons to use LibreNMS and not Observium:

- You want to work with others on the project, and you know that [your
  time and work are not lost][7].
- You want to add and test features that are not a priority for
  the Observium developers.  Refer to [CONTRIBUTING][8] for more details.
- You want to use the additional features of LibreNMS.

[1]: http://postman.memetic.org/pipermail/observium/2013-October/003915.html
"Observium edition split announcement"
[2]: https://github.com/librenms/librenms/blob/master/README.md
"LibreNMS README"
[3]: http://fisheye.observium.org/rdiff/Observium?csid=3251&u&N
"Link to Observium license change"
[5]: https://github.com/librenms/librenms/blob/master/LICENSE.txt
"LibreNMS copy of GPL v3"
[6]: http://www.gnu.org/philosophy/free-sw.html
"Free Software Foundation - what is free software?"
[7]: https://www.libertysys.com.au/2011/03/observium-and-gpl-misconceptions/
"Paul's blog on what the GPL offers users"
[8]: ../Developing/Getting-Started.md
"Contribution guidelines"
[9]: https://community.librenms.org
"LibreNMS issue database at GitHub"
[10]: ../Installation/Migrating-from-Observium.md
"Migrating from Observium to LibreNMS"
