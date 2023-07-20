<?php

namespace Modules\Tiki\Commands;

use App\Base\CommandBus;
use Gobiz\Log\LogService;
use Gobiz\Validation\ValidationException;
use Gobiz\Workflow\WorkflowException;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\Tiki\Services\Tiki;
use Modules\Store\Models\Store;
use Modules\Tiki\Jobs\SyncTikiOrderJob;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncQueueSubscription extends CommandBus
{
    /**
     * @var Store
     */
    protected $store;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SyncQueueSubscription constructor
     *
     * @param Store $store
     * @param User $creator
     */
    public function __construct(Store $store)
    {
        $this->store   = $store;
        $this->creator = Service::user()->getSystemUserDefault();
        $this->api     = Service::tiki()->api();

        $this->logger = LogService::logger('tiki-queue-subscriptions', [
            'context' => ['shop_id' => $store->marketplace_store_id],
        ]);
    }

    /**
     * @return Order
     * @throws ValidationException
     * @throws WorkflowException
     */
    public function handle()
    {
        // dd($this->store);
        // Lấy thông tin Sub
        $subscriptions = $this->store->getSetting('subscription');
        $accessToken   = $this->store->getSetting('token_client_credential');
        $queueCode     = $this->store->getSetting('queue_code');

        $datas = [];
        // dd($subscriptions);
        if (isset($subscriptions[0])) {
            $subscription = $subscriptions[0];

            $ackId = data_get($subscription, 'code');
            if ($ackId) {
                $paramsRequest = [
                    'access_token' => $accessToken,
                    'queue_code'   => $queueCode,
                    'ack_id'       => $ackId
                ];
                $dataPullEvents = $this->api->pullEvents($paramsRequest)->getData('events');
                $datas[] = $dataPullEvents;

                if (!empty($dataPullEvents)) {
                    foreach ($dataPullEvents as $dataPullEvent) {
                        $typeQueue = data_get($dataPullEvent, 'type');
                        $listType = [
                            Tiki::WEBHOOK_ORDER_STATUS_UPDATED,
                            Tiki::WEBHOOK_ORDER_STATUS_CREATED,
                        ];
                        if (in_array($typeQueue, $listType)) {
                            $orderCode = data_get($dataPullEvent, 'payload.order_code');
                            dispatch(new SyncTikiOrderJob($this->store, ['order_id' => $orderCode]));
                        }
                    }
                }
            }
        }
        $this->logger->info('data', $datas);
    }
}
