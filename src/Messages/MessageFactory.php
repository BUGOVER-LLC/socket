<?php

declare(strict_types=1);

namespace Service\Socket\Messages;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Messages\PusherMessage;
use Illuminate\Support\Str;
use JsonException;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

/**
 * Class MessageFactory
 * @package Src\Socket
 */
class MessageFactory
{
    /**
     * @param MessageInterface $message
     * @param ConnectionInterface $connection
     * @param ChannelManager $channelManager
     * @return PusherMessage
     * @throws JsonException
     */
    public static function createForMessage(
        MessageInterface $message,
        ConnectionInterface $connection,
        ChannelManager $channelManager
    ): PusherMessage {
        $payload = json_decode($message->getPayload(), false, 512, JSON_THROW_ON_ERROR);

        return Str::startsWith($payload->event, 'pusher:')
            ? new ServerMessage($payload, $connection, $channelManager)
            : new ClientMessage($payload, $connection, $channelManager);
    }
}
