# Send SNMPv2c GET sysDescr.0 until the agent answers. usage: snmp_probe.py community host port
import socket
import sys
import time

community, host, port = sys.argv[1].encode(), sys.argv[2], int(sys.argv[3])
oid = bytes([0x2B, 6, 1, 2, 1, 1, 1, 0])
vb = b"\x30" + bytes([4 + len(oid)]) + b"\x06" + bytes([len(oid)]) + oid + b"\x05\x00"
vbl = b"\x30" + bytes([len(vb)]) + vb
pdu_body = b"\x02\x01\x01\x02\x01\x00\x02\x01\x00" + vbl
pdu = b"\xa0" + bytes([len(pdu_body)]) + pdu_body
body = b"\x02\x01\x01\x04" + bytes([len(community)]) + community + pdu
msg = b"\x30" + bytes([len(body)]) + body

s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
s.settimeout(0.2)
deadline = time.time() + 300
while time.time() < deadline:
    try:
        s.sendto(msg, (host, port))
        if b"Linux" in s.recv(4096):
            sys.exit(0)
    except OSError:
        pass
    time.sleep(0.05)
sys.exit(1)
