<?php
namespace App\Validators;

use DB;

class NotExistsValidator extends \Illuminate\Validation\Validator
{
    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     * @return int
     */
    public function validateNotExists($attribute, $value, $parameters)
    {
        $count = DB::table($parameters[0])
            ->where($parameters[1], '=', $value)
            ->whereNull('delete_date')
            ->count();

        if ($count) {
            return false;
        }

        return true;
    }
}
