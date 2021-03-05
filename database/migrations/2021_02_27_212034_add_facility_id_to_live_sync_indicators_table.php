<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFacilityIdToLiveSyncIndicatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('live_sync_indicators', function (Blueprint $table) {
            $table->bigInteger('facility_id')->unsigned()->nullable()->index();
            $table->dropColumn('facility_code');
            $table->dropColumn('facility_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('live_sync_indicators', function (Blueprint $table) {
            $table->dropIndex(['facility_id']);
            $table->dropColumn('facility_id');
            $table->string('facility_code');
            $table->string('facility_name')->nullable();
        });
    }
}
