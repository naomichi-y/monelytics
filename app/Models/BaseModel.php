<?php
namespace App\Models;

use Validator;

use Illuminate\Database\Eloquent\SoftDeletes;

class BaseModel extends \Eloquent {
    use SoftDeletes;

    const CREATED_AT = 'create_date';
    const UPDATED_AT = 'last_update_date';
    const DELETED_AT = 'delete_date';

    protected $rules = [];
    protected $messages = [];
    protected $errors;

    /**
     * 既定のルールは $rules だが、用途ごとに異なるルールを使いたい場合は
     * 引数で渡す。インスタンスを共有したまま $rules を書き換えると、
     * 後続の検証まで影響を受けるため。
     *
     * @param array $fields
     * @param array|null $rules
     * @param array|null $messages
     * @return bool
     */
    public function validate(array $fields, ?array $rules = null, ?array $messages = null)
    {
        $validator = Validator::make($fields, $rules ?? $this->rules, $messages ?? $this->messages);
        $result = true;

        if ($validator->fails()) {
            $this->errors = $validator->messages();
            $result = false;
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors->toArray();
    }
}
