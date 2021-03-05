<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('mechanism')->nullable()->index();
            $table->string('agency')->nullable()->index();
            $table->string('project')->nullable()->index();
            $table->string('code')->nullable()->index();
            $table->string('uid')->nullable()->index();
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->string('source')->nullable()->index();
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
        Schema::dropIfExists('partners');
    }
}
