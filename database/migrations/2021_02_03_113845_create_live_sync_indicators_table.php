<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiveSyncIndicatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('live_sync_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('value');
            $table->string('facility_code');
            $table->string('facility_name')->nullable();
            $table->dateTime('indicator_date')->nullable();
            $table->string('indicator_id')->nullable();
            $table->string('stage')->nullable();
            $table->string('facility_manifest_id')->nullable();
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
        Schema::dropIfExists('live_sync_indicators');
    }
}
