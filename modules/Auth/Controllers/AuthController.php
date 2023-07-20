<?php

namespace Modules\Auth\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Http\Redirector;
use Modules\Auth\Services\Permission;
use Modules\Location\Models\Location;
use Modules\Merchant\Commands\CreateMerchant;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantSetting;
use Modules\User\Models\User;

class AuthController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function login()
    {
        if (!$domain = $this->request()->get('domain')) {
            return $this->response()->error('DOMAIN_REQUIRED');
        }

        if (!$tenant = Service::tenant()->findByDomain($domain)) {
            return $this->response()->error('TENANT_NOT_FOUND');
        }

        $ref   = $this->request()->get('ref', '');
        $state = Service::auth()->makeAuthState($tenant, $domain, $ref);
        $url   = Service::auth()->oauth($tenant)->with(['state' => $state])->redirect()->getTargetUrl();

        return $this->response()->success(['url' => $url]);
    }

    /**
     * @param User $user
     * @return Merchant
     */
    protected function createMerchant(User $user)
    {
        $location = Location::query()->where('code', Location::COUNTRY_VIETNAM)->first();

        return (new CreateMerchant($user, [
            'user_id' => $user->id,
            'location_id' => $location->id,
            'username' => $user->username,
            'code' => $user->username,
            'name' => $user->name,
        ]))->handle();
    }

    /**
     * @return RedirectResponse|Redirector
     */
    public function loginCallback()
    {
        /**
         * @var Tenant $tenant
         */
        $state             = (string)$this->request()->get('state');
        $result            = Service::auth()->parseAuthState($state);
        $tenant            = $result['tenant'];
        $domain            = $result['domain'];
        $ref               = $result['ref'];
        $authenticatedUser = Service::auth()->oauth($tenant)->user();

        // Merchant portal
        if (in_array($domain, $tenant->merchant_domains ?: [])) {
            $merchant = $tenant->merchants()->firstWhere('username', $authenticatedUser->getNickname());

            if (!$merchant) {
                if (strtolower($ref) != 'ubox') {
                    return redirect($tenant->url('login/callback', ['error' => 'MERCHANT_NOT_FOUND'], $domain));
                }

                $user     = Service::auth()->saveUser($tenant, $authenticatedUser);
                $merchant = $this->createMerchant($user);

                if (!$merchant instanceof Merchant) {
                    return redirect($tenant->url('login/callback', ['error' => $merchant], $domain));
                }
            } else {
                $user = Service::auth()->saveUser($tenant, $authenticatedUser);
            }

            $merchant->update(['user_id' => $user->id]);

            // Admin portal
        } else {
            $user = Service::auth()->saveUser($tenant, $authenticatedUser);
        }

        $token = Auth::login($user);
        $url   = $tenant->url('login/callback', compact('token'), $domain);

        return redirect($url);
    }

    /**
     * @return JsonResponse
     */
    public function user()
    {
        /** @var User $user */
        $user           = Auth::user();
        $tenantSettings = $user->tenant->settings()->whereIn('key', [
            'COUNTRY', 'CURRENCY_FORMAT', 'CURRENCY_PRECISION',
            'CURRENCY_THOUSANDS_SEPARATOR', 'CURRENCY_DECIMAL_SEPARATOR',
            TenantSetting::CARE_SOFT_DOMAIN, TenantSetting::CARE_SOFT_DOMAIN_ID
        ])->get();

        // Enable/disable tính năng trên menu của seller theo từng tenant
        $allowedModules = $user->tenant->getSetting(TenantSetting::ALLOWED_MODULES);

        $tenant = $user->tenant->only(['id', 'code']);

        if (!$this->user->can(Permission::OPERATION_VIEW_ALL_PRODUCT)) {
            $suppliers = $user->suppliers->toArray();
        } else {
            $suppliers = $this->user->tenant->suppliers->toArray();
        }

        return $this->response()->success([
            'user' => $user,
            'merchant' => $user->merchant,
            'tenant' => $tenant,
            'tenant_settings' => $tenantSettings,
            'allowed_modules' => $allowedModules,
            'suppliers' => $suppliers,
        ]);
    }
}
