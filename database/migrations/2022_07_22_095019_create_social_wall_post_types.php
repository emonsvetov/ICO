<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSocialWallPostTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_wall_post_types', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->timestamps();
        });

        DB::table('social_wall_post_types')->insert([
            'type' => 'Event',
            'created_at' => now(),
            'updated_at' => null,
        ]);
        DB::table('social_wall_post_types')->insert([
            'type' => 'Comment',
            'created_at' => now(),
            'updated_at' => null,
        ]);
        DB::table('social_wall_post_types')->insert([
            'type' => 'Message',
            'created_at' => now(),
            'updated_at' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_wall_post_types');
    }
}
