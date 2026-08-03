A LibreNMS user, [Dan](https://twitter.com/thedanbrown), supplied the
full procedure and the scripts to do a migration from Observium to
LibreNMS.

We keep a copy of the scripts, with permission. The scripts are in the
`scripts\Migration` folder of your installation.

# Setup:

The scripts do these tasks:

-   Make the RRD directories on LibreNMS
-   Convert the RRD files on Observium to XML (for a move from x86 to x64)
-   Copy the RRD/XML files to LibreNMS
-   Convert the XML files back to RRD files
-   Add the device to LibreNMS

# Script:

Two versions of the scripts are available for download:
- One version converts the RRDs to XML, and then back to RRD files on the destination server. This is necessary if you move from x86 to x64.
- If the two servers have the same architecture, this step is not necessary. The other version copies the initial RRD files with SCP.

The procedure uses four files. **Put all four files on the two servers. The default location in the scripts is /tmp/**:

-   nodelist.txt – This file contains the list of hosts that you want to move. Each name must be the same as the hostname that Observium uses
-   mkdir.sh – This script makes the necessary directories on your LibreNMS server
-   destwork.sh – This script adds the device to LibreNMS. In one version, it also converts the XML files to RRD files
-   convert.sh – This is the primary script that you start. It does the migration

You can open the scripts and change them for your configuration. Each
file has some variables that you must set for your conversion. The
variables have clear names. If you have problems, add a comment.

# Conversion:

This section applies when:

-   Root access is available on the two servers
-   You have SSH access to the two servers
-   All four files are in the tmp directory of the two servers

We recommend that you start with only one or two hosts, and examine the
results. As an example, 10 usual devices took approximately 20 minutes
with the RRD to XML conversion. Each environment is different. Start
with a small number of hosts. Then increase to full automation.

### SSH Keys

First, exchange the SSH keys to make the login procedure of the scripts
automatic. Do these steps on your Observium server:

`ssh-keygen -t rsa`

Accept the default values. You can enter a passphrase. Then:

`ssh-copy-id librenms`

Here, librenms is the hostname or the IP address of your destination
server.

## Nodelist.txt

The nodelist.txt file contains the list of hosts that you want to
migrate from Observium. Each name must be the same as the name of the
RRD folder on Observium. To get the names, run this command:

`ls /opt/observium/rrd/`

Important: the nodelist.txt file must be on **the Observium server and
the LibreNMS server**. When you have your list, open nodelist.txt with
nano:

`nano /tmp/nodelist.txt`

Replace the example data with the hosts that you convert. Push CTRL+X
and then Y to save your changes. Make the same changes on the LibreNMS
server.

## Script Variables

When nodelist.txt is correct, set the variables in the three shell
scripts. Start with convert.sh. Open it with nano:

`nano /tmp/convert.sh`

Change the variables for your environment. This is the list of
variables:

-   DEST – The IP address or the hostname of your LibreNMS server
-   L_RRDPATH – The location of the LibreNMS RRD directory. The default value is the default installation location
-   O_RRDPATH – The location of the Observium RRD directory. The default value is the default installation location
-   MKDIR – The location of the mkdir.sh script
-   DESTSCRIPT – The location of the destwork.sh script
-   NODELIST – The location of the nodelist.txt file

Then open the destwork.sh script:

`nano /tmp/destwork.sh`
