<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacilityUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facility_uploads', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('facility_id');
            $table->string('uid')->nullable();
            $table->string('partner')->nullable();
            $table->string('docket')->nullable();
            $table->bigInteger('expected')->nullable();
            $table->bigInteger('received')->nullable();
            $table->string('status')->nullable();
            $table->dateTime('updated')->nullable();
            $table->boolean('posted')->default(false);
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
        Schema::dropIfExists('facility_uploads');
    }
}
