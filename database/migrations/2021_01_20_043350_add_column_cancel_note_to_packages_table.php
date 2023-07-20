<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnCancelNoteToPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('cancel_note')->nullable()->comment('Ghi chú khi kiện');
            $table->integer('canceler_id')->nullable()->index()->comment('Người hủy gói');
            $table->dateTime('reimported_at')->nullable()->comment('Ngày tái nhập');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['cancel_note', 'reimported_at']);
        });
    }
}
