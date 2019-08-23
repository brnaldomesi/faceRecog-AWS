<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCaseMatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('case_matches', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('case_id');
			$table->integer('source_imageId');
			$table->integer('match_imageId');
			$table->integer('similarity');
			$table->datetime('searchedOn');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('case_matches');
    }
}
