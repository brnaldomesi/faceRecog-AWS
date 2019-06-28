<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddImagedateToFaceTmpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('face_tmps', function (Blueprint $table) {
            $table->dateTime('imagedate')->after('pose');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('face_tmps', function (Blueprint $table) {
            $table->dropColumn('imagedate');
        });
    }
}
