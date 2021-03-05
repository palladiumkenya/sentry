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
            $table->bigInteger('facility_id')->index();
            $table->string('uid')->nullable()->index();
            $table->string('partner')->nullable()->index();
            $table->string('docket')->nullable()->index();
            $table->bigInteger('expected')->nullable();
            $table->bigInteger('received')->nullable();
            $table->string('status')->nullable()->index();
            $table->dateTime('updated')->nullable();
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
        Schema::dropIfExists('facility_uploads');
    }
}
