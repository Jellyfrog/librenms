## Socket Statistics (ss)

The Socket Statistics application polls ss and collects socket statuses.  You can remove single sockets and address-families in the optional configuration JSON file of the script.

1. The socket types below are polled directly.  When you remove a socket type, this disables direct polling, and also indirect polling in the address-families that list the socket type as their child:
```
dccp (also exists within address-families "inet" and "inet6")
mptcp (also exists within address-families "inet" and "inet6")
raw (also exists within address-families "inet" and "inet6")
sctp (also exists within address-families "inet" and "inet6")
tcp (also exists within address-families "inet" and "inet6")
udp (also exists within address-families "inet" and "inet6")
xdp
```

2. The following socket types are polled within an address-family only:
```
inet6 (within address-family "inet6")
p_dgr (within address-family "link")
p_raw (within address-family "link")
ti_dg (within address-family "tipc")
ti_rd (within address-family "tipc")
ti_sq (within address-family "tipc")
ti_st (within address-family "tipc")
v_dgr (within address-family "vsock")
v_str (within address-family "vsock")
unknown (within address-families "inet", "inet6", "link", "tipc", and "vsock")
```

3. The address-families below are polled directly. Their child socket types are below them, with a tab before them.  When you remove a socket type (see "1" above), this removes it from the address-family.  When you remove an address-family, this removes all of its child socket types.  But if those socket types are not DIRECTLY removed (see "1" above), the system continues to monitor them, directly or in other address-families in which they exist:
```
inet
    dccp
    mptcp
    raw
    sctp
    tcp
    udp
    unknown
inet6
    dccp
    icmp6
    mptcp
    raw
    sctp
    tcp
    udp
    unknown
link
    p_dgr
    p_raw
    unknown
netlink
tipc
    ti_dg
    ti_rd
    ti_sq
    ti_st
    unknown
unix
    u_dgr
    u_seq
    u_str
vsock
    v_dgr
    v_str
    unknown
```

### SNMP Extend

1. Copy the python script, ss.py, to the desired host

    ```
    wget https://github.com/librenms/librenms-agent/raw/master/snmp/ss.py -O /etc/snmp/ss.py
    ```

2. Make the script executable

    ```
    chmod +x /etc/snmp/ss.py
    ```

3. Edit your snmpd.conf file and add:

    ```
    extend ss /etc/snmp/ss.py
    ```

4. (Optional) Create a /etc/snmp/ss.json file and specify:

    1. "ss_cmd" - String path to the ss binary: ["/sbin/ss"]

    2. "socket_types" - A comma-delimited list of socket types to include.  The following socket types are valid: dccp, icmp6, mptcp, p_dgr, p_raw, raw, sctp, tcp, ti_dg, ti_rd, ti_sq, ti_st, u_dgr, u_seq, u_str, udp, unknown, v_dgr, v_dgr, xdp.  Note that the "unknown" socket type shows in the /sbin/ss output with the netid "???".  Also note that the p_dgr and p_raw socket types apply only to the "link" address family; the ti_dg, ti_rd, ti_sq, and ti_st socket types apply only to the "tipc" address family; the u_dgr, u_seq, and u_str socket types apply only to the "unix" address family; and the v_dgr and v_str socket types apply only to the "vsock" address family.  When you remove the parent address families of these types, this also removes their socket types.  "all" includes all the socket types.  For example: to include only tcp, udp, icmp6 sockets, specify "tcp,udp,icmp6": ["all"]

    3. "addr_families" - A comma-delimited list of address families to include.  The following families are valid: inet, inet6, link, netlink, tipc, unix, vsock.  As given above under (b), when you remove the link, tipc, unix, or vsock address families, this also removes their socket types.  "all" includes all the families.  For example: to include only the inet and inet6 families, specify "inet,inet6": ["all"]

```
{
    "ss_cmd": "/sbin/ss",
    "socket_types": "all"
    "addr_families": "all"
}
```
In order to filter out uncommon/unused socket types, the following JSON configuration is recommended:
```
{
    "ss_cmd": "/sbin/ss",
    "socket_types": "icmp6,p_dgr,p_raw,raw,tcp,u_dgr,u_seq,u_str,udp",
    "addr_families": "inet,inet6,link,netlink,unix"
}
```

5. (Optional) If SELinux is in Enforcing mode, you must add a module so the script can poll sockets:
```
cat << EOF > snmpd_ss.te
module snmp_ss 1.0;

require {
    type snmpd_t;
    class netlink_tcpdiag_socket { bind create getattr nlmsg_read read setopt write };
}

#============= snmpd_t ==============

allow snmpd_t self:netlink_tcpdiag_socket { bind create getattr nlmsg_read read setopt write };
EOF
checkmodule -M -m -o snmpd_ss.mod snmpd_ss.te
semodule_package -o snmpd_ss.pp -m snmpd_ss.mod
semodule -i snmpd_ss.pp
```

6. Restart snmpd.
