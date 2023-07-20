<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Tenant\Models\TenantSetting;

class SeedTenantSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ([TenantSetting::PUBLISH_EVENT_ORDER_CREATE, TenantSetting::PUBLISH_EVENT_ORDER_CHANGE_AMOUNT] as $key) {
            TenantSetting::updateOrCreate([
                'tenant_id' => 1,
                'key' => $key
            ], [
                'value' => 1
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
