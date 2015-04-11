<?php
class UserService
{
  private $user;
  private $activity_category;
  private $activity_category_group;

  /**
   * コンストラクタ。
   *
   * @param User $user
   */
  public function __construct(User $user, ActivityCategory $activity_category, ActivityCategoryGroup $activity_category_group)
  {
    $this->user = $user;
    $this->activity_category = $activity_category;
    $this->activity_category_group = $activity_category_group;
  }


  /**
   * ユーザを登録する。
   *
   * @param array $fields
   * @param bool &$errors
   * @return bool
   */
  public function create(array $fields, array &$errors = array())
  {
    $result = false;

    if ($this->user->validate($fields)) {
      $raw_password = $fields['password'];
      $fields['password'] = Hash::make($fields['password']);

      $user = $this->user->create($fields);

      if ($this->login($fields['email'], $raw_password, false, $errors)) {
        $this->setup($user->id);
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
  public function setup($user_id)
  {
    $result = array();
    $path = base_path() . '/app/storage/meta/setup.json';
    $activity_category_datum = json_decode(File::get($path));

    // 科目カテゴリの登録
    foreach ($activity_category_datum as $activity_category_data) {
      $data = $activity_category_data->record;
      $data->user_id = $user_id;

      $activity_category = new $this->activity_category((array) $data);
      $activity_category->save();

      // 科目カテゴリグループの登録
      if (isset($activity_category_data->relations)) {
        $activity_category_group_datum = $activity_category_data->relations->activity_category_groups;

        foreach ($activity_category_group_datum as $activity_category_group_data) {
          $data = $activity_category_group_data->record;
          $data->activity_category_id = $activity_category->id;

          $activity_category_group = new $this->activity_category_group((array) $data);
          $activity_category_group->user_id = $user_id;
          $activity_category_group->save();

          $result[] = array(
            'id' => $activity_category_group->id,
            'balance_type' => $activity_category->balance_type,
            'cost_type' => $activity_category->cost_type
          );
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
  public function login($email, $password, $remember_me = false, array &$errors = array())
  {
    $fields = array('email' => $email, 'password' => $password);
    $result = false;

    if ($this->user->authValidate($fields)) {
      if (Auth::attempt($fields, $remember_me)) {
        $result = Auth::getUser();

      } else {
        $errors[] = Lang::get('validation.custom.login.error');
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
  public function update($user_id, array $fields, &$errors = array())
  {
    $fields['id'] = $user_id;
    $result = false;

    if ($this->user->updateValidate($fields)) {
      $user = $this->user->find($user_id);
      $user->email = $fields['email'];
      $user->nickname = $fields['nickname'];

      if (strlen($fields['password'])) {
        $user->password = Hash::make($fields['password']);
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
    $user = $this->user->find($user_id);

    if ($user->type != User::TYPE_DEMO) {
      $user->delete();
    }
  }
}
