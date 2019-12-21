<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeCaseMatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('case_matches', function (Blueprint $table) {
            $table->dropColumn('match_imageId');
			$table->dropColumn('similarity');
			$table->longtext('results')->after('source_imageId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('case_matches', function (Blueprint $table) {
            //
        });
    }
}
