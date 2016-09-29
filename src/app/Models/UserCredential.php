<?php
namespace App\Models;

class UserCredential extends BaseModel {
  const CREDENTIAL_TYPE_GENERAL = 1;
  const CREDENTIAL_TYPE_FACEBOOK = 2;

  protected $guarded = ['id'];

  public function user()
  {
    return $this->belongTo('App\Models\User');
  }
}
