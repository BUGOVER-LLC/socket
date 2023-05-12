<?php

declare(strict_types=1);

namespace Service\Socket\Messages;

use BeyondCode\LaravelWebSockets\WebSockets\Messages\PusherChannelProtocolMessage;
use JsonException;
use Ratchet\ConnectionInterface;
use Service\Socket\Events\Ponged;

class ProtocolMessage extends PusherChannelProtocolMessage
{
    /**
     * @param ConnectionInterface $connection
     * @throws JsonException
     */
    protected function ping(ConnectionInterface $connection)
    {
        $connection->send(json_encode(['event' => 'pusher:pong',], JSON_THROW_ON_ERROR));

        Ponged::dispatch($connection->app->id, $connection->socketId);
    }
}
