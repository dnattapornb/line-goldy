<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRomCharactersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rom_characters', function (Blueprint $table)
        {
            $table->bigInteger('id')->unsigned()->autoIncrement();
            $table->bigInteger('key')->unsigned()->unique();
            $table->string('name')->default('');
            $table->boolean('main')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rom_characters');
    }
}
