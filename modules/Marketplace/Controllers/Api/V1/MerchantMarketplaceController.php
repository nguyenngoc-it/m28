<?php

namespace Modules\Marketplace\Controllers\Api\V1;

use App\Base\Controller;
use App\Base\Job;
use App\Base\Validator;
use Modules\Marketplace\Services\OAuthConnectable;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Store\Factories\MerchantStoreFactory;
use Modules\Store\Models\Store;
use Modules\Tenant\Models\Tenant;

class MerchantMarketplaceController extends Controller
{

    public function oauthUrl(string $code)
    {
        $tenant      = $this->user->tenant;
        $domain      = $this->request()->get('url');
        $warehouseId = $this->request()->get('warehouse_id');
        $merchantCode = $this->request()->get('merchant_code');

        $merchant = Merchant::where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();
        if (!$merchant){
            return $this->response()->error('INPUT_INVALID', ['merchant' => Validator::ERROR_NOT_EXIST]);
        }
        if (
            !($marketplace = Service::marketplace()->marketplace($code))
            || !$marketplace instanceof OAuthConnectable
        ) {
            return $this->response()->error('404', [], 404);
        }
        if (!filter_var($domain, FILTER_VALIDATE_URL)) {
            return $this->response()->error('INPUT_INVALID', ['url' => Validator::ERROR_INVALID]);
        }

        if (!in_array($warehouseId, $merchant->location->warehouses->pluck('id')->all())) {
            return $this->response()->error('INPUT_INVALID', ['warehouse_id' => Validator::ERROR_NOT_EXIST]);
        }

        $callbackUrl = url("oauth/marketplaces/$code/oauth-callback");
        $state       = Service::marketplace()->makeOAuthState($tenant, $merchant->id, $domain, $warehouseId);
        $url         = $marketplace->makeOAuthUrl($callbackUrl, $state);

        return $this->response()->success([
            'url' => $url,
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
            return redirect($tenant->redirectUrl(['error' => 'AUTHORIZATION_FAILED'], $domain));
        }

        $store = Store::query()->firstOrNew([
            'tenant_id' => $tenant->id,
            'marketplace_code' => $code,
            'marketplace_store_id' => $oauthResponse->storeId,
        ]);

        // Store đã được kết nối đến merchant khác
        if ($store->status == Store::STATUS_ACTIVE && $store->merchant_id && $store->merchant_id != $merchant->id) {
            return redirect($tenant->redirectUrl([
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
            return redirect($tenant->redirectUrl(['store_id' => $store->id, 'shop_exist' => true], $domain));
        }
        return redirect($tenant->redirectUrl(['store_id' => $store->id], $domain));
    }

}
