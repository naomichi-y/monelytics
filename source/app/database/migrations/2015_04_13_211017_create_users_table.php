<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('users', function(Blueprint $table)
    {
      $table->increments('id')->unsigned();
      $table->string('email', 128);
      $table->string('password', 64)->nullable();
      $table->string('nickname', 32);
      $table->tinyInteger('type')->default(1)->unsigned();
      $table->rememberToken();
      $table->timestamp('create_date');
      $table->timestamp('last_update_date')->nullableTimestamps();
      $table->timestamp('delete_date')->nullableTimestamps()->nullable();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::drop('users');
  }

}
