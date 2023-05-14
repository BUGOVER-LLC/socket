<?php

declare(strict_types=1);

namespace Nucleus\Socket\Channels;

use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel;
use JsonException;
use Ratchet\ConnectionInterface;
use Nucleus\Socket\Events\Subscribed;
use Nucleus\Socket\Events\Unsubscribed;
use stdClass;

class Common extends Channel
{
    /**
     * @throws JsonException
     */
    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $this->saveConnection($connection);

        $connection->send(
            json_encode([
                'event' => 'pusher_internal:subscription_succeeded',
                'channel' => $this->channelName,
            ], JSON_THROW_ON_ERROR)
        );

        Subscribed::dispatch(
            $connection->app->id,
            $connection->socketId,
            $this->getName(),
        );
    }

    /**
     * @param ConnectionInterface $connection
     */
    public function unsubscribe(ConnectionInterface $connection)
    {
        unset($this->subscribedConnections[$connection->socketId]);

        if (!$this->hasConnections()) {
            DashboardLogger::vacated($connection, $this->channelName);
        }

        Unsubscribed::dispatch(
            $connection->app->id,
            $connection->socketId,
            $this->getName()
        );
    }
}
