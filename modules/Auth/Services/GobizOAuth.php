<?php

namespace Modules\Auth\Services;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class GobizOAuth extends AbstractProvider
{
    /**
     * Indicates if the session state should be utilized.
     *
     * @var bool
     */
    protected $stateless = true;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->makeUrl('oauth/authorize'), $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->makeUrl('oauth/token');
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), ['grant_type' => 'authorization_code']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $url = $this->makeUrl('oauth/userinfo').'?access_token='.$token;
        $response = $this->getHttpClient()->get($url);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => $user['username'],
            'name' => $user['fullname'],
            'email' => $user['email'],
            'avatar' => $user['avatar'],
        ]);
    }

    /**
     * @param string $path
     * @return string
     */
    protected function makeUrl($path)
    {
        return config('gobiz.m10.url').'/'.$path;
    }
}