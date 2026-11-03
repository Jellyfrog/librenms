<?php

/* Copyright (C) 2015 James Campbell <neokjames@gmail.com>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>. */

/* Copyright (C) 2015 Daniel Preussker <f0o@devilcode.org>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>. */

/**
 * Pushover API Transport
 *
 * @author neokjames <neokjames@gmail.com>
 * @copyright 2015 neokjames, f0o, LibreNMS
 * @license GPL
 */

namespace LibreNMS\Alert\Transport;

use Illuminate\Notifications\AnonymousNotifiable;
use LibreNMS\Alert\Transport;
use LibreNMS\Enum\AlertState;
use LibreNMS\Exceptions\AlertTransportDeliveryException;
use LibreNMS\Util\Http;
use NotificationChannels\Pushover\Exceptions\CouldNotSendNotification;
use NotificationChannels\Pushover\Exceptions\ServiceCommunicationError;
use NotificationChannels\Pushover\Pushover as PushoverClient;
use NotificationChannels\Pushover\PushoverMessage;
use NotificationChannels\Pushover\PushoverReceiver;

class Pushover extends Transport
{
    public function deliverAlert(array $alert_data): bool
    {
        $options = $this->parseUserOptions($this->config['options'] ?? '');

        // Entities are html encoded so html() will cause them to be displayed correctly in pushover alerts
        $message = PushoverMessage::create((string) $alert_data['msg'])
            ->title((string) $alert_data['title'])
            ->html();

        if ($alert_data['severity'] == 'critical') {
            $message->highPriority();
            if (! empty($options['sound_critical'])) {
                $message->sound($options['sound_critical']);
            }
        } elseif ($alert_data['severity'] == 'warning') {
            $message->normalPriority();
            if (! empty($options['sound_warning'])) {
                $message->sound($options['sound_warning']);
            }
        }

        if ($alert_data['state'] == AlertState::RECOVERED) {
            $message->normalPriority();
            if (! empty($options['sound_ok'])) {
                $message->sound($options['sound_ok']);
            }
        }

        $receiver = PushoverReceiver::withUserKey($this->config['userkey']);

        // Unset message fields are null and would be sent as empty values, so remove them.
        // User options are free form and can set fields PushoverMessage does not know, so merge them here.
        // This calls the client directly as PushoverChannel swallows connection errors.
        $fields = array_filter($message->toArray(), fn ($value) => $value !== null);
        $params = array_merge($fields, $receiver->toArray(), $options);

        try {
            (new PushoverClient(Http::guzzleClient(), $this->config['appkey']))->send($params, new AnonymousNotifiable);
        } catch (CouldNotSendNotification|ServiceCommunicationError $e) {
            throw new AlertTransportDeliveryException($alert_data, $e->getCode(), $e->getMessage(), $message->content, $params);
        }

        return true;
    }

    public static function configTemplate(): array
    {
        return [
            'config' => [
                [
                    'title' => 'Api Key',
                    'name' => 'appkey',
                    'descr' => 'Api Key',
                    'type' => 'password',
                ],
                [
                    'title' => 'User/Group Key',
                    'name' => 'userkey',
                    'descr' => 'User/Group Key',
                    'type' => 'password',
                ],
                [
                    'title' => 'Pushover Options',
                    'name' => 'options',
                    'descr' => 'Pushover options',
                    'type' => 'textarea',
                ],
            ],
            'validation' => [
                'appkey' => 'required',
                'userkey' => 'required',
            ],
        ];
    }
}
