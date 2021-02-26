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
            $table->bigInteger('facility_id')->index();
            $table->string('uid')->nullable()->index();
            $table->dateTime('create_date')->nullable();
            $table->string('name')->nullable()->index();
            $table->string('value')->nullable();
            $table->dateTime('metric_date')->nullable();
            $table->string('manifest_id')->nullable()->index();
            $table->string('dwh_value')->nullable();
            $table->dateTime('dwh_metric_date')->nullable();
            $table->boolean('processed')->default(false)->index();
            $table->boolean('posted')->default(false)->index();
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
        Schema::dropIfExists('facility_metrics');
    }
}
