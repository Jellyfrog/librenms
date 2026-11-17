<?php

/**
 * SyslogTest.php
 *
 * Tests various syslog input for proper parsing
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2016 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use App\Facades\DeviceCache;
use App\Models\Device;
use LibreNMS\Syslog\Entry;
use LibreNMS\Syslog\Processor;

uses(LibreNMS\Tests\DBTestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

// The format is:
// $SOURCEIP||$FACILITY||$PRIORITY||$LEVEL||$TAG||$YEAR-$MONTH-$DAY $HOUR:$MIN:$SEC||$MSG||$PROGRAM
// There add an IP for each OS you want to test and use that in the input file

beforeEach(function (): void {
    DeviceCache::flush();
});

/**
 * The sender in the test data is 1.1.1.1, so the device has to be findable by that.
 */
function createSyslogDevice(array $attributes): void
{
    /** @var Device $device */
    $device = Device::factory()->create($attributes + ['ip' => '1.1.1.1']);

    test()->device_id = $device->device_id;
    test()->processor = new Processor;
}

function createExpectedSyslogEntry(string $line, array $resultDelta): Entry
{
    [$host, $facility, $priority, $level, $tag, $timestamp, , $program] = explode('||', trim($line));

    return new Entry(
        host: $host,
        facility: $facility,
        priority: $priority,
        level: $level,
        tag: $tag,
        timestamp: $timestamp,
        msg: $resultDelta['msg'] ?? '',
        program: $resultDelta['program'] ?? $program,
        device_id: test()->device_id,
    );
}

/**
 * Test an input line with the modified fields
 *
 * @param  string  $inputline  The line from the syslog daemon including the ||'s
 * @param  array  $modified  of the modified fields, most likely containging the keys program and msg
 */
function checkSyslog(string $inputline, array $modified): void
{
    $expected = createExpectedSyslogEntry($inputline, $modified);
    $entry = test()->processor->parseLogLine($inputline);
    expect($entry)->not->toBeNull();
    $res = test()->processor->parse($entry);
    expect($res)->toEqual($expected);
}

test('cisco syslog', function (): void {
    createSyslogDevice(['os' => 'ios', 'version' => 1, 'hostname' => 'cisco-switch1']);

    // ---- IOS ----
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||%CARD-SEVERITY-MSG:SLOT %FACILITY-SEVERITY-MNEMONIC: Message-text||', ['program' => '%CARD-SEVERITY-MSG:SLOT %FACILITY-SEVERITY-MNEMONIC', 'msg' => 'Message-text']);
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||%FACILITY-SUBFACILITY-SEVERITY-MNEMONIC: Message-text||', ['program' => '%FACILITY-SUBFACILITY-SEVERITY-MNEMONIC', 'msg' => 'Message-text']);
    checkSyslog('1.1.1.1||local7||info||info||be||2016-03-09 03:58:25||Mar 9 11:58:24.145 UTC: %SEC-6-IPACCESSLOGS: list MNGMNT denied 120.62.186.12 1 packet ||]', ['program' => '%SEC-6-IPACCESSLOGS', 'msg' => 'list MNGMNT denied 120.62.186.12 1 packet']);
    checkSyslog('1.1.1.1||local7||info||info||be||2016-04-27 021:12:28||Apr 27 21:12:28: %SYS-5-CONFIG_I: Configured from console by vty0||', ['program' => '%SYS-5-CONFIG_I', 'msg' => 'Configured from console by vty0']);
    checkSyslog('1.1.1.1||local7||info||info||be||2016-04-27 021:12:28||Mar 8 20:14:08.762: %FACILITY-SUBFACILITY-SEVERITY-MNEMONIC: Message-text||000956', ['program' => '%FACILITY-SUBFACILITY-SEVERITY-MNEMONIC', 'msg' => 'Message-text']);

    // ---- CatOS ----
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||%IP-3-UDP_SOCKOVFL:UDP socket overflow||', ['program' => '%IP-3-UDP_SOCKOVFL', 'msg' => 'UDP socket overflow']);
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||DTP-1-ILGLCFG: Illegal config (on, isl--on,dot1q) on Port [mod/port]||', ['program' => 'DTP-1-ILGLCFG', 'msg' => 'Illegal config (on, isl--on,dot1q) on Port [mod/port]']);
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||Cannot enable text mode config if ACL config is cleared from nvram||', ['program' => '', 'msg' => 'Cannot enable text mode config if ACL config is cleared from nvram']);
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||%PAGP-5-PORTFROMSTP / %PAGP-5-PORTTOSTP||', ['program' => '%PAGP-5-PORTFROMSTP / %PAGP-5-PORTTOSTP']);
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||%SYS-3-EOBC_CHANNELREINIT||', ['program' => '%SYS-3-EOBC_CHANNELREINIT']);
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||%SYS-4-MODHPRESET:||', ['program' => '%SYS-4-MODHPRESET', 'msg' => '']);
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||InbandPingProcessFailure:Module x not responding over inband||', ['program' => 'INBANDPINGPROCESSFAILURE', 'msg' => 'Module x not responding over inband']);
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||RxSBIF_SEQ_NUM_ERROR:slot=x||', ['program' => 'RXSBIF_SEQ_NUM_ERROR', 'msg' => 'slot=x']);

    // With program from syslog
    checkSyslog('1.1.1.1||local7||notice||notice||bd||2016-04-04 15:18:43||Apr 4 13:18:42.670: %LINEPROTO-5-UPDOWN: Line protocol on Interface GigabitEthernet0/32, changed state to up||345735', ['program' => '%LINEPROTO-5-UPDOWN', 'msg' => 'Line protocol on Interface GigabitEthernet0/32, changed state to up']);

    // Incorrect time
    checkSyslog('1.1.1.1||user||info||info||0e||2016-04-06 15:20:35||*Apr 4 21:26:41.778 UTC: %LWAPP-3-REPLAY_ERR: 1 wcm: Received replay error on slot 1, WLAN ID 1, count 1 from AP xxxx.xxxx.xxxx||', ['program' => '%LWAPP-3-REPLAY_ERR', 'msg' => '1 wcm: Received replay error on slot 1, WLAN ID 1, count 1 from AP xxxx.xxxx.xxxx']);

    checkSyslog('1.1.1.1||user||info||info||0e||2016-04-06 15:20:35||.Apr 4 21:26:41.778 UTC: %LWAPP-3-REPLAY_ERR: 1 wcm: Received replay error on slot 1, WLAN ID 1, count 1 from AP xxxx.xxxx.xxxx||', ['program' => '%LWAPP-3-REPLAY_ERR', 'msg' => '1 wcm: Received replay error on slot 1, WLAN ID 1, count 1 from AP xxxx.xxxx.xxxx']);
});

test('linux syslog', function (): void {
    createSyslogDevice(['os' => 'linux', 'version' => 1, 'hostname' => 'linux-server1']);

    // ---- PAM ----
    checkSyslog('1.1.1.1||authpriv||info||info||56||2016-02-28 00:23:34||pam_unix(cron:session): session opened for user librenms by (uid=0)||CRON', ['program' => 'CRON', 'msg' => 'pam_unix(cron:session): session opened for user librenms by (uid=0)']);
    checkSyslog('1.1.1.1||authpriv||info||info||55||2016-02-28 00:23:34||pam_unix(sudo:session): session opened for user librenms by root (uid=0)||sudo', ['program' => 'SUDO', 'msg' => 'pam_unix(sudo:session): session opened for user librenms by root (uid=0)']);
    checkSyslog('1.1.1.1||auth||info||info||0e||2016-02-28 00:23:34||pam_krb5(sshd:auth): authentication failure; logname=root uid=0 euid=0 tty=ssh ruser= rhost=123.213.132.231||sshd', ['program' => 'SSHD', 'msg' => 'pam_krb5(sshd:auth): authentication failure; logname=root uid=0 euid=0 tty=ssh ruser= rhost=123.213.132.231']);
    checkSyslog('1.1.1.1||auth||info||info||0e||2016-02-28 00:23:34||pam_krb5[sshd:auth]: authentication failure; logname=root uid=0 euid=0 tty=ssh ruser= rhost=123.213.132.231||sshd', ['program' => 'SSHD', 'msg' => 'pam_krb5[sshd:auth]: authentication failure; logname=root uid=0 euid=0 tty=ssh ruser= rhost=123.213.132.231']);

    // ---- Postfix ----
    checkSyslog('1.1.1.1||mail||info||info||16||2016-02-28 00:23:34||5C62E329EF: to=<admin@example.com>, relay=mail.example.com[127.0.0.1]:25, delay=0.11, delays=0.04/0.01/0/0.06, dsn=2.0.0, status=sent (250 2.0.0 Ok: queued as 5362E6A670E)||postfix/smtp', ['program' => 'POSTFIX/SMTP', 'msg' => '5C62E329EF: to=<admin@example.com>, relay=mail.example.com[127.0.0.1]:25, delay=0.11, delays=0.04/0.01/0/0.06, dsn=2.0.0, status=sent (250 2.0.0 Ok: queued as 5362E6A670E)']);
    checkSyslog('1.1.1.1||mail||info||info||16||2016-02-28 00:23:34||D7256400EF: from=<librenms@librenms.example.com>, size=882, nrcpt=1 (queue active)||postfix/qmgr', ['program' => 'POSTFIX/QMGR', 'msg' => 'D7256400EF: from=<librenms@librenms.example.com>, size=882, nrcpt=1 (queue active)']);

    // ---- No program ----
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||some random message||', ['program' => 'USER', 'msg' => 'some random message']);

    // ---- Other ----
    checkSyslog('1.1.1.1||cron||info||info||4e||2016-02-28 00:23:34||(librenms) CMD (   /opt/librenms/alerts.php >> /var/log/librenms_alert.log 2>&1)||CRON', ['program' => 'CRON', 'msg' => '(librenms) CMD (   /opt/librenms/alerts.php >> /var/log/librenms_alert.log 2>&1)']);
    checkSyslog('1.1.1.1||authpriv||notice||notice||55||2016-02-28 00:23:34||    root : TTY=pts/1 ; PWD=/opt/librenms ; USER=librenms ; COMMAND=/usr/bin/git status||sudo', ['program' => 'SUDO', 'msg' => 'root : TTY=pts/1 ; PWD=/opt/librenms ; USER=librenms ; COMMAND=/usr/bin/git status']);
});

test('procurve syslog', function (): void {
    createSyslogDevice(['os' => 'procurve', 'version' => 1, 'hostname' => 'procurve-switch1']);

    // ---- 2900/2910/3800/5400 ----
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||chassis:  Slot A Ready||00422', ['program' => 'CHASSIS', 'msg' => 'Slot A Ready [00422]']);
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||ports:  port 21 is now on-line||00076', ['program' => 'PORTS', 'msg' => 'port 21 is now on-line [00076]']);
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||ports:  port 21 is now off-line||00077', ['program' => 'PORTS', 'msg' => 'port 21 is now off-line [00077]']);
    checkSyslog('1.1.1.1||user||warning||warning||0c||2016-02-28 00:23:34||FFI:  port 21-High collision or drop rate. See help.||00331', ['program' => 'FFI', 'msg' => 'port 21-High collision or drop rate. See help. [00331]']);

    // ---- 2610 ----
    checkSyslog('1.1.1.1||user||warning||warning||0c||2016-02-28 00:23:34||port 21-Excessive undersized/giant packets. See help.||FFI', ['program' => 'FFI', 'msg' => 'port 21-Excessive undersized/giant packets. See help.']);
    checkSyslog('1.1.1.1||user||info||info||0e||2016-02-28 00:23:34||updated time by -4 seconds||SNTP', ['program' => 'SNTP', 'msg' => 'updated time by -4 seconds']);
});

test('zywall syslog', function (): void {
    createSyslogDevice(['os' => 'zywall', 'version' => 1, 'hostname' => 'zywall']);

    // ---- USG60W ----
    checkSyslog('1.1.1.1||local1||info||info||8e||2017-06-14 17:51:25||0" dst="0.0.0.0:0" msg="DHCP server assigned 195.159.132.109 to Chromecast(6C:AD:F8:B1:10:1D)" note="DHCP ACK" user="unknown" devID="a0e4cb7d7f52" cat="DHCP"||src="0.0.0.0', ['program' => 'DHCP', 'msg' => 'src="0.0.0.0:0" dst="0.0.0.0:0" msg="DHCP server assigned 195.159.132.109 to Chromecast(6C:AD:F8:B1:10:1D)" note="DHCP ACK" user="unknown" devID="a0e4cb7d7f52" cat="DHCP"']);
});
