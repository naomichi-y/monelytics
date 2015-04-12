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

  public function activityCategory()
  {
    return $this->belongsTo('ActivityCategory', 'activity_category_id', 'id');
  }

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = array('password');

  public static function boot()
  {
    parent::boot();

    static::deleting(function($user) {
      $activity_category_groups = App::make('ActivityCategoryGroup')
        ->where('user_id', '=', $user->id)
        ->get();


      foreach ($activity_category_groups as $activity_category_group) {
        $activity_category_id = $activity_category_group->activity_category_id;

        $activity_category = App::make('ActivityCategory')->find($activity_category_id);

        if ($activity_category) {
          $activity_category->delete();
        }
      }
    });
  }

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
