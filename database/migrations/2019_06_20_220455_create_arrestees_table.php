<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArresteesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('arrestees', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('organizationId');
			$table->string('personId');
            $table->string('name', 256);
            $table->string('dob', 256);
            $table->enum('gender', ['MALE', 'FEMALE'])->default('MALE');
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
        Schema::dropIfExists('arrestees');
    }
}

