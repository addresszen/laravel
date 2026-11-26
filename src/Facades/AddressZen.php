<?php

declare(strict_types=1);

namespace AddressZen\Laravel\Facades;

use AddressZen\Client;
use Illuminate\Support\Facades\Facade;

/**
 * Static access to the bound `AddressZen\Client`: every client method,
 * such as `AddressZen::addressVerify(['query' => '1010 Cauthen Ln, Alamogordo, NM 88310'])`.
 *
 * @mixin Client
 *
 * @see Client
 */
final class AddressZen extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
