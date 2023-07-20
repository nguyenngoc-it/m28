<?php

namespace Modules\Gobiz\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;

interface M4ApiInterface
{
    /**
     * Get balance, credit of an Account
     *
     * @param string $account
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getAccount(string $account);

    /**
     * Get balance, credit of list Account
     *
     * @param array $filter
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getAccounts(array $filter = []);

    /**
     * Create Account
     *
     * @param array $payload
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function createAccount(array $payload);

    /**
     * List transaction of an account
     *
     * @param string $account
     * @param array $filter
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function transactions(string $account, array $filter = []);

    /**
     * Create refund transaction
     *
     * @param string $account
     * @param array $payload
     * @param string|null $uniqueTrack
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function refund(string $account, array $payload, string $uniqueTrack = null);

    /**
     * Create collect transaction
     *
     * @param string $account
     * @param array $payload
     * @param string|null $uniqueTrack
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function collect(string $account, array $payload, string $uniqueTrack = null);

    /**
     * Create deposit transaction
     *
     * @param string $account
     * @param array $payload
     * @param string|null $uniqueTrack
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function deposit(string $account, array $payload, string $uniqueTrack = null);

    /**
     * Create withdraw transaction
     *
     * @param string $account
     * @param array $payload
     * @param string|null $uniqueTrack
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function withdraw(string $account, array $payload, string $uniqueTrack = null);
}
