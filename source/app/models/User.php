<?php

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableInterface;

class User extends BaseModel implements UserInterface, RemindableInterface {
  const TYPE_GENERAL = 1;
  const TYPE_DEMO = 9;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'users';

  protected $guarded = array('id');

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = array('password');

  protected $validate_rules = array(
    'nickname' => 'required|max:32',
    'email' => 'required|not_exists:users,email',
    'password' => 'required|min:8'
  );

  public function authValidate(array $fields)
  {
    $this->validate_rules = array(
      'email' => 'required',
      'password' => 'required'
    );

    return $this->validate($fields);
  }

  public function updateValidate(array $fields)
  {
    $this->validate_rules = array(
      'id' => 'required'
    );

    $user = $this->find($fields['id']);

    if ($user->email != $fields['email']) {
      $this->validate_rules['email'] = 'required|not_exists:users,email';
    }

    if (strlen($fields['password'])) {
      $this->validate_rules['password'] = 'required|min:8|confirmed';
    }

    return $this->validate($fields);
  }

	/**
	 * Get the unique identifier for the user.
	 *
	 * @return mixed
	 */
	public function getAuthIdentifier()
	{
		return $this->getKey();
	}

	/**
	 * Get the password for the user.
	 *
	 * @return string
	 */
	public function getAuthPassword()
  {
		return $this->password;
	}

	/**
	 * Get the token value for the "remember me" session.
	 *
	 * @return string
	 */
	public function getRememberToken()
	{
		return $this->remember_token;
	}

	/**
	 * Set the token value for the "remember me" session.
	 *
	 * @param  string  $value
	 * @return void
	 */
	public function setRememberToken($value)
	{
		$this->remember_token = $value;
	}

	/**
	 * Get the column name for the "remember me" token.
	 *
	 * @return string
	 */
	public function getRememberTokenName()
	{
		return 'remember_token';
	}

	/**
	 * Get the e-mail address where password reminders are sent.
	 *
	 * @return string
	 */
	public function getReminderEmail()
	{
		return $this->email;
	}
}
