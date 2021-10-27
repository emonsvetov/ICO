<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_status_id')->nullable();; //FK to user status table 
            //$table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

			//$table->unsignedBigInteger('role_id'); //dropdown pending
			$table->string('first_name');
			$table->string('last_name');
			//$table->string('email',320)->nullable();
			$table->string('phone',50)->nullable();
			$table->string('award_level')->nullable();
			$table->date('work_anniversary')->nullable();
			$table->date('dob')->nullable();
			$table->string('username')->nullable();
            $table->integer('employee_number')->nullable();
			$table->string('division')->nullable();
			$table->string('office_location')->nullable();
			$table->string('position_title')->nullable();
			$table->string('position_grade_level')->nullable();
			$table->integer('supervisor_employee_number')->nullable();
			$table->integer('organizational_head_employee_number')->nullable();
			
            $table->datetime('deactivated')->nullable();
            $table->datetime('activated')->nullable();
            $table->datetime('state_updated')->nullable();
            $table->string('last_location')->nullable();
            $table->integer('update_id')->nullable();

            $table->rememberToken();
            $table->timestamps();

            $table->index(['id','organization_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
