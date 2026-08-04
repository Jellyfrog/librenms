This document is divided into sections for the type of
support that you add. In all of these examples, we
use the OS `pulse` as the example OS that we add.

- [Adding the initial detection.](os/Initial-Detection.md)
- [Adding Memory and CPU information.](os/Mem-CPU-Information.md)
- [Adding Health / Sensor information.](os/Health-Information.md)
- [Adding Wireless Sensor information.](os/Wireless-Sensors.md)
- [Adding custom graphs.](os/Custom-Graphs.md)
- [Adding Unit tests (required).](os/Test-Units.md)
- [Optional Settings](os/Settings.md)

There is a script in the pre-beta stage that can make the
procedure to add a new OS faster. It has basic support to add
sensors (state sensors are an exception).

In this example, we add a new OS with the name test-os. We use the device
ID 101, which is already added. The OS is of the type network and
belongs to the vendor, Cisco:

`./scripts/new-os.php -h 101 -o test-os -t network -v cisco`

The script then goes through the procedure with you, with some more
questions. Warning: this is  pre-beta at this time, and it can cause
some problems. Tell us about problems on [Discord](https://t.libren.ms/discord).
