<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebhookToTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->integer('webhook_id')->after('client_secret')->nullable()->comment('Webhook id đồng bộ từ webhook service');
            $table->string('webhook_url')->after('webhook_id')->nullable()->comment('Webhook url đồng bộ từ webhook service');
            $table->string('webhook_secret')->after('webhook_url')->nullable()->comment('Webhook secret đồng bộ từ webhook service');
        });
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
