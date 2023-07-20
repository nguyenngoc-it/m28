<?php /** @noinspection PhpMissingReturnTypeInspection */

namespace Modules\Marketplace\Controllers;

use App\Base\Controller;
use App\Base\Job;
use Illuminate\Support\Arr;
use Modules\Service;
use Modules\Marketplace\Services\OAuthConnectable;
use Modules\Marketplace\Validators\MakeOAuthUrlValidator;
use Modules\Store\Factories\MerchantStoreFactory;
use Modules\Store\Models\Store;
use Modules\Tenant\Models\Tenant;

class MarketplaceController extends Controller
{
    public function index()
    {
        return $this->response()->success([
            'marketplaces' => Service::marketplace()->marketplaces(),
        ]);
    }

    public function oauthUrl($code)
    {
        $tenant = $this->getAuthUser()->tenant;
        $inputs = [
            'tenant_id' => $tenant->id,
            'merchant_id' => $this->request()->get('merchant_id'),
            'marketplace_code' => $code,
            'warehouse_id' => $this->request()->get('warehouse_id'),
            'domain' => $this->request()->get('domain'),
        ];

        $validator = new MakeOAuthUrlValidator($inputs);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $marketplace = $validator->getMarketplace();
        $merchant    = $validator->getMerchant();
        $callbackUrl = url("marketplaces/$code/oauth-callback");
        $state       = Service::marketplace()->makeOAuthState(
            $merchant->tenant,
            $merchant->id,
            $validator->getDomain(),
            Arr::get($inputs, 'warehouse_id')
        );

        return $this->response()->success([
            'url' => $marketplace->makeOAuthUrl($callbackUrl, $state),
        ]);
    }

    public function oauthCallback($code)
    {
        /**
         * @var Store $store
         * @var Tenant $tenant
         */
        if (
            !($marketplace = Service::marketplace()->marketplace($code))
            || !$marketplace instanceof OAuthConnectable
        ) {
            return $this->response()->error('404', [], 404);
        }

        $oauthResponse = $marketplace->handleOAuthCallback($this->request());
        $state         = Service::marketplace()->parseOAuthState($oauthResponse->state);
        $merchant      = $state['merchant'];
        $domain        = $state['domain'];
        $warehouse     = $state['warehouse'];

        $tenant = $merchant->tenant;

        if ($oauthResponse->error) {
            return redirect($tenant->url('stores/connect-callback', ['error' => 'AUTHORIZATION_FAILED'], $domain));
        }

        $store = Store::query()->firstOrNew([
            'tenant_id' => $tenant->id,
            'marketplace_code' => $code,
            'marketplace_store_id' => $oauthResponse->storeId,
        ]);

        // Store đã được kết nối đến merchant khác
        if ($store->status == Store::STATUS_ACTIVE && $store->merchant_id && $store->merchant_id != $merchant->id) {
            return redirect($tenant->url('stores/connect-callback', [
                'error' => 'STORE_CONNECTED',
                'marketplace_store_id' => $store->marketplace_store_id,
                'merchant_id' => $store->merchant->id,
                'merchant_name' => $store->merchant->name,
            ], $domain));
        }

        $oldStatus           = $store->status;
        $store->warehouse_id = $warehouse->id;
        $store->merchant_id  = $merchant->id;
        $store->settings     = array_merge($store->settings ?: [], $oauthResponse->settings ?: []);
        $store->name         = $oauthResponse->storeName;
        $store->status       = Store::STATUS_ACTIVE;
        $store->save();

        $merchantStoreFactory = new MerchantStoreFactory($store);
        $jober                = $merchantStoreFactory->makeSyncProductJober();

        if ($jober instanceof Job) {
            dispatch($jober);
        }

        if ($oldStatus && $oldStatus == Store::STATUS_ACTIVE && $store->merchant_id == $merchant->id) {
            return redirect($tenant->url('stores/connect-callback', ['store_id' => $store->id, 'shop_exist' => true], $domain));
        }

        return redirect($tenant->url('stores/connect-callback', ['store_id' => $store->id], $domain));
    }
}
