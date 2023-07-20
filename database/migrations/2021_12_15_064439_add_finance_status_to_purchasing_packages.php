<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\PurchasingPackage\Models\PurchasingPackage;
class AddFinanceStatusToPurchasingPackages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchasing_packages', function (Blueprint $table) {
            $table->string('finance_status')->default(PurchasingPackage::FINANCE_STATUS_UNPAID)->comment('Trạng thái tài chính');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchasing_packages', function (Blueprint $table) {
            $table->dropColumn('finance_status');
        });
    }
}
