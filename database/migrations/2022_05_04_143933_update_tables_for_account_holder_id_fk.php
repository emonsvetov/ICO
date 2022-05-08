<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTablesForAccountHolderIdFk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //update "merchants"
        Schema::table('merchants', function (Blueprint $table) {
            // $table->foreignId('account_holder_id')->constrained()->onDelete('cascade'); //Later on we will need to constraint all the account_holder_id columns in respective tables. TODO
            $table->unsignedBigInteger('account_holder_id')->after('id');
            $table->index('account_holder_id');
        });
        //update "programs"
        Schema::table('programs', function (Blueprint $table) {
            // $table->foreignId('account_holder_id')->constrained()->onDelete('cascade'); //Later on we will need to constraint all the account_holder_id columns in respective tables. TODO
            $table->unsignedBigInteger('account_holder_id')->after('id');
            $table->index('account_holder_id');
        });
        //update "users"
        Schema::table('users', function (Blueprint $table) {
            // $table->foreignId('account_holder_id')->constrained()->onDelete('cascade'); //Later on we will need to constraint all the account_holder_id columns in respective tables. TODO
            $table->unsignedBigInteger('account_holder_id')->after('id');
            $table->index('account_holder_id');
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //update "merchants"
        Schema::table('merchants', function (Blueprint $table) {
            // $table->dropForeign('merchants_account_holder_id_foreign'); //Later on we will need to constraint all the account_holder_id columns in respective tables. TODO
            $table->dropColumn('account_holder_id');
        });        
        //update "programs"
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('account_holder_id');
        });
        //update "users"
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_holder_id');
        });
        
    }
}
