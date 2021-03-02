<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPartnerIdToFacilityUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('facility_uploads', function (Blueprint $table) {
            $table->bigInteger('partner_id')->unsigned()->index();
            $table->dropIndex(['partner']);
            $table->dropColumn('partner');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('facility_uploads', function (Blueprint $table) {
            $table->string('partner')->nullable()->index();
            $table->dropColumn('partner_id');
        });
    }
}
