<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('m4_tenant_supplier')->comment('M4 tenant lưu ví công nợ với supplier');
        });

        Schema::create('supplier_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tenant_id');
            $table->unsignedInteger('supplier_id');
            $table->string('type', 255)->comment('Loại giao dịch: IMPORT | EXPORT | IMPORT_BY_RETURN | PAYMENT');
            $table->string('object_type', 255)->nullable();
            $table->unsignedInteger('object_id')->nullable();
            $table->double('amount', 20, 3);
            $table->json('metadata')->nullable();
            $table->string('inventory_trans_id', 255)->nullable()->comment('Mã gd trên M28 ví công nợ tồn kho');
            $table->string('inventory_m4_trans_id', 255)->nullable()->comment('Mã gd trên M4 ví công nợ tồn kho');
            $table->string('sold_trans_id', 255)->nullable()->comment('Mã gd trên M28 ví công nợ đã bán');
            $table->string('sold_m4_trans_id', 255)->nullable()->comment('Mã gd trên M4 ví công nợ đã bán');
            $table->nullableTimestamps();

            $table->unique(['object_id', 'object_type', 'type', 'supplier_id'], 'unique_type_supplier');
            $table->unique('inventory_trans_id', 'inventory_trans_id');
            $table->unique('inventory_m4_trans_id', 'inventory_m4_trans_id');
            $table->unique('sold_trans_id', 'sold_trans_id');
            $table->unique('sold_m4_trans_id', 'sold_m4_trans_id');
            $table->index(['supplier_id', 'type'], 'supplier_id_type');
            $table->index(['created_at'], 'created_at');
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
