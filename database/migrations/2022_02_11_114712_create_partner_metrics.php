<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnerMetrics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partner_metrics', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('partner_id')->index();
            $table->string('name')->nullable()->index();
            $table->string('value')->nullable();
            $table->dateTime('metric_date')->nullable();
            $table->string('dwh_value')->nullable();
            $table->dateTime('dwh_metric_date')->nullable();
            $table->boolean('posted')->default(false)->index();
            $table->bigInteger('etl_job_id')->unsigned()->nullable()->index();
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
        Schema::dropIfExists('partner_metrics');
    }
}
