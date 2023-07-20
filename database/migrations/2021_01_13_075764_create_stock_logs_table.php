<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('stock_id')->index();
            $table->string('action');
            $table->integer('quantity')->comment('Số lượng thay đổi');
            $table->string('object_type')->nullable()->comment('Đối tượng liên quan đến việc thay đổi stock');
            $table->string('object_id')->nullable()->comment('ID đối tượng liên quan đến việc thay đổi stock');
            $table->text('payload')->nullable()->comment('Thông tin chi tiết');
            $table->integer('creator_id')->index();
            $table->timestamps();

            $table->index(['object_id', 'object_type']);
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
