<?php

use App\Models\Giftcode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeMediumInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $allGiftcodes = Giftcode::withTrashed()->get()->map->only(['code', 'id']);
        $uniqueGiftcodes = [];

        foreach ($allGiftcodes as $giftcode) {
            if (in_array($giftcode['code'], $uniqueGiftcodes)) {
                Giftcode::where('id', $giftcode['id'])->forceDelete();
            } else {
                $uniqueGiftcodes[] = $giftcode['code'];
            }
        }

        Schema::table('medium_info', function (Blueprint $table) {
            $table->string('code', 165)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('medium_info', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->string('code', 165)->nullable()->change();
        });
    }
}
