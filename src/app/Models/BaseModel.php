<?php
namespace App\Models;

use Validator;

use Illuminate\Database\Eloquent\SoftDeletes;

class BaseModel extends \Eloquent {
  use SoftDeletes;

  const CREATED_AT = 'create_date';
  const UPDATED_AT = 'last_update_date';
  const DELETED_AT = 'delete_date';

  protected $rules = array();
  protected $messages = array();
  protected $errors;

  /**
   * @param array $fields
   * @return bool
   */
  public function validate(array $fields)
  {
    $validator = Validator::make($fields, $this->rules, $this->messages);
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
