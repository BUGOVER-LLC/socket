<?php

declare(strict_types=1);

namespace Service\Socket\Commands;

use BeyondCode\LaravelWebSockets\Console\StartWebSocketServer;
use BeyondCode\LaravelWebSockets\Server\Logger\ConnectionLogger;
use BeyondCode\LaravelWebSockets\Server\Logger\HttpLogger;
use BeyondCode\LaravelWebSockets\Server\Logger\WebsocketsLogger;

/**
 * @class overrited for the debug enable mode
 */
class StartSocket extends StartWebSocketServer
{
    /**
     * @return $this|StartSocket
     */
    protected function configureHttpLogger(): StartSocket|static
    {
        app()->singleton(HttpLogger::class, function ($app) {
            return (new HttpLogger($this->output))
                ->enable($this->option('debug') ?: ($app['config']['app']['debug'] ?? false))
                ->verbose($this->output->isVerbose());
        });

        return $this;
    }

    /**
     * @return $this|StartSocket
     */
    protected function configureMessageLogger(): StartSocket|static
    {
        app()->singleton(WebsocketsLogger::class, function ($app) {
            return (new WebsocketsLogger($this->output))
                ->enable(true) // @TODO this debug already true
                ->verbose($this->output->isVerbose());
        });

        return $this;
    }

    /**
     * @return $this|StartSocket
     */
    protected function configureConnectionLogger(): StartSocket|static
    {
        app()->bind(ConnectionLogger::class, function ($app) {
            return (new ConnectionLogger($this->output))
                ->enable($app['config']['app']['debug'] ?? false)
                ->verbose($this->output->isVerbose());
        });

        return $this;
    }
}
