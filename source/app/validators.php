<?php
/**
 * @param array $attribute
 * @param string $value
 * @param array $parameters
 * @return bool
 */
Validator::extend('not_exists', function($attribute, $value, $parameters)
{
  $count = DB::table($parameters[0])
    ->where($parameters[1], '=', $value)
    ->whereNull('delete_date')
    ->count();

  if ($count) {
    return false;
  }

  return true;
});
