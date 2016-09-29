<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivitiesTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('activities', function(Blueprint $table)
    {
      $table->increments('id')->unsigned();
      $table->integer('user_id')->unsigned();
      $table->date('activity_date');
      $table->integer('activity_category_group_id')->unsigned();
      $table->string('location', 64)->nullable();
      $table->string('content', 255)->nullable();
      $table->integer('amount');
      $table->tinyInteger('credit_flag')->default(0)->unsigned();
      $table->tinyInteger('special_flag')->default(0)->unsigned();
      $table->timestamp('create_date')->default(DB::raw('CURRENT_TIMESTAMP'));
      $table->timestamp('last_update_date')->default(DB::raw('CURRENT_TIMESTAMP'))->nullableTimestamps();
      $table->timestamp('delete_date')->nullableTimestamps()->nullable();

      $table->index('activity_date');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::drop('activities');
  }

}
