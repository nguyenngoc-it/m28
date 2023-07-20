<?php

namespace Modules\Merchant\Commands;

use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\User\Models\User;
use Modules\Service;
use Modules\Tenant\Models\Tenant;
use Gobiz\Log\LogService;
use Psr\Log\LoggerInterface;

class RegisterMerchant
{
    /**
     * @var array
     */
    protected $input;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var Location
     */
    protected $location;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * RegisterMerchant constructor.
     * @param Tenant $tenant
     * @param Location $location
     * @param array $input
     */
    public function __construct(User $user, Location $location, array $input, Tenant $tenant = null)
    {
        $this->user     = $user;
        $this->tenant   = $tenant == null ? $user->tenant : $tenant;
        $this->location = $location;
        $this->input    = $input;
        $this->logger   = LogService::logger('register_merchant');
    }


    /**
     * @return array|Merchant
     */
    public function handle()
    {
        $phone = trim($this->input['phone']);
        $username = trim($this->input['username']);

        // đăng ký tài khoản M10
        $response = $this->tenant->m10()->register([
            'username' => $username,
            'password' => $this->input['password'],
            'confirmPassword' => $this->input['re_password'],
            'email' => $this->input['email'],
            'fullname' => $username,
            'nickname' => $username,
            'phone' => $phone
        ]);

        if (empty($response->getData('username'))) {
            $error      = @json_decode($response->getBody(), true);
            $violations = Arr::get($error, 'violations');

            $key     = Arr::get($error, 'title');
            $key     = strtolower(trim($key));
            $key     = str_replace(" ", "_", $key);
            $message = Arr::get($error, 'detail');

            if (!empty($violations)) {
                foreach ($violations as $violation) {
                    $key     = $key . '_' . $violation['field'];
                    $message = $violation['message'];
                }
            }

            return [
                'key' => $key,
                'message' => $message
            ];
        }

        /** @var Merchant $merchant */
        // $user            = Service::user()->getSystemUserDefault();
        // $user->tenant_id = $this->tenant->id;

        $merchant = (new CreateMerchant($this->user, [
            'location_id'          => $this->location->id,
            'username'             => $username,
            'code'                 => $username,
            'name'                 => $username,
            'creator_id'           => Arr::get($this->input, 'creator_id', 0),
            'free_days_of_storage' => $this->input['free_days_of_storage'],
            'ref'                  => trim(Arr::get($this->input, 'ref', '')),
            'phone' => $phone
        ], $this->tenant))->handle();

        return $merchant;
    }
}
