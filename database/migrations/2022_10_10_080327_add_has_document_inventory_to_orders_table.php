<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHasDocumentInventoryToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('has_document_inventory')->default(false);
        });

        $documentFreightBillInventories = \Modules\Document\Models\DocumentFreightBillInventory::query()
            ->select(['order_id'])
            ->groupBy('order_id')->get();
        foreach ($documentFreightBillInventories as $documentFreightBillInventory) {
            dispatch(new \Modules\Order\Jobs\CalculateHasDocumentInventoryJob($documentFreightBillInventory->order_id));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('has_document_inventory');
        });
    }
}
