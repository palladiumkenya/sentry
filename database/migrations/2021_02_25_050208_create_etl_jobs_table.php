<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEtlJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('etl_jobs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('job_date')->index();
            $table->dateTime('started_at')->nullable()->index();
            $table->dateTime('completed_at')->nullable()->index();
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('facility_uploads', function (Blueprint $table) {
            $table->bigInteger('etl_jobs_id')->unsigned()->nullable()->index();
        });

        Schema::table('facility_metrics', function (Blueprint $table) {
            $table->bigInteger('etl_jobs_id')->unsigned()->nullable()->index();
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
            $table->dropIndex(['etl_jobs_id']);
            $table->dropColumn('etl_jobs_id');
        });

        Schema::table('facility_metrics', function (Blueprint $table) {
            $table->dropIndex(['etl_jobs_id']);
            $table->dropColumn('etl_jobs_id');
        });

        Schema::dropIfExists('etl_jobs');
    }
}
