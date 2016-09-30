<?php
namespace App\Models;

use Lang;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends BaseModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract {
    use Authenticatable, Authorizable, CanResetPassword;

    const TYPE_GENERAL = 1;

    protected $guarded = ['id'];

    public function userCredential()
    {
        return $this->hasMany('App\Models\UserCredential');
    }

    public function activityCategories()
    {
        return $this->hasMany('App\Models\ActivityCategory');
    }

    public function activityCategoryGroups()
    {
        return $this->hasMany('App\Models\ActivityCategoryGroup');
    }

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password'];

    public static function boot()
    {
        parent::boot();

        static::deleting(function($user) {
            $activity_categories = $user->activityCategories()->get();

            foreach ($activity_categories as $activity_category) {
                $activity_category->delete();
            }

            $user->userCredential()->delete();
        });
    }

    protected $rules = [
        'nickname' => 'required|max:32',
        'email' => 'required|not_exists:users,email',
        'password' => 'required|min:8'
    ];

    public function oauthValidate(array $fields)
    {
        $this->rules = [
            'nickname' => 'required|max:32',
            'email' => 'required|not_exists:users,email'
        ];
        $this->messages = [
            'email.not_exists' => Lang::get('validation.custom.user.create_oauth.registered')
        ];

        return $this->validate($fields);
    }

    public function loginValidate(array $fields)
    {
        $this->rules = [
            'email' => 'required',
            'password' => 'required'
        ];

        return $this->validate($fields);
    }

    public function updateValidate(array $fields)
    {
        $this->rules = [
            'id' => 'required'
        ];

        $user = $this->find($fields['id']);

        if ($user->email != $fields['email']) {
            $this->rules['email'] = 'required|not_exists:users,email';
        }

        if (strlen($fields['password'])) {
            $this->rules['password'] = 'required|min:8|confirmed';
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
