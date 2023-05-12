<?php

declare(strict_types=1);

namespace Service\Socket\Provider;

use Illuminate\Database\Eloquent\Model;
use Service\Socket\Commands\Tcp;
use Src\Core\Enums\ConstQueue;

/**
 * Class MessageProvider
 * @package Service\Socket\Messages
 */
class MessageProvider
{
    /**
     * MessageProvider constructor.
     * @param  string  $routeFile
     * @param  array  $routeData
     * @param  Model  $user
     * @param  object  $data
     */
    public function __construct(protected string $routeFile, protected array $routeData, protected Model $user, protected object $data)
    {
    }

    /**
     *
     */
    public function routeProvider(): void
    {
        $routes = require base_path("routes/socket/$this->routeFile.php");
        $class = array_keys($routes[$this->routeData['url']])[0];

        if (!class_exists($class)) {
            return;
        }

        Tcp::dispatch($this->user, $this->data, $class, $routes, $this->routeData)->onQueue(ConstQueue::tcp()->getValue());
    }
}
