<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestMailListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_mail_list', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable()->index();
            $table->string('name')->nullable();
            $table->enum('list_subscribed', ['Paeds','Covid','DQA','NUPI','Triangulation'])->nullable();
            $table->boolean('is_main');
            $table->boolean('is_cc');
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
        Schema::dropIfExists('test_mail_list');
    }
}
