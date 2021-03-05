<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacilityPartnerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facility_partner', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('facility_id')->unsigned()->index();
            $table->bigInteger('partner_id')->unsigned()->index();
            $table->string('docket')->nullable()->index();
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('facility_partner');
    }
}
