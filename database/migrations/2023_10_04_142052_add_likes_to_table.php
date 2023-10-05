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
            $table->json('like')->default(json_encode([]));
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
        Schema::table('SocialWallPost', function (Blueprint $table) {
            $table->dropColumn('like');
        });
    }
}
