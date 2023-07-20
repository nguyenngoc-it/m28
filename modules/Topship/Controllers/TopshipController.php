<?php

namespace Modules\Topship\Controllers;

use App\Base\Controller;
use Gobiz\Log\LogService;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Topship\Jobs\SyncTopshipFreightBillJob;

class TopshipController extends Controller
{
    public function webhook()
    {
        $key = $this->request()->get('key');
        $event = ($event = $this->request()->getContent()) ? json_decode($event, true) : [];
        $logger = LogService::logger('topship-events');

        $logger->debug('event', $event);

        if (!$this->authenticateWebhook($key)) {
            $logger->error('UNAUTHENTICATED', ['key' => $key]);
            return $this->response()->error(403, [], 403);
        }

        foreach ($this->request()->get('changes', []) as $change) {
            $this->processEvent($change);
        }

        return 'ok';
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function authenticateWebhook($key)
    {
        if (!$key) {
            return false;
        }

        return ShippingPartner::query()
            ->where('provider', ShippingPartner::PROVIDER_TOPSHIP)
            ->get()
            ->filter(function (ShippingPartner $shippingPartner) use ($key) {
                $token = $shippingPartner->getSetting(ShippingPartner::TOPSHIP_TOKEN);

                return hash('sha256', $token) === $key;
            })
            ->isNotEmpty();
    }

    /**
     * @param array $event
     */
    protected function processEvent(array $event)
    {
        switch ($event['entity']) {
            case 'fulfillment': {
                $fulfillment = $event['latest']['fulfillment'];

                if (Service::topship()->mapFreightBillStatus($fulfillment['shipping_state'])) {
                    $this->dispatch(new SyncTopshipFreightBillJob($fulfillment));
                }

                return;
            }
        }
    }
}
