<?php
namespace Seeds\Test;

use Illuminate\Database\Seeder;

use DB;
use Hash;

use App\Models\User;
use App\Models\UserCredential;

class UserTableSeeder extends Seeder {
  public function run()
  {
    DB::table('users')->truncate();
    DB::table('user_credentials')->truncate();

    $users = [
      [
        'id' => 1,
        'email' => 'test@monelytics.me',
        'password' => Hash::make('testtest'),
        'nickname' => 'test',
        'type' => User::TYPE_GENERAL
      ]
    ];

    foreach ($users as $user) {
      $user = User::create($user);

      $user_credential = [
        'user_id' => $user->id,
        'credential_type' => UserCredential::CREDENTIAL_TYPE_GENERAL
      ];

      UserCredential::create($user_credential);
    }
  }
}
