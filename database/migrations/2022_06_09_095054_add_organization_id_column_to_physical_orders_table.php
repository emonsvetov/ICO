<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrganizationIdColumnToPhysicalOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('physical_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_id')->after('id');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('physical_orders', function (Blueprint $table) {
            $table->dropColumn('organization_id');
            $table->dropSoftDeletes();
        });
    }
}
