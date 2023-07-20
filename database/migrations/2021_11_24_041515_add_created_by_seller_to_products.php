<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedBySellerToProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_services', function (Blueprint $table) {
            $table->increments('id');
            $table->string('service_code', 255)->nullable()->index()->comment('Mã dịch vụ');
            $table->unsignedInteger('product_id')->default(0)->index()->comment('Mã dịch vụ');
            $table->timestamps();
            $table->unique(['service_code', 'product_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('merchant_id')->default(0)->after('creator_id')->index()
                ->comment('merchant tạo sản phẩm, mặc định = 0 nếu sản phẩm hệ thống');
            $table->unique(['tenant_id', 'code', 'merchant_id']);
        });

        Schema::table('skus', function (Blueprint $table) {
            $table->unsignedInteger('merchant_id')->after('creator_id')->default(0)->index();
            $table->double('length', 9, 3)->after('weight');
            $table->double('width', 9, 3)->after('length');
            $table->double('height', 9, 3)->after('width');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
}
