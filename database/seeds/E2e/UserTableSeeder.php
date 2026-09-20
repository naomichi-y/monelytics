<?php
namespace Seeds\E2e;

use DB;
use Hash;
use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\UserCredential;

class UserTableSeeder extends Seeder {
    const USER_ID = 1;
    const EMAIL = 'e2e@monelytics.test';
    const PASSWORD = 'e2e-password';

    public function run()
    {
        DB::table('users')->truncate();
        DB::table('user_credentials')->truncate();

        $user = User::create([
            'id' => self::USER_ID,
            'email' => self::EMAIL,
            'password' => Hash::make(self::PASSWORD),
            'nickname' => 'e2e',
            'type' => User::TYPE_GENERAL,
        ]);

        UserCredential::create([
            'user_id' => $user->id,
            'credential_type' => UserCredential::CREDENTIAL_TYPE_GENERAL,
        ]);
    }
}
