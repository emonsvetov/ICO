<?php

use App\Models\ProgramList;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReportProgramStatusToProgramListTable extends Migration
{

    const REPORT_NAME = 'Program Status';
    const REPORT_URL = '/manager/report/program-status';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('program_list')->insert([
            ['name' => self::REPORT_NAME, 'url' => self::REPORT_URL],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('program_list')->where('name', self::REPORT_NAME)->delete();
    }
}
