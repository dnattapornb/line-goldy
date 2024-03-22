<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLineUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('line_users', function (Blueprint $table)
        {
            $table->bigInteger('id')->unsigned()->autoIncrement();
            $table->string('key')->unique();
            $table->string('name')->default('');
            $table->string('display_name')->default('');
            $table->string('picture_url')->default('');
            $table->boolean('published')->default(false);
            $table->boolean('is_friend')->default(false);
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
        Schema::dropIfExists('line_users');
    }
}
