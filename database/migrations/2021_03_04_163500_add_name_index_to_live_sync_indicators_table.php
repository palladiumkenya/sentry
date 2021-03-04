<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNameIndexToLiveSyncIndicatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('live_sync_indicators', function (Blueprint $table) {
            $table->index('name');
            $table->index('indicator_date');
            $table->index('stage');
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
        Schema::table('live_sync_indicators', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['indicator_date']);
            $table->dropIndex(['stage']);
            $table->dropSoftDeletes();
        });
    }
}
