<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnActionToDocumentSupplierTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('document_supplier_transactions', function (Blueprint $table) {
            $table->string('action')->nullable()->comment('Loại giao dịch nạp tiền hay trừ tiền');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('document_supplier_transactions', function (Blueprint $table) {
            $table->dropColumn(['action']);
        });
    }
}
