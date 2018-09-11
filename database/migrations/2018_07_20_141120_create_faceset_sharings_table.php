<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFacesetSharingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faceset_sharings', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('organization_owner');
			$table->tinyInteger('organization_requestor');
            $table->enum('status', ['ACTIVE', 'PENDING', 'DECLINED'])->default('PENDING');
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
        Schema::dropIfExists('faceset_sharings');
    }
}
