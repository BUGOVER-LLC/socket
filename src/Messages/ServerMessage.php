<?php

declare(strict_types=1);

namespace Nucleus\Socket\Messages;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Messages\PusherMessage;
use Illuminate\Support\Str;
use JsonException;
use Ratchet\ConnectionInterface;
use stdClass;

class ServerMessage implements PusherMessage
{
    /**
     * @param stdClass $payload
     * @param ConnectionInterface $connection
     * @param ChannelManager $channelManager
     */
    public function __construct(protected stdClass $payload, protected ConnectionInterface $connection, protected ChannelManager $channelManager)
    {
    }

    /**
     *
     */
    public function respond()
    {
        $eventName = Str::camel(Str::after($this->payload->event, ':'));

        if ('respond' !== $eventName && method_exists($this, $eventName)) {
            /** @noinspection VariableFunctionsUsageInspection */
            \call_user_func([$this, $eventName], $this->connection, $this->payload->data ?? new stdClass());
        }
    }

    /*
     * @link https://pusher.com/docs/pusher_protocol#ping-pong
     */

    /**
     * @param ConnectionInterface $connection
     * @param stdClass $payload
     */
    public function unsubscribe(ConnectionInterface $connection, stdClass $payload): void
    {
        $channel = $this->channelManager->findOrCreate($connection->app->id, $payload->channel);

        $channel->unsubscribe($connection);
    }

    /*
     * @link https://pusher.com/docs/pusher_protocol#pusher-subscribe
     */

    /**
     * @param ConnectionInterface $connection
     * @throws JsonException
     */
    protected function ping(ConnectionInterface $connection): void
    {
        $connection->send(json_encode(['event' => 'pusher:pong'], JSON_THROW_ON_ERROR));
    }

    /**
     * @param ConnectionInterface $connection
     * @param stdClass $payload
     */
    protected function subscribe(ConnectionInterface $connection, stdClass $payload): void
    {
        $channel = $this->channelManager->findOrCreate($connection->app->id, $payload->channel);

        $channel->subscribe($connection, $payload);
    }
}
