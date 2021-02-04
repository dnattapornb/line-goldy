<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveMainColumnFromRomCharactersTable extends Migration
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
            $table->dropColumn('main');
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
            $table->boolean('main')->default(true);
        });
    }
}
