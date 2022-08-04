<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeSocialWallPosts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('social_wall_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_id');
            $table->dropColumn('program_account_holder_id');
            $table->unsignedBigInteger('program_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('social_wall_posts', function (Blueprint $table) {
            $table->dropColumn('organization_id');
            $table->dropColumn('program_id');
            $table->unsignedBigInteger('program_account_holder_id')
                ->comment('The program the post was generated from')
                ->nullable();
        });
    }
}
