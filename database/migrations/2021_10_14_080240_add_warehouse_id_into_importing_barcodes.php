<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWarehouseIdIntoImportingBarcodes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importing_barcodes', function (Blueprint $table) {
            $table->json('snapshot_skus')->after('object_id')->nullable()->comment('Thông tin skus được quét vào, phục vụ việc hiển thị ở chi tiết chứng từ');
            $table->string('imported_type')->after('snapshot_skus')->nullable()->comment('Loại chứng từ nhập: return_goods');
            $table->unsignedInteger('freight_bill_id')->after('document_id')->default(0)->comment('Nếu quét vào 1 mã vận đơn cần lưu lại quan hệ để sau đối chiếu cho nhanh');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('importing_barcodes', function (Blueprint $table) {
            //
        });
    }
}
