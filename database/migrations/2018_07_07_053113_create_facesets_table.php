<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFacesetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facesets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('facesetToken');
            $table->tinyInteger('organizationId');
            $table->enum('gender', ['MALE', 'FEMALE'])->default('MALE');
			$table->integer('faces')->default(0);
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
        Schema::dropIfExists('facesets');
    }
}
