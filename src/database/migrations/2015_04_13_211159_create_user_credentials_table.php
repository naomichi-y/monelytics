<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserCredentialsTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('user_credentials', function(Blueprint $table)
    {
      $table->increments('id')->unsigned();
      $table->integer('user_id')->unsigned();
      $table->tinyInteger('credential_type')->unsigned();
      $table->string('credential_id', 255)->nullable();
      $table->string('access_token', 255)->nullable();
      $table->timestamp('create_date')->default(DB::raw('CURRENT_TIMESTAMP'));
      $table->timestamp('last_update_date')->default(DB::raw('CURRENT_TIMESTAMP'))->nullableTimestamps();
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
    Schema::drop('user_credentials');
  }

}
