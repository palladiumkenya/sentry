<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubCountyToFacilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('sub_county')->nullable()->index();
            $table->string('ward')->nullable()->index();
            $table->string('constituency')->nullable()->index();
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->dropIndex(['partner']);
            $table->dropColumn('partner');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('partner')->nullable()->index();
            $table->dropIndex(['sub_county']);
            $table->dropColumn('sub_county');
            $table->dropIndex(['ward']);
            $table->dropColumn('ward');
            $table->dropIndex(['constituency']);
            $table->dropColumn('constituency');
            $table->dropIndex(['created_by']);
            $table->dropColumn('created_by');
        });
    }
}
