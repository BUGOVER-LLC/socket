<?php

declare(strict_types=1);

namespace Nucleus\Socket;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\QueryParameters;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\ConnectionsOverCapacity;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\UnknownAppKey;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\WebSocketException;
use Exception;
use JsonException;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use Nucleus\Socket\Events\Closed;
use Nucleus\Socket\Events\Connected;
use Nucleus\Socket\Events\Received;
use Nucleus\Socket\Messages\MessageFactory;

/**
 * Class RouterHandler
 * @package Nucleus\Socket
 */
class RouterHandler implements MessageComponentInterface
{
    /**
     * RouterHandler constructor.
     * @param ChannelManager $channelManager
     */
    public function __construct(protected ChannelManager $channelManager)
    {
    }

    /**
     * When a new connection is opened it will be passed to this method
     * @param ConnectionInterface $connection
     * @throws ConnectionsOverCapacity
     * @throws UnknownAppKey|JsonException
     * @throws Exception
     */
    public function onOpen(ConnectionInterface $connection)
    {
        $this
            ->verifyAppKey($connection)
            ->limitConcurrentConnections($connection)
            ->generateSocketId($connection)
            ->establishConnection($connection);

        Connected::dispatch($connection->app->id, $connection->socketId);
    }

    /**
     * @param ConnectionInterface $connection
     * @return RouterHandler
     * @throws JsonException
     */
    protected function establishConnection(ConnectionInterface $connection): RouterHandler
    {
        $connection->send(
            json_encode([
                'event' => 'pusher:connection_established',
                'data' => json_encode(['socket_id' => $connection->socketId, 'activity_timeout' => 30], JSON_THROW_ON_ERROR),
            ], JSON_THROW_ON_ERROR)
        );

        DashboardLogger::connection($connection);

        StatisticsLogger::connection($connection);

        return $this;
    }

    /**
     * @param ConnectionInterface $connection
     * @return RouterHandler
     * @throws Exception
     */
    protected function generateSocketId(ConnectionInterface $connection): RouterHandler
    {
        $socketId = sprintf('%d.%d', random_int(1, 1000000000), random_int(1, 1000000000));

        $connection->socketId = $socketId;

        return $this;
    }

    /**
     * @param ConnectionInterface $connection
     * @return RouterHandler
     * @throws ConnectionsOverCapacity
     */
    protected function limitConcurrentConnections(ConnectionInterface $connection): RouterHandler
    {
        if (null !== ($capacity = $connection->app->capacity)) {
            $connectionsCount = $this->channelManager->getConnectionCount($connection->app->id);
            if ($connectionsCount >= $capacity) {
                throw new ConnectionsOverCapacity();
            }
        }

        return $this;
    }

    /**
     * @param ConnectionInterface $connection
     * @return RouterHandler
     * @throws UnknownAppKey
     */
    protected function verifyAppKey(ConnectionInterface $connection): RouterHandler
    {
        $appKey = QueryParameters::create($connection->httpRequest)->get('appKey');

        if (!$app = App::findByKey($appKey)) {
            throw new UnknownAppKey($appKey);
        }

        $connection->app = $app;

        return $this;
    }

    /**
     * @param ConnectionInterface $connection
     * @param MessageInterface $message
     * @throws JsonException
     */
    public function onMessage(ConnectionInterface $connection, MessageInterface $message)
    {
        $message = MessageFactory::createForMessage($message, $connection, $this->channelManager);

        $message->respond();

        StatisticsLogger::webSocketMessage($connection);

        Received::dispatch(
            $connection->app->id,
            $connection->socketId,
            $message
        );
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param ConnectionInterface $connection
     */
    public function onClose(ConnectionInterface $connection)
    {
        $this->channelManager->removeFromAllChannels($connection);

        DashboardLogger::disconnection($connection);

        StatisticsLogger::disconnection($connection);

        Closed::dispatch($connection->app->id, $connection->socketId);
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param ConnectionInterface $connection
     * @param Exception $exception
     * @throws JsonException
     */
    public function onError(ConnectionInterface $connection, Exception $exception)
    {
        if ($exception instanceof WebSocketException) {
            $connection->send(json_encode($exception->getPayload(), JSON_THROW_ON_ERROR));
        }
    }
}
