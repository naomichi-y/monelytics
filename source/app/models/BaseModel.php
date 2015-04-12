<?php

use Illuminate\Database\Eloquent\SoftDeletingTrait;

class BaseModel extends Eloquent {
  use SoftDeletingTrait;

  const CREATED_AT = 'create_date';
  const UPDATED_AT = 'last_update_date';
  const DELETED_AT = 'delete_date';

  protected $validate_rules = array();
  protected $errors;

  /**
   * @param array $fields
   * @return bool
   */
  public function validate(array $fields)
  {
    $validator = Validator::make($fields, $this->validate_rules);
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