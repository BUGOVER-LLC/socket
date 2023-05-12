<?php

declare(strict_types=1);

namespace Service\Socket\Channels;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\PresenceChannel;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature;
use JsonException;
use Ratchet\ConnectionInterface;
use Service\Socket\Events\Unsubscribed;
use stdClass;

class Presence extends PresenceChannel
{
    /**
     * @throws InvalidSignature
     * @throws JsonException
     */
    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $this->verifySignature($connection, $payload);

        $this->saveConnection($connection);

        $channelData = json_decode($payload->channel_data, true, 512, JSON_THROW_ON_ERROR);

        // The ID of the user connecting
        $userId = (string)$channelData['user_id'];

        // Check if the user was already connected to the channel before storing the connection in the state
        $userFirstConnection = !isset($this->users[$userId]);

        // Add or replace the user info in the state
        $this->users[$userId] = $channelData['user_info'] ?? [];

        // Add the socket ID to user ID map in the state
        $this->sockets[$connection->socketId] = $userId;

        // Send the success event
        $connection->send(
            json_encode([
                'event' => 'pusher_internal:subscription_succeeded',
                'channel' => $this->channelName,
                'data' => json_encode($this->getChannelData(), JSON_THROW_ON_ERROR),
            ], JSON_THROW_ON_ERROR)
        );

        // The `pusher_internal:member_added` event is triggered when a user joins a channel.
        // It's quite possible that a user can have multiple connections to the same channel
        // (for example by having multiple browser tabs open)
        // and in this case the events will only be triggered when the first tab is opened.
        if ($userFirstConnection) {
            $this->broadcastToOthers($connection, [
                'event' => 'pusher_internal:member_added',
                'channel' => $this->channelName,
                'data' => json_encode($channelData, JSON_THROW_ON_ERROR),
            ]);
        }

        Unsubscribed::dispatch(
            $connection->app->id,
            $connection->socketId,
            $this->getName(),
            $channelData
        );
    }

    /**
     * @throws JsonException
     */
    public function unsubscribe(ConnectionInterface $connection)
    {
        parent::unsubscribe($connection);

        if (!isset($this->sockets[$connection->socketId])) {
            return;
        }

        // Find the user ID belonging to this socket
        $userId = $this->sockets[$connection->socketId];

        // Remove the socket from the state
        unset($this->sockets[$connection->socketId]);

        // Test if the user still has open sockets to this channel
        $userHasOpenConnections = null !== (array_flip($this->sockets)[$userId] ?? null);

        // The `pusher_internal:member_removed` is triggered when a user leaves a channel.
        // It's quite possible that a user can have multiple connections to the same channel
        // (for example by having multiple browser tabs open)
        // and in this case the events will only be triggered when the last one is closed.
        if (!$userHasOpenConnections) {
            $this->broadcastToOthers($connection, [
                'event' => 'pusher_internal:member_removed',
                'channel' => $this->channelName,
                'data' => json_encode([
                    'user_id' => $userId,
                ], JSON_THROW_ON_ERROR),
            ]);

            // Remove the user info from the state
            unset($this->users[$userId]);
        }

        Unsubscribed::dispatch(
            $connection->app->id,
            $connection->socketId,
            $this->getName(),
            $this->users[$userId]
        );
    }
}
