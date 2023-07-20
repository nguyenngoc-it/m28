<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Store\Models\Store;

class CreateInvalidOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invalid_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->string('source')->comment('INTERNAL_API, SHOPEE, ...');
            $table->string('code');
            $table->text('payload')->comment('Thông tin đơn');
            $table->string('error_code')->comment('Mã lỗi');
            $table->text('errors')->comment('Danh sách lỗi chi tiết');
            $table->integer('creator_id')->index();
            $table->timestamps();

            $table->unique(['code', 'source', 'tenant_id'], 'code');
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
