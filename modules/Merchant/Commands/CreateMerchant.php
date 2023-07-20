<?php

namespace Modules\Merchant\Commands;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use Illuminate\Support\Facades\DB;
use Modules\Merchant\Events\MerchantCreated;
use Modules\Merchant\Models\Merchant;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class CreateMerchant
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
     * @var User
     */
    protected $creator;

    /**
     * CreateMerchant constructor.
     * @param User $creator
     * @param array $input
     * @param Tenant|null $tenant
     */
    public function __construct(User $creator, array $input, Tenant $tenant = null)
    {
        $this->creator = $creator;
        $this->tenant  = $tenant;
        $this->input   = $input;
    }


    /**
     * @return array|Merchant|null
     */
    public function handle()
    {
        if ($this->tenant) {
            $tenant_id = $this->tenant->id;
        } else {
            $tenant_id = $this->creator->tenant_id;
        }
        $this->input['tenant_id'] = $tenant_id;

        $merchant = null;
        DB::beginTransaction();
        try {
            $merchant = Merchant::create($this->input);
            $this->createM4Account($merchant);

            DB::commit();
        } catch (RestApiException $exception) {
            DB::rollBack();

            $response = $exception->getResponse();
            return [
                'key' => $response->getData('title'),
                'message' => $response->getData('detail')
            ];
        }

        if ($merchant instanceof Merchant) {
            (new MerchantCreated($merchant, $this->creator))->queue();
        }

        return $merchant;
    }

    /**
     * @param Merchant $merchant
     * @return RestApiResponse
     * @throws RestApiException
     */
    protected function createM4Account(Merchant $merchant)
    {
        $tenant = $merchant->tenant;

        return $tenant->m4Merchant()->createAccount([
            'account' => $merchant->code,
            'name' => $merchant->name
        ]);
    }
}
