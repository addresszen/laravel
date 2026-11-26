# AddressZen Laravel Package

Set your AddressZen API key in `.env`, then inject the [PHP SDK](https://packagist.org/packages/addresszen/sdk) client into a controller, or call the facade. Laravel 12 or 13, PHP 8.2 or later.

```bash
composer require addresszen/laravel
```

Package discovery registers the service provider and the `AddressZen` facade.

```bash
# .env
ADDRESSZEN_API_KEY=ak_test
ADDRESSZEN_MANAGEMENT_KEY=uk_... # only for key, licensee and config operations
```

```php
use AddressZen\Client;
use AddressZen\Types\UsaVerifyMatch;

final class CheckoutController
{
    public function __construct(private readonly Client $client) {}

    public function isDeliverable(string $address): bool
    {
        return $this->client->addressVerify(['query' => $address])->result instanceof UsaVerifyMatch;
    }
}
```

```php
use AddressZen\Laravel\Facades\AddressZen;

$suggestions = AddressZen::findAddress(['query' => '1010 Cauthen Ln'])->result->hits;
```

Inject `AddressZen\Client`, or `AddressZen\Contracts\ClientContract` to swap in a test double. Each API operation is one method on the client and the facade, such as `AddressZen::addressVerify($body)`.

Requests go through the Guzzle that Laravel ships, found by `php-http/discovery`, or through `Psr\Http\Client\ClientInterface` when your application binds one. Publish the config file to change the base URI, the HTTP client binding or the headers sent with every request:

```bash
php artisan vendor:publish --tag=addresszen-config
```

| Option | Purpose |
| --- | --- |
| `api_key` | Required. Your API key or a licensee key, from `ADDRESSZEN_API_KEY` |
| `management_key` | Your Management Key for account operations, from `ADDRESSZEN_MANAGEMENT_KEY` |
| `base_uri` | Defaults to `https://api.addresszen.com/v1` |
| `http_client` | Container binding of a PSR-18 client to use instead of the default |
| `headers` | Headers sent with every request |

[Documentation](https://docs.addresszen.com/docs/sdks/php/laravel) covers configuration, testing with a mock client and the SDK itself.

## License

SEE LICENSE IN LICENSE
