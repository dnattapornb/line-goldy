<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeyRomJobIdToRomCharactersTable extends Migration
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
            $table->bigInteger('rom_job_id')->unsigned()->index()->nullable()->after('line_user_id');
            $table->foreign('rom_job_id')->references('id')->on('rom_jobs');
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
            $table->dropForeign(['rom_job_id']);
            $table->dropColumn('rom_job_id');
        });
    }
}
