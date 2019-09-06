<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreateCasesTable extends Migration
{
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('organizationId');
            $table->integer('userId');
            $table->enum('status', ['ACTIVE', 'CLOSED', 'SOLVED'])->default('ACTIVE');
            $table->string('caseNumber');
            $table->string('type');
            $table->string('dispo')->nullable()->default(NULL);
            $table->dateTime('lastSearched')->nullable()->default(NULL);
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
        Schema::dropIfExists('cases');
    }
}
