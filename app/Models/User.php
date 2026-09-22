<?php
namespace App\Models;

use Lang;

use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends BaseModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract {
    use Notifiable, Authenticatable, Authorizable, CanResetPassword;

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

    public function activityCategoryItems()
    {
        return $this->hasMany('App\Models\ActivityCategoryItem');
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
        // email 規則がないと 'this-is-not-an-email' のような文字列でも登録できる。
        'email' => 'required|email|not_exists:users,email',
        'password' => 'required|min:8'
    ];

    public function loginValidate(array $fields)
    {
        return $this->validate($fields, [
            'email' => 'required',
            'password' => 'required'
        ]);
    }

    public function updateValidate(array $fields)
    {
        $rules = [
            'id' => 'required'
        ];

        $user = $this->find($fields['id']);

        if ($user->email != $fields['email']) {
            $rules['email'] = 'required|email|not_exists:users,email';
        }

        if (strlen($fields['password'] ?? '')) {
            $rules['password'] = 'required|min:8|confirmed';

            // 現在のパスワードを問うのは、置き去りのセッションや盗まれた
            // Cookie だけでパスワードを差し替えられないようにするため。
            // 廃止した Facebook ログインだけで作られた利用者は password が
            // 空で、入力できる現在のパスワードを持たない。その人にまで
            // 求めるとパスワードを設定する手段がなくなるため、既に持って
            // いる人だけに課す (edit.blade.php も同じ条件で欄を出す)。
            // 一致するかどうかは保存済みのハッシュと突き合わせる
            // UserService::update が見る。
            if (strlen($user->password ?? '')) {
                $rules['current_password'] = 'required';
            }
        }

        return $this->validate($fields, $rules);
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
