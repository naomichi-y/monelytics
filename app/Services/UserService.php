<?php
namespace App\Services;

use Auth;
use File;
use Hash;
use Lang;

use App\Models;

class UserService
{
    private $user;
    private $user_credential;
    private $activity_category;
    private $activity_category_item;

    /**
     * コンストラクタ。
     *
     * @param \Models\User $user
     * @param \Models\UserCredential $user_credential
     * @param \Models\ActivityCategory $activity_category
     * @param \Models\ActivityCategoryItem $activity_category_item
     */
    public function __construct(
        Models\User $user,
        Models\UserCredential $user_credential,
        Models\ActivityCategory $activity_category,
        Models\ActivityCategoryItem $activity_category_item)
    {
        $this->user = $user;
        $this->user_credential = $user_credential;
        $this->activity_category = $activity_category;
        $this->activity_category_item = $activity_category_item;
    }

    /**
     * ユーザを登録する。
     *
     * @param array $fields
     * @param bool &$errors
     * @return bool
     */
    public function create($fields, array &$errors = [])
    {
        $result = false;

        if ($this->user->validate($fields)) {
            $raw_password = $fields['password'];
            $fields['password'] = Hash::make($fields['password']);

            $user = $this->user->create($fields);

            $this->user_credential->create([
                'user_id' => $user->id,
                'credential_type' => Models\UserCredential::CREDENTIAL_TYPE_GENERAL
            ]);

            if ($this->login($fields['email'], $raw_password, false, $errors)) {
                $this->seed($user->id);
                $result = true;
            }

        } else {
            $errors = $this->user->getErrors();
        }

        return $result;
    }

    /**
     * ユーザの初期データを登録する。
     *
     * @param int $user_id
     * @return array
     */
    public function seed($user_id)
    {
        $result = [];
        $path = base_path() . '/resources/master/setup.json';
        $activity_category_datum = json_decode(File::get($path));

        // 大項目の登録
        foreach ($activity_category_datum as $activity_category_data) {
            $data = $activity_category_data->record;
            $data->user_id = $user_id;

            $activity_category = new $this->activity_category((array) $data);
            $activity_category->save();

            // 小項目の登録
            if (isset($activity_category_data->relations)) {
                $activity_category_item_datum = $activity_category_data->relations->activity_category_items;

                foreach ($activity_category_item_datum as $activity_category_item_data) {
                    $data = $activity_category_item_data->record;
                    $data->activity_category_id = $activity_category->id;

                    $activity_category_item = new $this->activity_category_item((array) $data);
                    $activity_category_item->user_id = $user_id;
                    $activity_category_item->save();

                    $result[] = [
                        'id' => $activity_category_item->id,
                        'balance_type' => $activity_category->balance_type,
                        'cost_type' => $activity_category->cost_type
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * ログイン処理を行う。
     *
     * @param string $email
     * @param string $password
     * @param bool $remember_me
     * @param array &$errors
     * @return bool
     */
    public function login($email, $password, $remember_me = false, array &$errors = [])
    {
        $fields = ['email' => $email, 'password' => $password];
        $result = false;

        if ($this->user->loginValidate($fields)) {
            if (Auth::attempt($fields, $remember_me)) {
                $result = Auth::getUser();

            } else {
                $errors[] = Lang::get('validation.custom.user.login.authentication');
            }

        } else {
            $errors = $this->user->getErrors();
        }

        return $result;
    }


    /**
     * ユーザデータを更新する。
     *
     * @param $int user_id
     * @param array $fields
     * @param &$fields
     * @return bool
     */
    public function update($user_id, array $fields, &$errors = [])
    {
        $fields['id'] = $user_id;
        $result = false;

        if ($this->user->updateValidate($fields)) {
            $user = $this->user->find($user_id);
            $user->email = $fields['email'];
            $user->nickname = $fields['nickname'];

            if (strlen($fields['password'])) {
                $user->password = Hash::make($fields['password']);
                $count = $user->userCredential()
                    ->where('credential_type', '=', Models\UserCredential::CREDENTIAL_TYPE_GENERAL)
                    ->count();

                if ($count == 0) {
                    $this->user_credential->create([
                        'user_id' => $user->id,
                        'credential_type' => Models\UserCredential::CREDENTIAL_TYPE_GENERAL
                    ]);
                }
            }

            $user->save();
            $result = true;

        } else {
            $errors = $this->user->getErrors();
        }

        return $result;
    }

    /**
     * ログアウト処理を行う。
     */
    public function logout()
    {
        Auth::logout();
    }

    /**
     * ユーザを削除する。
     *
     * @param int $user_id
     */
    public function delete($user_id) {
        $this->user->find($user_id)->delete();
    }
}
