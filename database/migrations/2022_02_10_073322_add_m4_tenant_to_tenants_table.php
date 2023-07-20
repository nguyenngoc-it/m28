<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddM4TenantToTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('m4_tenant_merchant')->comment('Id tenant code m4 lưu ví công nợ với seller');
            $table->string('m4_tenant_shipping_partner')->comment('Id tenant code m4 lưu ví công nợ với DVVC');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['m4_tenant_merchant', 'm4_tenant_shipping_partner']);
        });
    }
}
