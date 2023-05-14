<?php

declare(strict_types=1);

namespace Nucleus\Socket\Broadcast;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * Class BaseBroadcast
 * @package Nucleus\Socket
 */
class BaseBroadcast
{
    /**
     * @return string|int|float|bool|null
     */
    public function getUser(): string|int|float|bool|null
    {
        return app(Request::class)->request->item('user');
    }

    /**
     * @return bool|float|int|string|InputBag|null
     */
    public function getData(): InputBag|float|bool|int|string|null
    {
        return app(Request::class)->request->item('data');
    }
}
