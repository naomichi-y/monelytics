<?php
namespace App\Libraries\Condition;

class DailyPaginateCondition extends BaseDateCondition {
    public $activity_category_group_id = [];
    public $keyword;
    public $location;
    public $credit_flag;
    public $cost_type;
    public $sort_field;
    public $sort_type;
    public $limit;

    public function __construct(array $fields = [])
    {
        if (empty($fields['sort_field'])) {
            $fields['sort_field'] = 'activity_date';
        }

        // 並び順は利用者入力から来る。Laravel 13 は asc/desc 以外を拒否するため、
        // 旧来の「asc 以外は降順」という挙動を保ったまま値を確定させる。
        $fields['sort_type'] = strtolower($fields['sort_type'] ?? '') === 'asc' ? 'asc' : 'desc';

        if (empty($fields['limit'])) {
            $fields['limit'] = \Agent::isMobile() ? 5 : 30;
        }

        parent::__construct($fields);
    }
}
