## Weechat BOT

The Weechat BOT transport can send alerts to an IRC channel through the Weechat Bot UDP listener.

Documentation for how to enable the UDP listener is on [the project site](https://github.com/sndrsmnk/weechatbot#udp-listener)

This transport is also compatible with Gozerbot. Gozerbot does not have the concept of an IRC server. Thus, keep this field empty for Gozerbot compatibility.

**Example:**
| Config | Example |
| ------ | ------- |
| Weechat Bot server | wcb.example.com |
| Weechat Bot port | 47774 |
| UDP listener Password | s00p3rzeeKRiT! |
| IRC server | IRCnet |
| IRC channel | #librenms |
