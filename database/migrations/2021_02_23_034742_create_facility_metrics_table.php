<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacilityMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facility_metrics', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('facility_id');
            $table->string('uid')->nullable();
            $table->dateTime('create_date')->nullable();
            $table->string('name')->nullable();
            $table->string('value')->nullable();
            $table->dateTime('metric_date')->nullable();
            $table->dateTime('manifest_id')->nullable();
            $table->string('dwh_value')->nullable();
            $table->dateTime('dwh_metric_date')->nullable();
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
        Schema::dropIfExists('facility_metrics');
    }
}
