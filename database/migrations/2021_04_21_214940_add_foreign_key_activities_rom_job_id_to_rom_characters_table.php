<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeyActivitiesRomJobIdToRomCharactersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rom_characters', function (Blueprint $table)
        {
            $table->bigInteger('activities_rom_job_id')->unsigned()->index()->nullable()->after('guild_wars_rom_job_id');
            $table->foreign('activities_rom_job_id')->references('id')->on('rom_jobs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rom_characters', function (Blueprint $table)
        {
            $table->dropForeign(['activities_rom_job_id']);
            $table->dropColumn('activities_rom_job_id');
        });
    }
}
