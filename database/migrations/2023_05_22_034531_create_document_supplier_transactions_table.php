<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentSupplierTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_supplier_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('document_id')->index();
            $table->unsignedInteger('supplier_id')->index();
            $table->double('amount', 15, 3);
            $table->string('transaction_code');
            $table->dateTime('payment_time')->nullable()->comment('Thời gian thanh toán');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_settings');
    }
}
