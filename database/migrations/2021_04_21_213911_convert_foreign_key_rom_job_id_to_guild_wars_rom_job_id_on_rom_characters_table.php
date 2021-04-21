<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ConvertForeignKeyRomJobIdToGuildWarsRomJobIdOnRomCharactersTable extends Migration
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
            $table->dropForeign(['rom_job_id']);
            $table->renameColumn('rom_job_id', 'guild_wars_rom_job_id');
            $table->foreign('guild_wars_rom_job_id')->references('id')->on('rom_jobs');
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
            $table->dropForeign(['guild_wars_rom_job_id']);
            $table->renameColumn('guild_wars_rom_job_id', 'rom_job_id');
            $table->foreign('rom_job_id')->references('id')->on('rom_jobs');
        });
    }
}
