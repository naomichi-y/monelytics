<?php
class UserCredential extends BaseModel {
  const CREDENTIAL_TYPE_GENERAL = 1;
  const CREDENTIAL_TYPE_FACEBOOK = 2;

  protected $guarded = array('id');

  public function user()
  {
    return $this->belongTo('User');
  }
}
