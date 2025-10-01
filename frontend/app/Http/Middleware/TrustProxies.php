<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    public function __construct()
    {
        $configured = config('app.trusted_proxies');

        if ($configured === '*' || $configured === '0.0.0.0/0') {
            $this->proxies = '*';
            return;
        }

        if (is_string($configured) && trim($configured) !== '') {
            $this->proxies = array_map('trim', explode(',', $configured));
        }
    }
}
