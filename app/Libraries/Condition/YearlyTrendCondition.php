<?php
namespace App\Libraries\Condition;

class YearlyTrendCondition extends BaseCondition {
    public $begin_year;
    public $end_year;

    /**
     * ActivityCategory::BALANCE_TYPE_* のいずれか。
     * 未指定なら収支 (支出と収入の合算) を対象にする。
     */
    public $balance_type;
}
