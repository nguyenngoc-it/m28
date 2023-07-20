<?php

namespace Modules\Topship\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;

interface TopshipApiInterface
{
    /**
     * Get current account info
     *
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function me();

    /**
     * Get shipping services
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getShippingServices(array $input);

    /**
     * Create and confirm order
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function createAndConfirmOrder(array $input);

    /**
     * Get fulfillment
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getFulfillment(array $input);

    /**
     * Create webhook
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function createWebhook(array $input);

    /**
     * Delete webhook
     *
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getWebhooks();

    /**
     * Delete webhook
     *
     * @param array $input
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function deleteWebhook(array $input);
}
