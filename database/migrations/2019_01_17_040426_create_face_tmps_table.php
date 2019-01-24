<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFaceTmpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('face_tmps', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('organizationId');
            $table->string('image_url');
            $table->string('identifiers');
            $table->enum('gender', ['MALE', 'FEMALE'])->default('MALE');;

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
        Schema::dropIfExists('faces_tmp');
    }
}
