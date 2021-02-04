<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeyToRomCharactersTable extends Migration
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
            $table->bigInteger('line_user_id')->unsigned()->index()->nullable()->after('main');
            $table->foreign('line_user_id')->references('id')->on('line_users');
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
            $table->dropForeign('line_user_id');
            $table->dropColumn('line_user_id');
        });
    }
}
