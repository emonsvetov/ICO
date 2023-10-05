<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLikesToTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('social_wall_posts', function (Blueprint $table) {
            $table->json('like');
            $table->integer('likesCount')->default(0);
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
            $table->dropColumn('like');
            $table->dropColumn('likesCount');
        });
    }
}
