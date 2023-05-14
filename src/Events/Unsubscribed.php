<?php

declare(strict_types=1);

namespace Nucleus\Socket\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Unsubscribed
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $appId
     * @param string $socketId
     * @param string $channelName
     * @param stdClass|null $user
     * @return void
     */
    public function __construct(public string $appId, public string $socketId, public string $channelName, public ?stdClass $user = null)
    {
    }
}
