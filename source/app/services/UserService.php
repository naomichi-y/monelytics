<?php
class UserService
{
  private $user;

  /**
   * コンストラクタ。
   *
   * @param User $user
   */
  public function __construct(User $user)
  {
    $this->user = $user;
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

      $this->user->create($fields);

      if ($this->login($fields['email'], $raw_password, false, $errors)) {
        $result = true;
      }

    } else {
      $errors = $this->user->getErrors();
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
