<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPersonidInfoToFaceTmpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('face_tmps', function (Blueprint $table) {
            $table->string('filename')->after('gender');
			$table->string('personId')->after('filename');
			$table->string('name')->after('personId');
			$table->string('dob')->after('name');
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
            $table->dropColumn('filename');
			$table->dropColumn('personId');
			$table->dropColumn('name');
			$table->dropColumn('dob');
        });
    }
}
