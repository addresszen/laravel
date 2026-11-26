<?php

declare(strict_types=1);

namespace AddressZen\Laravel;

use AddressZen\Client;
use AddressZen\Contracts\ClientContract;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Binds an `AddressZen\Client` built from `config/addresszen.php`,
 * so it can be constructor-injected as `Client` or `ClientContract`.
 * Package discovery registers the provider and the `AddressZen` facade.
 */
final class AddressZenServiceProvider extends ServiceProvider
{
    public const CONFIG = 'addresszen';

    /**
     * The `vendor:publish` tag that copies the config file into the application.
     */
    public const CONFIG_TAG = 'addresszen-config';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/addresszen.php', self::CONFIG);

        $this->app->singleton(Client::class, fn (Container $app): Client => self::clientFor($app));
        $this->app->alias(Client::class, ClientContract::class);
        $this->app->alias(Client::class, self::CONFIG);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/addresszen.php' => $this->app->configPath('addresszen.php'),
            ], self::CONFIG_TAG);
        }
    }

    private static function clientFor(Container $app): Client
    {
        $repository = $app->make(Repository::class);
        $config = $repository->get(self::CONFIG);
        $config = is_array($config) ? $config : [];

        $apiKey = $config['api_key'] ?? null;

        if (! is_string($apiKey) || $apiKey === '') {
            throw new InvalidArgumentException('AddressZen needs an API key. Set ADDRESSZEN_API_KEY in .env or api_key in config/addresszen.php.');
        }

        $httpClient = $config['http_client'] ?? null;
        $httpClient = is_string($httpClient) && $httpClient !== ''
            ? $app->make($httpClient)
            : self::bound($app, ClientInterface::class);

        if ($httpClient !== null && ! $httpClient instanceof ClientInterface) {
            throw new InvalidArgumentException('addresszen.http_client must resolve to a Psr\Http\Client\ClientInterface.');
        }

        $requestFactory = self::bound($app, RequestFactoryInterface::class);
        $streamFactory = self::bound($app, StreamFactoryInterface::class);

        return ClientFactory::create(
            $apiKey,
            self::optionalString($config, 'management_key'),
            self::optionalString($config, 'base_uri'),
            self::headersFrom($config['headers'] ?? []),
            $httpClient,
            $requestFactory instanceof RequestFactoryInterface ? $requestFactory : null,
            $streamFactory instanceof StreamFactoryInterface ? $streamFactory : null,
        );
    }

    /**
     * The service bound under `$abstract`, or null when the application has none.
     */
    private static function bound(Container $app, string $abstract): ?object
    {
        if (! $app->bound($abstract)) {
            return null;
        }

        $service = $app->make($abstract);

        return is_object($service) ? $service : null;
    }

    /**
     * The `headers` option as the SDK takes it: a name to a scalar value.
     * Anything else in the array is ignored.
     *
     * @return array<string, scalar|null>
     */
    private static function headersFrom(mixed $headers): array
    {
        $result = [];

        if (! is_array($headers)) {
            return $result;
        }

        foreach ($headers as $name => $value) {
            if (is_string($name) && (is_scalar($value) || $value === null)) {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    /**
     * @param  array<mixed>  $config
     */
    private static function optionalString(array $config, string $key): ?string
    {
        $value = $config[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
