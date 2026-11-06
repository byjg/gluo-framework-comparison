<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthJWT as ShieldAuthJWT;

/**
 * Shield's JWT authenticator. The HS256 secret comes from .env:
 * authjwt.keys.default.0.secret
 */
class AuthJWT extends ShieldAuthJWT
{
    public array $defaultClaims = [
        'iss' => 'http://localhost:9084',
    ];
}
