<?php

namespace Modules\Marketplace\Controllers;

use App\Base\Controller;
use App\Base\Validator;
use Illuminate\Http\JsonResponse;
use Modules\Marketplace\Services\OAuthConnectable;
use Modules\Service;

class MerchantMarketplaceController extends Controller
{
    /**
     * @param string $code
     * @return JsonResponse
     */
    public function oauthUrl(string $code)
    {
        $tenant      = $this->user->tenant;
        $merchant    = $this->user->merchant;
        $domain      = $this->request()->get('domain') ?: $tenant->merchant_domains[0];
        $warehouseId = $this->request()->get('warehouse_id');

        if (
            !($marketplace = Service::marketplace()->marketplace($code))
            || !$marketplace instanceof OAuthConnectable
        ) {
            return $this->response()->error('404', [], 404);
        }
        if (!in_array($domain, $tenant->merchant_domains, true)) {
            return $this->response()->error('INPUT_INVALID', ['domain' => Validator::ERROR_NOT_EXIST]);
        }

        if (!in_array($warehouseId, $merchant->location->warehouses->pluck('id')->all())) {
            return $this->response()->error('INPUT_INVALID', ['warehouse_id' => Validator::ERROR_NOT_EXIST]);
        }

        $callbackUrl = url("marketplaces/$code/oauth-callback");
        $state       = Service::marketplace()->makeOAuthState($tenant, $merchant->id, $domain, $warehouseId);

        return $this->response()->success([
            'url' => $marketplace->makeOAuthUrl($callbackUrl, $state),
        ]);
    }
}
