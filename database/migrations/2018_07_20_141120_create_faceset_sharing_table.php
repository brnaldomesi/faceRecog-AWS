<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFacesetSharingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faceset_sharing', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('organization_owner');
			$table->tinyInteger('organization_requestor');
            $table->dateTime('date_sent');
			$table->string('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('faceset_sharing');
    }
}
