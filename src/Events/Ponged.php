<?php

declare(strict_types=1);

namespace Nucleus\Socket\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Ponged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $appId
     * @param string $socketId
     * @return void
     */
    public function __construct(public string $appId, public string $socketId)
    {
    }
}
