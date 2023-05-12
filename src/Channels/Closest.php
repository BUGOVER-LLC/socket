<?php

declare(strict_types=1);

namespace Service\Socket\Channels;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\PrivateChannel;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature;
use Ratchet\ConnectionInterface;
use stdClass;

class Closest extends PrivateChannel
{
    /**
     * @throws InvalidSignature
     */
    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $this->verifySignature($connection, $payload);

        parent::subscribe($connection, $payload);
    }
}
