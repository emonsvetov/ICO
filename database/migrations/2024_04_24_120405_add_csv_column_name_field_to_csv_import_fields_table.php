<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCsvColumnNameFieldToCsvImportFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('csv_import_fields', function (Blueprint $table) {
            $table->string('name', 165)->change();
            $table->string('csv_column_name', 165)->after('rule');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('csv_import_fields', function (Blueprint $table) {
            $table->longText('name')->change();
            $table->dropColumn('csv_column_name');
        });
    }
}
