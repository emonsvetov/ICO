<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProgramIdToCsvImports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('csv_imports', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable()->after('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('csv_imports', function (Blueprint $table) {
            $table->dropColumn(['program_id']);
        });
    }
}
