<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Merchant\Models\Merchant;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Gobiz\Support\RestApiResponse;

class CreateAccountM4Seeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            $merchants = $tenant->merchants;
            foreach ($merchants as $merchant) {
                try {
                    $response = $this->createM4Account($tenant, $merchant);
                    print_r($response->getBody());
                } catch (RestApiResponse $exception) {
                    print_r($exception->getMessage());
                }
            }
        }
    }

    /**
     * @param Tenant $tenant
     * @param Merchant $merchant
     * @return RestApiResponse
     * @throws \Gobiz\Support\RestApiException
     */
    protected function createM4Account(Tenant $tenant ,Merchant $merchant)
    {
        return $tenant->m4Merchant()->createAccount([
            'account' => $merchant->code,
            'name' => $merchant->name
        ]);
    }
}
