<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCsvImportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('csv_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('csv_import_type_id');
            $table->string('name');
            $table->string('path');
            $table->string('size')->nullable()->default(0);
            $table->integer('rowcount')->nullable()->default(0);
            $table->boolean('is_processed')->default(0);
            $table->boolean('is_imported')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['id','organization_id','csv_import_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('csv_imports');
    }
}
