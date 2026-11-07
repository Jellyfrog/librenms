<?php

namespace LibreNMS\Enum;

enum OutageAction: string
{
    case Open = 'open';
    case Close = 'close';
    case None = 'none';
}
