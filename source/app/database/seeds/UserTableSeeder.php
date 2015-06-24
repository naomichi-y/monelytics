<?php
use Monelytics\Models;

class UserTableSeeder extends Seeder {
  public function run()
  {
    DB::table('users')->truncate();
    DB::table('user_credentials')->truncate();

    $users = array(
      array(
        'id' => 1,
        'email' => 'test@monelytics.me',
        'password' => Hash::make('testtest'),
        'nickname' => 'test',
        'type' => Models\User::TYPE_GENERAL
      )
    );

    foreach ($users as $user) {
      $user = Models\User::create($user);

      $user_credential = array(
        'user_id' => $user->id,
        'credential_type' => Models\UserCredential::CREDENTIAL_TYPE_GENERAL
      );

      Models\UserCredential::create($user_credential);
    }
  }
}
