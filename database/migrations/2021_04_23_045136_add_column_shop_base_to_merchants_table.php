<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnShopBaseToMerchantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Modules\User\Models\User::create([
            'username' => \Modules\User\Models\User::USERNAME_SHOP_BASE,
            'name' => 'Shop Base',
            'email' => 'shopbase@gmail.com',
            'tenant_id' => 0,
        ]);

        Schema::create('shop_bases', function (Blueprint $table) {
            $table->id();
            $table->integer('merchant_id')->index();
            $table->integer('order_id')->index()->default(0)->comment('ID don ben M28 neu tao thanh cong');
            $table->boolean('status')->index()->default(false)->comment('trạng thái tạo đơn thành công hay không');
            $table->text('data')->comment('Du lieu shopbase');
            $table->text('errors')->nullable();
            $table->timestamps();
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->string('shop_base_account')->nullable()->comment('Tài khoản shop base');
            $table->string('shop_base_app_key')->nullable()->comment('Private app key shop base');
            $table->string('shop_base_password')->nullable()->comment('Private app password shop base');
            $table->string('shop_base_secret')->nullable()->comment('Shared Secret verify web hook shop base');
            $table->string('shop_base_webhook_id')->nullable()->comment('WebHook ID shop base');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_bases');

        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['shop_base_account', 'shop_base_app_key', 'shop_base_password', 'shop_base_secret', 'shop_base_webhook_id']);
        });
    }
}
