# Remote monitoring using tinc VPN

This article tells you how to use tinc to connect a number of remote
sites and their subnets to your central monitoring server. With this,
you can connect to devices on remote private IP ranges through one
gateway on each site. The routes send the traffic safely back to your
LibreNMS installation.

## Configuring the monitoring server

tinc is available on almost all Linux distributions through package
management. If your system is different, go to
the tinc homepage to find an applicable version for your operating
system: <https://www.tinc-vpn.org/download/>

Here, we describe the setup for Debian-based systems. But there
are almost no differences for CentOS or similar systems.

- First, make sure that your firewall accepts connections on port 655 UDP
  and TCP.
- Then install tinc with `apt-get install tinc`.
- Create this directory structure to hold all your
  configuration files: `mkdir -p /etc/tinc/myvpn/hosts` "myvpn" is
  the name of your VPN network. You can select a different name.
- Create your main configuration file: `vim /etc/tinc/myvpn/tinc.conf`

```bash
Name = monitoring
AddressFamily = ipv4
Device = /dev/net/tun
```

- Then we need network up and down scripts that set some network
  settings for the VPN: `vim /etc/tinc/myvpn/tinc-up`

```bash
#!/bin/sh
ifconfig $INTERFACE 10.6.1.1 netmask 255.255.255.0
ip route add 10.6.1.1/24 dev $INTERFACE
ip route add 10.0.0.0/22 dev $INTERFACE
ip route add 10.100.0.0/22 dev $INTERFACE
ip route add 10.200.0.0/22 dev $INTERFACE
```

- In this example, 10.6.1.1 is the VPN IP address of the
  monitoring server on a /24 subnet. The system automatically replaces
  $INTERFACE with the name of the VPN, "myvpn" in this example. Then
  there is a route for the VPN subnet. With this route, we can get
  access to other sites through their VPN address. The last 3 lines
  specify the remote subnets. In the example, I want to get access to
  devices on three different remote private /22 subnets, and monitor
  devices on them from this server. Thus, I set routes for each of
  those remote sites in my tinc-up script.

- The tinc-down script is simple. It only removes the
  special interface. This also removes the routes: `vim
  /etc/tinc/myvpn/tinc-down`

```bash
#!/bin/sh
ifconfig $INTERFACE down
```

- Make sure that your scripts are executable: `chmod +x
  /etc/tinc/myvpn/tinc-*`
- As a last step, we need a host configuration file. Its name must be
  the same as the "Name" that you set in tinc.conf: `vim
  /etc/tinc/myvpn/hosts/monitoring`

```bash
Subnet = 10.6.1.1/32
```

On the monitoring server, we only enter the subnet. We do not
set its external IP address. This makes sure that the server listens
on all available external interfaces.

- Now use tinc to create our key-pair: `tincd -n myvpn -K`
- Now the file `/etc/tinc/myvpn/hosts/monitoring` has an RSA
  public key at its end, and your private key is in
  `/etc/tinc/myvpn/rsa_key.priv`.
- To make sure that the connection starts again after each reboot,
  you can add your VPN name to `/etc/tinc/nets.boot`.
- Now you can start tinc with `tincd -n myvpn`. It listens for
  connections from your remote sites.

## Remote site configuration

The steps for all remote gateway devices are almost the same as the
steps for your central monitoring server. These devices can be
routers, or computers or VMs on the remote subnet. They must have
access to the internet, and they must be able to forward IP packets
externally.

- Install tinc
- Create the directory structure: `mkdir -p /etc/tinc/myvpn/hosts`
- Create the main configuration: `vim /etc/tinc/myvpn/tinc.conf`

```bash
Name = remote1
AddressFamily = ipv4
Device = /dev/net/tun
ConnectTo = monitoring
```

- Create the up script: `vim /etc/tinc/myvpn/tinc-up`

```bash
#!/bin/sh
ifconfig $INTERFACE 10.6.1.2 netmask 255.255.255.0
ip route add 10.6.1.2/32 dev $INTERFACE
```

- Create the down script: `vim /etc/tinc/myvpn/tinc-down`

```bash
#!/bin/sh
ifconfig $INTERFACE down
```

- Make the scripts executable: `chmod +x /etc/tinc/myvpn/tinc*`
- Create the device configuration: `vim /etc/tinc/myvpn/hosts/remote1`

```bash
Address = 198.51.100.2
Subnet = 10.0.0.0/22
```

This sets the device IP address outside of the VPN, and the subnet that the device makes available.

- Copy the host configuration of the monitoring server (which includes
  the public key). Then add its external IP address: `vim
  /etc/tinc/myvpn/hosts/monitoring`

```bash
Address = 203.0.113.6
Subnet = 10.6.1.1/32

-----BEGIN RSA PUBLIC KEY-----
VeDyaqhKd4o2Fz...
```

- Make the keys of this device: `tincd -n myvpn -K`
- Copy the host file of this device, which includes the public key,
  to your monitoring server.
- Add the name of the VPN to `/etc/tinc/nets.boot` if you want the
  connection to start automatically after a reboot.
- Start tinc: `tincd -n myvpn`

You can do these steps again for each remote site. Only select
different names and other internal IP addresses. In my configuration,
I connected 3 remote sites behind Ubiquiti EdgeRouters. Those
devices let me install software through the Debian package
management. Thus, the setup was easy. Only create the necessary
configuration files and network scripts on each device. Then send
the host configurations, which include the public keys, to each
device that connects back.

Now you can add all devices that you want to monitor in LibreNMS. Use
their internal IP address on the remote subnets, or use a type of
name resolution. I put the most important devices in my
`/etc/hosts` file on the monitoring server.

Also, tinc is a mesh VPN. Thus, you can specify more than one
"ConnectTo" on each device. Then the connections stay on,
also when one network path goes down.
