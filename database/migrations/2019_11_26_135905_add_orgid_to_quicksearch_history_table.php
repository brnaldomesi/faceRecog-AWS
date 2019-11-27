<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrgidToQuicksearchHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quicksearch_history', function (Blueprint $table) {
            //
			$table->tinyInteger('organizationId')->after('userid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quicksearch_history', function (Blueprint $table) {
            //
			$table->dropColumn('organizationId');
        });
    }
}
