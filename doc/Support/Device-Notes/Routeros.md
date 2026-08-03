With this agent script, LibreNMS can run a script on a Mikrotik / RouterOS device to collect the vlan information from /interface/vlan/ and /interface/bridge/vlan/

## Installation

- Go to https://github.com/librenms/librenms-agent/tree/master/snmp/Routeros
- Copy and paste the contents of the LNMS_vlans.scr file into a script in a RouterOS device.  Give this script the name LNMS_vlans. (This is NOT the same as when you create a txt file and import it into the Files section of the device)
- If you are not sure how to create the script:  Download the LNMS_vlans.scr file.  Rename it to remove the .scr extension.  Copy this file to all the Mikrotik devices that you want to monitor.
- Open a Terminal / CLI on each tik and run this.  ```{ :global txtContent [/file get LNMS_vlans contents]; /system/script/add name=LNMS_vlans owner=admin policy=ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon source=$txtContent ;}```  This imports the contents of that txt file into a script with the name LNMS_vlans
- Enable an SNMP community that has READ and WRITE capabilities. This is important. Without it, LibreNMS cannot run the script above. We recommend SNMP v3 for this. 
- Discover / Force rediscover your Mikrotik devices. After discovery is complete, the vlans menu shows in LibreNMS for the device.

### *** IMPORTANT NOTE ***

We strongly recommend that you permit SNMP communication only from a small set of IP addresses, from which LibreNMS and related systems come. (usually a /32 address for each) The cause: with the write permission, an attack on a device is possible. (such as removal of all firewall filters, or a change of the admin credentials) 

### Theory of operation:

The Mikrotik vlan discovery plugin uses the ability of ROS to start a script through SNMP.

First, LibreNMS makes sure that the script exists. If it is present, LibreNMS starts the LNMS_vlans script. 

The script collects information from:
- /interface/bridge/vlan for tagged ports inside bridge
- /interface/bridge/vlan for currently untagged ports inside bridge
- /interface/bridge/port for ports PVID (untagged) inside bridge
- /interface/vlan for vlan interfaces

after the script collects the information, it sends the information to LibreNMS through SNMP

the protocol is:
type,vlanId,ifName <cr>

i.e: 
T,254,ether1 means Tagged vlan 254 on port ether1

U,100,wlan2 means Untagged vlan 100 on port wlan2
