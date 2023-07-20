<?php

namespace Modules\Marketplace\Services;

use DateTimeInterface;

class OAuth2Token
{
    /**
     * @var string
     */
    public $accessToken;

    /**
     * @var string
     */
    public $refreshToken;

    /**
     * @var DateTimeInterface|null
     */
    public $accessTokenExpiredAt;

    /**
     * @var DateTimeInterface|null
     */
    public $refreshTokenExpiredAt;
}
