<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialWallPosts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_wall_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('social_wall_post_type_id')->nullable();
            $table->unsignedInteger('social_wall_post_id')
                ->comment('The parent social wall post that this post belongs to')
                ->nullable();
            $table->unsignedBigInteger('event_xml_data_id')->nullable();
            $table->unsignedBigInteger('program_account_holder_id')
                ->comment('The program the post was generated from')
                ->nullable();
            $table->unsignedBigInteger('awarder_program_id')
                ->nullable()
                ->default(0);
            $table->unsignedBigInteger('sender_user_account_holder_id');
            $table->unsignedBigInteger('receiver_user_account_holder_id');
            $table->mediumText('comment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_wall_posts');
    }
}
