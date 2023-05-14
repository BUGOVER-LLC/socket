<?php

declare(strict_types=1);

namespace Nucleus\Socket\Provider;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Apps\AppProvider as BaseAppProvider;
use BeyondCode\LaravelWebSockets\Exceptions\InvalidApp;
use Illuminate\Support\Collection;

/**
 *
 */
class AppProvider implements BaseAppProvider
{
    /**
     * @var Collection
     */
    protected Collection $apps;

    public function __construct()
    {
        $this->apps = collect(config('websockets.apps'));
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->apps
            ->map(function (array $appAttributes) {
                return $this->instanciate($appAttributes);
            })
            ->toArray();
    }

    /**
     * @param  array|null  $appAttributes
     * @return App|null
     * @throws InvalidApp
     */
    protected function instanciate(?array $appAttributes): ?App
    {
        if (!$appAttributes) {
            return null;
        }

        $app = new App($appAttributes['id'], $appAttributes['key'], $appAttributes['secret']);

        if (isset($appAttributes['name'])) {
            $app->setName($appAttributes['name']);
        }

        if (isset($appAttributes['host'])) {
            $app->setHost($appAttributes['host']);
        }

        if (isset($appAttributes['path'])) {
            $app->setPath($appAttributes['path']);
        }

        $app
            ->enableClientMessages($appAttributes['enable_client_messages'])
            ->enableStatistics($appAttributes['enable_statistics'])
            ->setCapacity($appAttributes['capacity'] ?? null);

        return $app;
    }

    /**
     * @param $appId
     * @return App|null
     * @throws InvalidApp
     */
    public function findById($appId): ?App
    {
        $appAttributes = $this
            ->apps
            ->firstWhere('id', $appId);

        return $this->instanciate($appAttributes);
    }

    /**
     * @param  string  $appKey
     * @return App|null
     * @throws InvalidApp
     */
    public function findByKey(string $appKey): ?App
    {
        $appAttributes = $this
            ->apps
            ->firstWhere('key', $appKey);

        return $this->instanciate($appAttributes);
    }

    /**
     * @param  string  $appSecret
     * @return App|null
     * @throws InvalidApp
     */
    public function findBySecret(string $appSecret): ?App
    {
        $appAttributes = $this
            ->apps
            ->firstWhere('secret', $appSecret);

        return $this->instanciate($appAttributes);
    }
}
